<template>
  <div class="page-view profile-dashboard">
    <div class="profile-header">
      <div class="header-left">
        <button class="btn-icon" @click="router.back()">
          <i class="pi pi-arrow-right"></i>
        </button>
        <div class="profile-title-block">
          <div class="flex-align gap-3">
            <div class="unit-icon-box bg-indigo-light text-indigo">
              <i class="pi pi-home"></i>
            </div>
            <div>
              <h2 class="profile-title">{{ unit ? `وحدة #${unit.unit_number} - ${arabicUnitTypes[unit.unit_type] || unit.unit_type}` : 'جاري التحميل...' }}</h2>
              <span class="profile-subtitle">
                <i class="pi pi-building text-muted"></i>
                {{ unit?.building?.name || '—' }}{{ unit?.floor ? ` | الطابق ${unit.floor}` : '' }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-primary">
          <i class="pi pi-cog"></i> إجراء صيانة
        </button>
      </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="profile-kpi-grid">
      <div class="kpi-card highlight-card">
        <div class="kpi-top">
          <span class="kpi-label">حالة الوحدة</span>
          <i :class="unit?.status === 'occupied' ? 'pi pi-check-circle text-success' : unit?.status === 'maintenance' ? 'pi pi-exclamation-triangle text-danger' : 'pi pi-home text-warning'"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value" :class="statusClass">{{ statusText }}</span>
        </div>
        <span class="kpi-footer">{{ unit?.status === 'occupied' ? 'تدر عائد استثماري منتظم' : unit?.status === 'maintenance' ? 'قيد الصيانة حالياً' : 'متاحة للتأجير' }}</span>
      </div>

      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">قيمة الإيجار</span>
          <i class="pi pi-money-bill text-accent"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value">{{ unit ? format(unit.rent_amount) : '—' }}</span>
        </div>
        <span class="kpi-footer">مساحة الوحدة: {{ unit?.area || '—' }} م²</span>
      </div>

      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">تكلفة الصيانة (آخر 6 أشهر)</span>
          <i class="pi pi-wrench text-danger"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-danger">{{ format(totalMaintenanceCost) }}</span>
        </div>
        <span class="kpi-footer">{{ maintenanceItems.length }} طلب{{ maintenanceItems.length !== 1 ? 'ات' : '' }} صيانة</span>
      </div>
    </div>

    <!-- Main Content -->
    <div class="profile-body-grid mt-4">
      <!-- Maintenance Analytics & History -->
      <div class="profile-col-left">
        <div class="dashboard-widget">
          <div class="widget-header">
            <h3>تحليل تكلفة الصيانة للوحدة</h3>
          </div>
          <div class="chart-wrapper">
            <Bar :data="maintenanceChartData" :options="chartOptions" />
          </div>
        </div>

        <div class="dashboard-widget mt-4">
          <div class="widget-header">
            <h3>تاريخ طلبات الصيانة</h3>
          </div>
          <div class="widget-list">
            <div v-if="maintenanceItems.length === 0" class="text-muted" style="padding: 20px; text-align: center;">لا توجد طلبات صيانة سابقة</div>
            <div v-for="m in maintenanceItems" :key="m.id" class="widget-list-item hover-lift">
              <div class="item-icon" :class="m.status === 'completed' ? 'bg-info-light' : 'bg-warning-light'">
                <i :class="m.status === 'completed' ? 'pi pi-check-circle text-info' : 'pi pi-cog text-warning'"></i>
              </div>
              <div class="item-info">
                <span class="item-title">{{ m.description }}</span>
                <span class="item-sub">{{ m.status === 'completed' ? 'اكتمل' : m.status === 'in_progress' ? 'قيد التنفيذ' : 'معلق' }}{{ m.cost ? ` | التكلفة: ${format(m.cost)}` : '' }}{{ m.completed_at ? ` | ${m.completed_at}` : '' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Current Tenant & Yield -->
      <div class="profile-col-right">
        <!-- Occupant Card -->
        <div class="dashboard-widget bg-indigo-light">
          <div class="widget-header">
            <h3>المستأجر الحالي</h3>
            <i class="pi pi-user text-indigo"></i>
          </div>
          <div v-if="contracts.length > 0 && contracts[0].tenant" class="tenant-mini-card">
            <div class="t-avatar"><i class="pi pi-user"></i></div>
            <div class="t-details">
              <h4>{{ contracts[0].tenant.first_name }} {{ contracts[0].tenant.last_name }}</h4>
              <span>رقم العقد: {{ contracts[0].contract_number }}</span>
            </div>
            <router-link :to="`/tenants/${contracts[0].tenant_id}`" class="btn-xs-primary ml-auto">ملف المستأجر</router-link>
          </div>
          <div v-else class="tenant-mini-card">
            <div class="t-avatar"><i class="pi pi-user"></i></div>
            <div class="t-details">
              <h4>لا يوجد مستأجر حالي</h4>
              <span>الوحدة شاغرة حالياً</span>
            </div>
          </div>
        </div>

        <!-- Financial Yield -->
        <div class="dashboard-widget mt-4">
          <div class="widget-header">
            <h3>الأداء المالي (Property Performance)</h3>
          </div>
          <div class="yield-stats">
            <div class="y-row">
              <span class="y-label">الإيجار الشهري</span>
              <span class="y-val text-success">{{ unit ? format(unit.rent_amount) : '—' }}</span>
            </div>
            <div class="y-row">
              <span class="y-label">المصروفات والصيانة</span>
              <span class="y-val text-danger">{{ format(totalMaintenanceCost) }}</span>
            </div>
            <div class="y-divider"></div>
            <div class="y-row">
              <span class="y-label font-bold text-primary">العائد السنوي التقديري</span>
              <span class="y-val font-bold text-primary">{{ unit ? format(unit.rent_amount * 12 - totalMaintenanceCost) : '—' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { formatCurrency } from '@/utils/currency'
import { useAppStore } from '@/stores/app'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const router = useRouter()
const route = useRoute()
const appStore = useAppStore()

const unit = ref(null)
const contracts = ref([])
const maintenanceItems = ref([])
const loading = ref(true)

const arabicUnitTypes = { apartment: 'سكني', shop: 'تجاري', warehouse: 'مستودع' }
const statusLabels = { available: 'شاغرة', occupied: 'مؤجرة', maintenance: 'صيانة' }
const statusColors = { available: 'text-warning', occupied: 'text-success', maintenance: 'text-danger' }

function format(val) {
  return formatCurrency(val || 0, appStore.selectedCurrency)
}

const maintenanceChartData = computed(() => {
  if (!maintenanceItems.value.length) {
    return { labels: [], datasets: [] }
  }
  const monthly = {}
  const now = new Date()
  for (let i = 5; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    monthly[key] = 0
  }
  maintenanceItems.value.forEach(m => {
    if (!m.completed_at && !m.created_at) return
    const date = new Date(m.completed_at || m.created_at)
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    if (monthly[key] !== undefined) monthly[key] += Number(m.cost || 0)
  })
  const arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
  return {
    labels: Object.keys(monthly).map(k => arabicMonths[parseInt(k.split('-')[1]) - 1]),
    datasets: [
      { label: 'تكلفة الصيانة (₪)', backgroundColor: '#F59E0B', borderRadius: 4, data: Object.values(monthly) }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: 'var(--border)' } } }
}

const totalMaintenanceCost = computed(() =>
  maintenanceItems.value.reduce((s, m) => s + Number(m.cost || 0), 0)
)

const statusClass = computed(() => statusColors[unit.value?.status] || 'text-muted')
const statusText = computed(() => statusLabels[unit.value?.status] || unit.value?.status || '—')

onMounted(async () => {
  try {
    const unitId = route.params.id
    const [unitRes, maintRes, contractRes] = await Promise.all([
      api.get(`/units/${unitId}`),
      api.get('/maintenance', { params: { unit_id: unitId } }),
      api.get('/contracts', { params: { unit_id: unitId, status: 'active' } })
    ])
    unit.value = unitRes.data?.data || null
    maintenanceItems.value = maintRes.data?.data || []
    contracts.value = contractRes.data?.data || []
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.profile-dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.profile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}
.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.flex-align { display: flex; align-items: center; }
.gap-3 { gap: 12px; }
.unit-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}
.bg-indigo-light { background: var(--accent-light); border: 1px solid var(--info-border); }
.text-indigo { color: var(--accent-hover); }

.profile-title {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}
.profile-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
}
.header-actions {
  display: flex;
  gap: 12px;
}

@media (max-width: 768px) {
  .profile-header {
    flex-wrap: wrap;
    gap: 12px;
  }
  .header-actions {
    width: 100%;
  }
}

.profile-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}
.kpi-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s ease;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.kpi-label { font-size: 13px; font-weight: 600; color: var(--text-secondary); }
.kpi-content { margin: 4px 0; }
.kpi-value { font-size: 24px; font-weight: 800; color: var(--text-primary); }
.kpi-footer { font-size: 11.5px; color: var(--text-muted); }
.text-success { color: var(--success); }
.text-accent { color: var(--accent-hover); }
.text-danger { color: var(--danger); }
.font-bold { font-weight: 700; }

.profile-body-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}
@media (max-width: 1024px) { .profile-body-grid { grid-template-columns: 1fr; } }
.mt-4 { margin-top: 20px; }

.dashboard-widget {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
}
.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.widget-header h3 { font-size: 14.5px; font-weight: 700; color: var(--text-primary); margin: 0; }
.chart-wrapper { height: 220px; width: 100%; }

.widget-list { display: flex; flex-direction: column; gap: 10px; }
.widget-list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: var(--bg-subtle);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
  transition: all 0.2s ease;
}
.item-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}
.bg-warning-light { background: var(--warning-bg); }
.text-warning { color: var(--warning-contrast); }
.bg-info-light { background: var(--info-bg); }
.text-info { color: var(--info-contrast); }

.item-info { flex: 1; display: flex; flex-direction: column; }
.item-title { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
.item-sub { font-size: 11.5px; color: var(--text-secondary); margin-top: 2px; }

/* Tenant Mini Card */
.tenant-mini-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--bg-surface);
  padding: 12px;
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-sm);
}
.t-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--info-bg);
  color: var(--info-contrast);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}
.t-details h4 { margin: 0; font-size: 13.5px; font-weight: 700; }
.t-details span { font-size: 11.5px; color: var(--text-secondary); }
.ml-auto { margin-right: auto; margin-left: 0; }
.btn-xs-primary {
  background: var(--accent);
  color: var(--text-on-accent);
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11.5px;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
}

/* Yield Stats */
.yield-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.y-row { display: flex; justify-content: space-between; font-size: 13.5px; }
.y-label { color: var(--text-secondary); }
.y-val { font-weight: 600; }
.y-divider { height: 1px; background: var(--border-light); margin: 4px 0; }
.text-primary { color: var(--text-primary); }
</style>
