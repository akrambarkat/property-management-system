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
              <h2 class="profile-title">شقة 401 - سكني</h2>
              <span class="profile-subtitle">
                <i class="pi pi-building text-muted"></i>
                برج السلام التنموي | الطابق الرابع
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
          <i class="pi pi-check-circle text-success"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-success">مؤجرة</span>
        </div>
        <span class="kpi-footer">تدر عائد استثماري منتظم</span>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">قيمة الإيجار السنوي</span>
          <i class="pi pi-money-bill text-accent"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value">18,500 ₪</span>
        </div>
        <span class="kpi-footer">متوسط سعر السوق: 19,000 ₪</span>
      </div>

      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">تكلفة الصيانة السنوية</span>
          <i class="pi pi-wrench text-danger"></i>
        </div>
        <div class="kpi-content">
          <span class="kpi-value text-danger">1,250 ₪</span>
        </div>
        <span class="kpi-footer">2 طلبات صيانة هذا العام</span>
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
            <Bar :data="maintenanceData" :options="chartOptions" />
          </div>
        </div>

        <div class="dashboard-widget mt-4">
          <div class="widget-header">
            <h3>تاريخ طلبات الصيانة</h3>
          </div>
          <div class="widget-list">
            <div class="widget-list-item hover-lift">
              <div class="item-icon bg-warning-light"><i class="pi pi-exclamation-triangle text-warning"></i></div>
              <div class="item-info">
                <span class="item-title">تسرب مياه في الحمام الرئيسي</span>
                <span class="item-sub">اكتمل | التكلفة: 450 ₪ | قبل شهرين</span>
              </div>
            </div>
            <div class="widget-list-item hover-lift">
              <div class="item-icon bg-info-light"><i class="pi pi-cog text-info"></i></div>
              <div class="item-info">
                <span class="item-title">تغيير فلاتر التكييف</span>
                <span class="item-sub">اكتمل | التكلفة: 150 ₪ | قبل 6 أشهر</span>
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
          <div class="tenant-mini-card">
            <div class="t-avatar"><i class="pi pi-user"></i></div>
            <div class="t-details">
              <h4>محمد إبراهيم الزهراني</h4>
              <span>رقم العقد: CNT-2025-103</span>
            </div>
            <router-link to="/tenants/205" class="btn-xs-primary ml-auto">ملف المستأجر</router-link>
          </div>
        </div>

        <!-- Financial Yield -->
        <div class="dashboard-widget mt-4">
          <div class="widget-header">
            <h3>الأداء المالي (Property Performance)</h3>
          </div>
          <div class="yield-stats">
            <div class="y-row">
              <span class="y-label">الإيرادات المحصلة (سنة)</span>
              <span class="y-val text-success">18,500 ₪</span>
            </div>
            <div class="y-row">
              <span class="y-label">المصروفات والصيانة</span>
              <span class="y-val text-danger">1,250 ₪</span>
            </div>
            <div class="y-divider"></div>
            <div class="y-row">
              <span class="y-label font-bold text-primary">العائد الصافي للوحدة</span>
              <span class="y-val font-bold text-primary">17,250 ₪</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const router = useRouter()

const maintenanceData = computed(() => ({
  labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
  datasets: [
    { label: 'تكلفة الصيانة (₪)', backgroundColor: '#F59E0B', borderRadius: 4, data: [0, 450, 0, 0, 0, 150] }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: '#F1F5F9' } } }
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
.bg-indigo-light { background: #EEF2FF; border: 1px solid #C7D2FE; }
.text-indigo { color: #4F46E5; }

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
.text-accent { color: var(--accent); }
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
  background: #F8FAFC;
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
.bg-warning-light { background: #FEF3C7; }
.text-warning { color: #D97706; }
.bg-info-light { background: #E0F2FE; }
.text-info { color: #0284C7; }

.item-info { flex: 1; display: flex; flex-direction: column; }
.item-title { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
.item-sub { font-size: 11.5px; color: var(--text-secondary); margin-top: 2px; }

/* Tenant Mini Card */
.tenant-mini-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #FFFFFF;
  padding: 12px;
  border-radius: var(--radius-sm);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.t-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #EFF6FF;
  color: #3B82F6;
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
  color: #fff;
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
