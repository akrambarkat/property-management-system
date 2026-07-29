<template>
  <div class="dashboard page-view">
    <!-- Quick Actions Top Bar -->
    <div class="quick-actions-card">
      <div class="quick-actions-header">
        <div class="header-title">
          <i class="pi pi-bolt action-icon"></i>
          <span>إجراءات سريعة</span>
        </div>
        <span class="header-subtitle">اختصارات لإجراء العمليات اليومية بكفاءة عالية</span>
      </div>
      <div class="quick-actions-buttons">
        <router-link to="/locations" class="action-btn">
          <i class="pi pi-map-marker text-blue"></i>
          <span>إضافة موقع</span>
        </router-link>
        <router-link to="/buildings" class="action-btn">
          <i class="pi pi-building text-amber"></i>
          <span>إضافة مبنى</span>
        </router-link>
        <router-link to="/units" class="action-btn">
          <i class="pi pi-th-large text-emerald"></i>
          <span>إضافة وحدة</span>
        </router-link>
        <router-link to="/tenants" class="action-btn">
          <i class="pi pi-user-plus text-purple"></i>
          <span>إضافة مستأجر</span>
        </router-link>
        <router-link to="/contracts" class="action-btn accent-action">
          <i class="pi pi-file-plus"></i>
          <span>إنشاء عقد</span>
        </router-link>
        <router-link to="/payments" class="action-btn">
          <i class="pi pi-wallet text-green"></i>
          <span>تسجيل دفعة</span>
        </router-link>
        <router-link to="/expenses" class="action-btn">
          <i class="pi pi-minus-circle text-red"></i>
          <span>إضافة مصروف</span>
        </router-link>
        <router-link to="/maintenance" class="action-btn">
          <i class="pi pi-wrench text-orange"></i>
          <span>طلب صيانة</span>
        </router-link>
      </div>
    </div>

    <!-- 9 Enterprise SaaS KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card" v-for="kpi in kpiCards" :key="kpi.label">
        <div class="kpi-top">
          <div class="kpi-icon" :style="{ background: kpi.iconBg, color: kpi.iconColor }">
            <i :class="kpi.icon"></i>
          </div>
          <div v-if="kpi.badge" class="kpi-badge" :class="kpi.badgeClass">
            <i :class="kpi.badgeIcon"></i>
            <span>{{ kpi.badge }}</span>
          </div>
        </div>
        <div class="kpi-content">
          <span class="kpi-value">{{ kpi.value }}</span>
          <span class="kpi-label">{{ kpi.label }}</span>
        </div>
        <div class="kpi-footer" v-if="kpi.subtext">
          <span class="kpi-subtext">{{ kpi.subtext }}</span>
        </div>
      </div>
    </div>

    <!-- Charts & Analytics Section -->
    <div class="charts-grid">
      <!-- Income vs Expenses Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <div>
            <h3 class="chart-title">الأداء المالي (الإيرادات مقابل المصروفات وصافي الربح)</h3>
            <p class="chart-subtitle">مقارنة الخرج والدخل الشهري للعام الحالي</p>
          </div>
          <div class="chart-legend">
            <span class="legend-item"><span class="dot bg-blue"></span> الإيرادات</span>
            <span class="legend-item"><span class="dot bg-red"></span> المصروفات</span>
            <span class="legend-item"><span class="dot bg-green"></span> صافي الربح</span>
          </div>
        </div>
        <div class="chart-body">
          <Bar :data="financialChartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Occupancy & Distribution Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <div>
            <h3 class="chart-title">توزيع حالة الوحدات العقارية</h3>
            <p class="chart-subtitle">نسبة الإشغال مقابل الشواغر والصيانة</p>
          </div>
        </div>
        <div class="chart-body pie-wrapper">
          <Doughnut :data="occupancyChartData" :options="doughnutOptions" />
        </div>
      </div>
    </div>

    <!-- Management Widgets Section -->
    <div class="widgets-grid">
      <!-- Contracts Widget -->
      <div class="widget-card">
        <div class="widget-header">
          <div class="widget-title-group">
            <i class="pi pi-file text-blue"></i>
            <h4>تنبيهات العقود</h4>
          </div>
          <router-link to="/contracts" class="widget-link">عرض الكل</router-link>
        </div>
        <div class="widget-list">
          <div class="widget-item" v-for="cnt in contractAlerts" :key="cnt.id">
            <div class="item-info">
              <span class="item-main">{{ cnt.tenant }}</span>
              <span class="item-sub">وحدة: {{ cnt.unit }} | ينتهي: {{ cnt.expiry }}</span>
            </div>
            <span class="status-badge" :class="cnt.badgeClass">{{ cnt.status }}</span>
          </div>
          <div v-if="!contractAlerts.length" class="empty-widget">
            <i class="pi pi-check-circle text-green"></i>
            <span>جميع العقود بحالة سارية ومستقرة</span>
          </div>
        </div>
      </div>

      <!-- Recent Payments & Overdues Widget -->
      <div class="widget-card">
        <div class="widget-header">
          <div class="widget-title-group">
            <i class="pi pi-wallet text-green"></i>
            <h4>آخر الدفعات والمتأخرات</h4>
          </div>
          <router-link to="/payments" class="widget-link">عرض الكل</router-link>
        </div>
        <div class="widget-list">
          <div class="widget-item" v-for="pay in recentPayments" :key="pay.id">
            <div class="item-info">
              <span class="item-main">{{ pay.tenant }}</span>
              <span class="item-sub">إيصال #{{ pay.receipt_number }} | {{ pay.payment_date }}</span>
            </div>
            <span class="item-amount text-green">+ {{ format(pay.amount) }}</span>
          </div>
          <div v-if="!recentPayments.length" class="empty-widget">
            <i class="pi pi-inbox"></i>
            <span>لا توجد مدفوعات مسجلة مؤخرًا</span>
          </div>
        </div>
      </div>

      <!-- Maintenance Orders Widget -->
      <div class="widget-card">
        <div class="widget-header">
          <div class="widget-title-group">
            <i class="pi pi-wrench text-amber"></i>
            <h4>طلبات الصيانة التشغيلية</h4>
          </div>
          <router-link to="/maintenance" class="widget-link">عرض الكل</router-link>
        </div>
        <div class="widget-list">
          <div class="widget-item" v-for="maint in maintenanceOrders" :key="maint.id">
            <div class="item-info">
              <span class="item-main">{{ maint.title }}</span>
              <span class="item-sub">{{ maint.location }} | التكلفة: {{ format(maint.cost) }}</span>
            </div>
            <span class="status-badge" :class="maint.badgeClass">{{ maint.status }}</span>
          </div>
          <div v-if="!maintenanceOrders.length" class="empty-widget">
            <i class="pi pi-check-circle text-green"></i>
            <span>لا توجد طلبات صيانة مفتوحة</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { formatCurrency } from '@/utils/currency'
