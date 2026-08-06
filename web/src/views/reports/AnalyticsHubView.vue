<template>
  <div class="page-view profile-dashboard">
    <div class="profile-header">
      <div class="header-left">
        <div class="profile-title-block">
          <div class="flex-align gap-3">
            <div class="unit-icon-box bg-success-light text-success">
              <i class="pi pi-chart-bar"></i>
            </div>
            <div>
              <h2 class="profile-title">مركز التحليلات المالية والأداء</h2>
              <span class="profile-subtitle">
                نظرة شمولية لأداء المحفظة العقارية والتدفقات النقدية
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <div class="time-horizon-selector">
          <button class="horizon-btn">أسبوعي</button>
          <button class="horizon-btn active">شهري</button>
          <button class="horizon-btn">سنوي</button>
        </div>
        <button class="btn-primary ml-2">
          <i class="pi pi-download"></i> تصدير التقرير
        </button>
      </div>
    </div>

    <!-- Top KPI Row -->
    <div class="profile-kpi-grid">
      <!-- Collection Rate -->
      <div class="kpi-card highlight-card">
        <div class="kpi-top">
          <span class="kpi-label">معدل التحصيل الفعلي</span>
          <i class="pi pi-percentage text-accent"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-accent">92.4%</span>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar bg-accent" style="width: 92.4%"></div>
        </div>
        <span class="kpi-footer">الهدف: 95% <i class="pi pi-arrow-up text-success ml-1"></i></span>
      </div>
      
      <!-- Total Profit -->
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">صافي الربح المتراكم (YTD)</span>
          <i class="pi pi-chart-line text-success"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-success">452,000 ₪</span>
        </div>
        <span class="kpi-footer"><i class="pi pi-arrow-up text-success"></i> +12% مقارنة بالعام الماضي</span>
      </div>

      <!-- Expense Analytics -->
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">إجمالي المصروفات (YTD)</span>
          <i class="pi pi-minus-circle text-danger"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-danger">128,500 ₪</span>
        </div>
        <span class="kpi-footer">70% مصاريف تشغيلية، 30% صيانة</span>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="profile-body-grid mt-4">
      <div class="dashboard-widget">
        <div class="widget-header">
          <h3>التحليل المالي (إيرادات مقابل مصروفات)</h3>
        </div>
        <div class="chart-wrapper">
          <Bar :data="financialData" :options="barChartOptions" />
        </div>
      </div>

      <div class="dashboard-widget">
        <div class="widget-header">
          <h3>توزيع المصروفات التشغيلية</h3>
        </div>
        <div class="chart-wrapper pie-wrapper">
          <Doughnut :data="expenseData" :options="doughnutOptions" />
        </div>
        <div class="occupancy-stats-footer mt-4">
          <div class="occ-stat"><span class="dot" style="background:var(--danger)"></span> صيانة عامة (45%)</div>
          <div class="occ-stat"><span class="dot" style="background:var(--warning)"></span> فواتير ومرافق (35%)</div>
          <div class="occ-stat"><span class="dot" style="background:var(--info)"></span> مصاريف إدارية (20%)</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const financialData = computed(() => ({
  labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس'],
  datasets: [
    { label: 'الإيرادات', backgroundColor: '#10B981', borderRadius: 4, data: [45000, 48000, 47000, 52000, 51000, 55000, 58000, 60000] },
    { label: 'المصروفات', backgroundColor: '#EF4444', borderRadius: 4, data: [12000, 14000, 11000, 18000, 13000, 15000, 14500, 16000] }
  ]
}))

const expenseData = computed(() => ({
  labels: ['صيانة عامة', 'فواتير ومرافق', 'مصاريف إدارية'],
  datasets: [{ backgroundColor: ['#EF4444', '#F59E0B', '#3B82F6'], data: [45, 35, 20], borderWidth: 0 }]
}))

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'top' } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: 'var(--border)' } } }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  cutout: '75%'
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
.bg-success-light { background: var(--success-bg); border: 1px solid var(--success-border); }
.text-success { color: var(--success); }

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
  align-items: center;
  gap: 16px;
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

.time-horizon-selector {
  display: flex;
  gap: 4px;
  background: var(--bg-subtle);
  padding: 3px;
  border-radius: var(--radius-sm);
}
.horizon-btn {
  border: none;
  background: transparent;
  padding: 6px 12px;
  font-size: 12px;
  border-radius: 4px;
  cursor: pointer;
  color: var(--text-secondary);
}
.horizon-btn.active {
  background: var(--bg-surface);
  color: var(--text-primary);
  font-weight: 600;
  box-shadow: var(--shadow-xs);
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
.ml-1 { margin-left: 4px; }
.ml-2 { margin-left: 12px; }

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
.bg-accent { background-color: var(--accent); }

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
.chart-wrapper { height: 280px; width: 100%; }
.pie-wrapper { height: 220px; }

.occupancy-stats-footer {
  display: flex;
  justify-content: space-around;
  font-size: 12px;
  font-weight: 600;
  border-top: 1px solid var(--border-light);
  padding-top: 12px;
}
.occ-stat { display: flex; align-items: center; gap: 6px; color: var(--text-secondary); }
.dot { width: 8px; height: 8px; border-radius: 50%; }
</style>
