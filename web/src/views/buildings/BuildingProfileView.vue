<template>
  <div class="page-view profile-dashboard">
    <!-- Header with Back Button -->
    <div class="profile-header">
      <div class="header-left">
        <button class="btn-icon" @click="router.back()">
          <i class="pi pi-arrow-right"></i>
        </button>
        <div class="profile-title-block">
          <h2 class="profile-title">برج السلام التنموي</h2>
          <span class="profile-subtitle">
            <i class="pi pi-map-marker text-muted"></i>
            منطقة الأعمال المركزية | BLD-{{ buildingId }}
          </span>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-secondary">
          <i class="pi pi-print"></i> طباعة التقرير
        </button>
        <button class="btn-primary">
          <i class="pi pi-pencil"></i> تعديل بيانات المبنى
        </button>
      </div>
    </div>

    <!-- Top KPI Row -->
    <div class="profile-kpi-grid">
      <!-- Health Score -->
      <div class="kpi-card highlight-card health-card">
        <div class="kpi-top">
          <span class="kpi-label">مؤشر صحة المبنى</span>
          <i class="pi pi-heart-fill text-success"></i>
        </div>
        <div class="health-score">
          <span class="score-value">92</span>
          <span class="score-max">/100</span>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar bg-success" style="width: 92%"></div>
        </div>
        <span class="kpi-footer">ممتاز (بناءً على الصيانة والإشغال)</span>
      </div>

      <!-- Occupancy Rate -->
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">معدل الإشغال</span>
          <i class="pi pi-chart-pie text-accent"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value">85%</span>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar bg-accent" style="width: 85%"></div>
        </div>
        <span class="kpi-footer">34 وحدة مؤجرة من أصل 40</span>
      </div>

      <!-- Monthly Revenue -->
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">الإيراد الشهري للمبنى</span>
          <i class="pi pi-wallet text-success"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-success">45,000 ₪</span>
        </div>
        <span class="kpi-footer text-success"><i class="pi pi-arrow-up"></i> +5.2% هذا الشهر</span>
      </div>

      <!-- Operating Profit -->
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">صافي الربح التشغيلي</span>
          <i class="pi pi-chart-line text-indigo"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-indigo">38,500 ₪</span>
        </div>
        <span class="kpi-footer text-muted">هامش ربح 85%</span>
      </div>
    </div>

    <!-- Main Dashboard Body -->
    <div class="profile-body-grid">
      <!-- Left Column: Vacant Units & Active Maintenance -->
      <div class="profile-col-left">
        <!-- Vacant Units Widget -->
        <div class="dashboard-widget">
          <div class="widget-header">
            <h3>الوحدات الشاغرة بحاجة للتسويق</h3>
            <span class="badge badge-warning">6 وحدات</span>
          </div>
          <div class="widget-list">
            <div class="widget-list-item hover-lift" v-for="unit in vacantUnits" :key="unit.id">
              <div class="item-icon bg-warning-light">
                <i class="pi pi-key text-warning"></i>
              </div>
              <div class="item-info">
                <span class="item-title">{{ unit.name }}</span>
                <span class="item-sub">{{ unit.type }} | السعر المقترح: {{ unit.price }} ₪</span>
              </div>
              <button class="btn-xs-primary">طرح للإيجار</button>
            </div>
          </div>
        </div>

        <!-- Property Performance Alert -->
        <div class="dashboard-widget bg-indigo-light">
          <div class="widget-header">
            <h3>تنبيه أداء المبنى</h3>
            <i class="pi pi-bolt text-indigo"></i>
          </div>
          <p class="text-sm text-indigo-dark mt-2 leading-relaxed">
            معدل الصيانة في الطابق الثالث مرتفع جداً مقارنة بالمتوسط. يرجى إرسال فريق لفحص البنية التحتية لتجنب تكاليف متزايدة.
          </p>
        </div>
      </div>

      <!-- Right Column: Revenue Trends & Upcoming Expirations -->
      <div class="profile-col-right">
        <!-- Revenue Trends Chart -->
        <div class="dashboard-widget">
          <div class="widget-header">
            <h3>أداء التدفق النقدي للمبنى (12 شهر)</h3>
          </div>
          <div class="chart-wrapper">
            <Bar :data="revenueData" :options="chartOptions" />
          </div>
        </div>

        <!-- Upcoming Contract Expirations -->
        <div class="dashboard-widget mt-4">
          <div class="widget-header">
            <h3>عقود قاربت على الانتهاء في المبنى</h3>
            <router-link to="/contracts" class="text-xs text-accent font-bold">عرض الكل</router-link>
          </div>
          <div class="widget-list">
            <div class="widget-list-item hover-lift" v-for="cnt in expiringContracts" :key="cnt.id">
              <div class="item-icon bg-danger-light">
                <i class="pi pi-file text-danger"></i>
              </div>
              <div class="item-info">
                <span class="item-title">{{ cnt.tenant }}</span>
                <span class="item-sub">وحدة: {{ cnt.unit }} | ينتهي بعد {{ cnt.daysLeft }} يوم</span>
              </div>
              <button class="btn-xs-secondary">تجديد</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const route = useRoute()