import { useAppStore } from '@/stores/app'

// Chart.js Registration
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const appStore = useAppStore()

function format(amount) {
  return formatCurrency(amount || 0, appStore.selectedCurrency)
}

const dashboardData = ref({
  total_locations: 0,
  total_buildings: 0,
  total_units: 0,
  occupied_units: 0,
  vacant_units: 0,
  maintenance_units: 0,
  occupancy_rate: 0,
  monthly_income: 0,
  monthly_expenses: 0,
  net_profit: 0,
  recent_payments: [],
  overdue_invoices: [],
  contract_alerts: [],
  maintenance_orders: []
})

const kpiCards = computed(() => {
  const d = dashboardData.value
  const occRate = d.occupancy_rate || (d.total_units ? Math.round((d.occupied_units / d.total_units) * 100) : 0)
  const vacant = d.vacant_units || (d.total_units - d.occupied_units)
  const net = d.net_profit || (d.monthly_income - d.monthly_expenses)

  return [
    {
      label: 'إجمالي المواقع العقارية',
      value: d.total_locations || 1,
      icon: 'pi pi-map-marker',
      iconBg: '#EFF6FF',
      iconColor: '#2563EB',
      subtext: 'مجمعات وأراضٍ مسجلة'
    },
    {
      label: 'عدد المباني والأبراج',
      value: d.total_buildings || 1,
      icon: 'pi pi-building',
      iconBg: '#FEF3C7',
      iconColor: '#D97706',
      subtext: 'تحت الإدارة الفعلية'
    },
    {
      label: 'إجمالي الوحدات السكنية/التجارية',
      value: d.total_units || 0,
      icon: 'pi pi-th-large',
      iconBg: '#F3E8FF',
      iconColor: '#9333EA',
      subtext: 'شقق ومحلات ومخازن'
    },
    {
      label: 'الوحدات المؤجرة فعلياً',
      value: d.occupied_units || 0,
      icon: 'pi pi-check-circle',
      iconBg: '#ECFDF5',
      iconColor: '#059669',
      badge: 'نشط',
      badgeClass: 'status-available',
      badgeIcon: 'pi pi-check'
    },
    {
      label: 'الوحدات الشاغرة (المتاحة)',
      value: vacant,
      icon: 'pi pi-info-circle',
      iconBg: '#FEF2F2',
      iconColor: '#DC2626',
      badge: vacant > 0 ? 'متاح للايجار' : 'كامل',
      badgeClass: vacant > 0 ? 'status-expired' : 'status-available'
    },
    {
      label: 'نسبة الإشغال الإجمالية',
      value: `${occRate}%`,
      icon: 'pi pi-chart-line',
      iconBg: '#E0F2FE',
      iconColor: '#0284C7',
      badge: '+2.4% عن الشهر السابق',
      badgeClass: 'status-occupied',
      badgeIcon: 'pi pi-arrow-up'
    },
    {
      label: 'دخل الشهر الفعلي (الإيرادات)',
      value: format(d.monthly_income),
      icon: 'pi pi-wallet',
      iconBg: '#ECFDF5',
      iconColor: '#10B981',
      subtext: 'تحصيلات إيجارات ومرافق'
    },
    {
      label: 'المصاريف التشغيلية والصيانة',
      value: format(d.monthly_expenses),
      icon: 'pi pi-minus-circle',
      iconBg: '#FFFBEB',
      iconColor: '#F59E0B',
      subtext: 'مصروفات تشغيلية وخدمية'
    },
    {
      label: 'صافي الربح المتوقع',
      value: format(net),
      icon: 'pi pi-dollar',
      iconBg: net >= 0 ? '#ECFDF5' : '#FEF2F2',
      iconColor: net >= 0 ? '#059669' : '#DC2626',
      badge: net >= 0 ? 'ربح ممتاز' : 'عجز',
      badgeClass: net >= 0 ? 'status-available' : 'status-expired'
    }
  ]
})

// Charts Data Configuration
const financialChartData = computed(() => {
  return {
    labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو'],
    datasets: [
      {
        label: 'الإيرادات',
        backgroundColor: '#3B82F6',
        borderRadius: 6,
        data: [12000, 15000, 14000, 18000, 17500, 21000, dashboardData.value.monthly_income || 22000]
      },
      {
        label: 'المصروفات',
        backgroundColor: '#EF4444',
        borderRadius: 6,
        data: [3000, 4200, 3500, 5000, 4800, 3900, dashboardData.value.monthly_expenses || 4500]
      },
      {
        label: 'صافي الربح',
        backgroundColor: '#10B981',
        borderRadius: 6,
        data: [9000, 10800, 10500, 13000, 12700, 17100, (dashboardData.value.monthly_income - dashboardData.value.monthly_expenses) || 17500]
      }
    ]
  }
})

const occupancyChartData = computed(() => {
  const occ = dashboardData.value.occupied_units || 14
  const vac = dashboardData.value.vacant_units || 4
  const maint = dashboardData.value.maintenance_units || 2

  return {
    labels: ['مؤجرة', 'شاغرة', 'تحت الصيانة'],
    datasets: [
      {
        backgroundColor: ['#10B981', '#EF4444', '#F59E0B'],
        borderWidth: 0,
        data: [occ, vac, maint]
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: { grid: { display: false } },
    y: { grid: { color: '#F1F5F9' } }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { font: { family: 'Readex Pro' } } }
  }
}

// Dummy / Dynamic Data Widgets
const contractAlerts = computed(() => {
  if (dashboardData.value.contract_alerts?.length) return dashboardData.value.contract_alerts
  return [
    { id: 1, tenant: 'شركة الأمل للتجارة', unit: 'محل 102', expiry: '2026-08-05', status: 'ينتهي قريبًا', badgeClass: 'status-maintenance' },
    { id: 2, tenant: 'خالد إبراهيم العلي', unit: 'شقة 401', expiry: '2026-08-12', status: 'ينتهي قريبًا', badgeClass: 'status-maintenance' },
    { id: 3, tenant: 'سامي يوسف ريان', unit: 'مخزن B2', expiry: '2026-07-20', status: 'منتهي', badgeClass: 'status-expired' }
  ]
})

const recentPayments = computed(() => {
  return dashboardData.value.recent_payments || []
})

const maintenanceOrders = computed(() => {
  if (dashboardData.value.maintenance_orders?.length) return dashboardData.value.maintenance_orders
  return [
    { id: 1, title: 'إصلاح تسريب مياه - شقة 203', location: 'برج الأمل', cost: 350, status: 'قيد التنفيذ', badgeClass: 'status-occupied' },
    { id: 2, title: 'صيانة مصعد البناية الرئيسية', location: 'مجمع الصفوة', cost: 1200, status: 'مفتوح', badgeClass: 'status-maintenance' }
  ]
})

onMounted(async () => {
  try {
    const { data } = await api.get('/reports/dashboard')
    if (data && data.data) {
      Object.assign(dashboardData.value, data.data)
    }
  } catch (err) {
    console.warn('Dashboard api fallback:', err)
  }
})
</script>

<style scoped>
/* Quick Actions Section */
.quick-actions-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px 24px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.quick-actions-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.header-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}
.action-icon {
  color: var(--secondary);
  font-size: 1.2rem;
}
.header-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
}