const router = useRouter()
const buildingId = route.params.id || '101'

const vacantUnits = ref([
  { id: 1, name: 'شقة 104', type: 'سكني - غرفتين', price: 1200 },
  { id: 2, name: 'شقة 203', type: 'سكني - 3 غرف', price: 1800 },
  { id: 3, name: 'محل 02', type: 'تجاري', price: 4500 }
])

const expiringContracts = ref([
  { id: 1, tenant: 'شركة الأفق ذ.م.م', unit: 'مكتب 501', daysLeft: 12 },
  { id: 2, tenant: 'عبدالرحمن العتيبي', unit: 'شقة 304', daysLeft: 20 }
])

const revenueData = computed(() => ({
  labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
  datasets: [
    { label: 'الإيرادات المحصلة', backgroundColor: '#10B981', borderRadius: 4, data: [42000, 42000, 45000, 45000, 45000, 45000] },
    { label: 'المصروفات', backgroundColor: '#EF4444', borderRadius: 4, data: [6500, 7200, 6000, 12000, 6500, 6500] }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'top' } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: 'var(--border)' } } }
}
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
.profile-title-block {
  display: flex;
  flex-direction: column;
}
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
    flex-wrap: wrap;
  }
  .header-actions .btn {
    flex: 1;
    justify-content: center;
  }
}

.profile-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
.kpi-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
}
.kpi-content {
  margin: 4px 0;
}
.kpi-value {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-primary);
}
.kpi-footer {
  font-size: 11.5px;
  color: var(--text-muted);
}
.progress-bar-container {
  width: 100%;
  height: 6px;
  background: var(--bg-subtle);
  border-radius: 4px;
  overflow: hidden;
  margin: 4px 0;
}
.progress-bar {
  height: 100%;
  border-radius: 4px;
}
.bg-success { background-color: var(--success); }
.bg-accent { background-color: var(--accent); }
.text-success { color: var(--success); }
.text-accent { color: var(--accent-hover); }
.text-indigo { color: var(--accent-hover); }

.health-score {
  display: flex;
  align-items: baseline;
  gap: 2px;
}
.score-value {
  font-size: 32px;
  font-weight: 900;
  color: var(--success);
}
.score-max {
  font-size: 14px;
  color: var(--text-muted);
}

.profile-body-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 20px;
}

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
.widget-header h3 {
  font-size: 14.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}
.badge-warning { background: var(--warning-bg); color: var(--warning-contrast); }

.widget-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
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
.bg-danger-light { background: var(--danger-bg); }
.text-danger { color: var(--danger-contrast); }

.item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.item-title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
}
.item-sub {
  font-size: 11.5px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.btn-xs-primary {
  background: var(--accent);
  color: var(--text-on-accent);
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11.5px;
  cursor: pointer;
  font-weight: 600;
}
.btn-xs-secondary {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  color: var(--text-primary);
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11.5px;
  cursor: pointer;
  font-weight: 600;
}

.bg-indigo-light { background: var(--accent-light); border-color: var(--info-border); }
.text-indigo { color: var(--accent-hover); }
.text-indigo-dark { color: var(--accent-hover); }
.mt-2 { margin-top: 8px; }
.mt-4 { margin-top: 20px; }
.leading-relaxed { line-height: 1.6; }

.chart-wrapper {
  height: 250px;
  width: 100%;
}

@media (max-width: 1024px) {
  .profile-body-grid {
    grid-template-columns: 1fr;
  }
}
</style>