.quick-actions-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 12px;
}
.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 10px;
  background: #F8FAFC;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
}
.action-btn i {
  font-size: 1.3rem;
}
.action-btn:hover {
  background: #FFFFFF;
  border-color: var(--accent);
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}
.action-btn.accent-action {
  background: var(--accent-light);
  border-color: #BFDBFE;
  color: var(--accent);
}
.action-btn.accent-action:hover {
  background: var(--accent);
  color: #FFFFFF;
}

/* Colors Utilities */
.text-blue { color: #2563EB; }
.text-amber { color: #D97706; }
.text-emerald { color: #059669; }
.text-purple { color: #9333EA; }
.text-green { color: #10B981; }
.text-red { color: #EF4444; }
.text-orange { color: #F97316; }

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 16px;
}
.kpi-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
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
.kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}
.kpi-content {
  display: flex;
  flex-direction: column;
}
.kpi-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.kpi-label {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 2px;
}
.kpi-footer {
  padding-top: 8px;
  border-top: 1px solid var(--border-light);
  font-size: 12px;
  color: var(--text-muted);
}

/* Charts Grid */
.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}
.chart-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 22px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.chart-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.chart-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}
.chart-subtitle {
  font-size: 12.5px;
  color: var(--text-secondary);
}
.chart-legend {
  display: flex;
  gap: 12px;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--text-secondary);
}
.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}
.bg-blue { background: #3B82F6; }
.bg-red { background: #EF4444; }
.bg-green { background: #10B981; }

.chart-body {
  height: 280px;
  position: relative;
}
.pie-wrapper {
  height: 240px;
}

/* Widgets Grid */
.widgets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 20px;
}
.widget-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border-light);
}
.widget-title-group {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
}
.widget-link {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--accent);
}
.widget-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.widget-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: #F8FAFC;
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
}
.item-info {
  display: flex;
  flex-direction: column;
}
.item-main {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-primary);
}
.item-sub {
  font-size: 11.5px;
  color: var(--text-secondary);
}
.item-amount {
  font-size: 13.5px;
  font-weight: 700;
}
.empty-widget {
  text-align: center;
  padding: 24px;
  font-size: 13px;
  color: var(--text-secondary);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

@media (max-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
