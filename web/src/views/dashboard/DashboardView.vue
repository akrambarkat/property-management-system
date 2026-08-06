<template>
  <div class="executive-dashboard page-view">
    <!-- Executive Header & Business Insights Alert Bar -->
    <div class="executive-hero-banner">
      <div class="hero-text-meta">
        <h2 class="hero-title">مركز التوجيه والتحكم القيادي (Executive Command Center)</h2>
        <p class="hero-subtitle">إدارة شمولية لـ {{ dashboardData.total_units }} وحدة عقارية، {{ dashboardData.total_buildings }} مبنى، عقود التشغيل والصيانة، والتدفقات النقدية اللحظية</p>
      </div>

      <!-- Smart Recommendations Carousel / Cards -->
      <div class="smart-insights-pills">
        <div class="insight-pill" v-for="(insight, i) in insights" :key="i" :class="insight.type">
          <i :class="insight.icon"></i>
          <span>{{ insight.text }}</span>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metrics Grid (10 Core Metrics) -->
    <div class="kpi-grid">
      <div class="kpi-card" v-for="kpi in executiveKpis" :key="kpi.label">
        <div class="kpi-top">
          <div class="kpi-icon" :style="{ background: kpi.iconBg, color: kpi.iconColor }">
            <i :class="kpi.icon"></i>
          </div>
          <span class="kpi-trend" :class="kpi.trendClass">
            <i :class="kpi.trendIcon"></i>
            {{ kpi.trend }}
          </span>
        </div>
        <div class="kpi-content">
          <span class="kpi-value">{{ kpi.value }}</span>
          <span class="kpi-label">{{ kpi.label }}</span>
        </div>
        <div class="kpi-footer">
          <span>{{ kpi.subtext }}</span>
        </div>
      </div>
    </div>

    <!-- Main Analytics & Cash Flow Grid -->
    <div class="analytics-grid">
      <!-- Revenue vs Expenses Trend (Cash Flow) -->
      <div class="chart-card main-chart">
        <div class="chart-header">
          <div>
            <h3 class="chart-title">التدفق النقدي والأداء المالي السنوي</h3>
            <p class="chart-subtitle">مقارنة الإيرادات المحصلة بالمصروفات التشغيلية وصافي الأرباح</p>
          </div>
          <div class="time-horizon-selector">
            <button class="horizon-btn active">شهري</button>
            <button class="horizon-btn">ربع سنوي</button>
            <button class="horizon-btn">سنوي</button>
          </div>
        </div>
        <div class="chart-body">
          <Bar :data="cashFlowChartData" :options="barChartOptions" />
        </div>
      </div>

      <!-- Occupancy & Property Health Doughnut -->
      <div class="chart-card donut-chart">
        <div class="chart-header">
          <div>
            <h3 class="chart-title">صحة الأصول ومعدل الإشغال</h3>
            <p class="chart-subtitle">حالة الـ {{ dashboardData.total_units }} وحدة عقارية</p>
          </div>
        </div>
        <div class="chart-body pie-wrapper">
          <Doughnut :data="occupancyData" :options="doughnutOptions" />
        </div>
        <div class="occupancy-stats-footer">
          <div class="occ-stat"><span class="dot bg-emerald"></span> {{ dashboardData.occupied_units }} مؤجرة ({{ dashboardData.occupancy_rate }}%)</div>
          <div class="occ-stat"><span class="dot bg-amber"></span> {{ dashboardData.vacant_units }} شاغرة ({{ vacantPercent }}%)</div>
          <div class="occ-stat"><span class="dot bg-rose"></span> {{ dashboardData.maintenance_units }} صيانة ({{ maintenancePercent }}%)</div>
        </div>
      </div>
    </div>

    <!-- Operational Command & Action Center -->
    <div class="command-grid">
      <!-- Urgent Actions & Overdues Timeline -->
      <div class="command-card">
        <div class="card-header">
          <div class="title-with-icon">
            <i class="pi pi-clock text-rose"></i>
            <h3>المتأخرات والتحصيلات المستحقة</h3>
          </div>
          <router-link to="/invoices" class="card-action">جدول التحصيل</router-link>
        </div>
        <div class="timeline-list">
          <div class="timeline-item" v-for="item in latePayments" :key="item.id">
            <div class="item-status-icon danger">
              <i class="pi pi-exclamation-circle"></i>
            </div>
            <div class="item-details">
              <span class="item-title">{{ item.tenant }}</span>
              <span class="item-sub">وحدة #{{ item.unit }} | متأخر منذ {{ item.daysLate }} يوم</span>
            </div>
            <div class="item-value-action">
              <span class="item-amount text-rose">{{ format(item.amount) }}</span>
              <button class="btn-xs-primary" @click="sendReminder(item)">تذكير</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Maintenance Requests & Field Operations -->
      <div class="command-card">
        <div class="card-header">
          <div class="title-with-icon">
            <i class="pi pi-wrench text-amber"></i>
            <h3>بلاغات الصيانة والميدان</h3>
          </div>
          <router-link to="/maintenance" class="card-action">إدارة الصيانة</router-link>
        </div>
        <div class="timeline-list">
          <div class="timeline-item" v-for="req in maintenanceRequestsList" :key="req.id">
            <div class="item-status-icon warning">
              <i class="pi pi-cog"></i>
            </div>
            <div class="item-details">
              <span class="item-title">{{ req.title }}</span>
              <span class="item-sub">{{ req.building }} | الفني: {{ req.technician }}</span>
            </div>
            <span class="priority-badge" :class="req.priorityClass">{{ req.priority }}</span>
          </div>
        </div>
      </div>

      <!-- Contracts Expiry & Renewal Pipeline -->
      <div class="command-card">
        <div class="card-header">
          <div class="title-with-icon">
            <i class="pi pi-file text-indigo"></i>
            <h3>تجديدات العقود القادمة</h3>
          </div>
          <router-link to="/contracts" class="card-action">سجل العقود</router-link>
        </div>
        <div class="timeline-list">
          <div class="timeline-item" v-for="cnt in upcomingRenewals" :key="cnt.id">
            <div class="item-status-icon info">
              <i class="pi pi-calendar"></i>
            </div>
            <div class="item-details">
              <span class="item-title">{{ cnt.tenant }}</span>
              <span class="item-sub">عقد #{{ cnt.contractNumber }} | ينتهي: {{ cnt.expiryDate }}</span>
            </div>
            <button class="btn-xs-secondary" @click="renewContract(cnt)">تجديد العقد</button>
          </div>
        </div>
      </div>
    </div>

    <!-- SMS Communication & Campaigns Widget -->
    <div class="command-card sms-widget">
      <div class="card-header">
        <div class="title-with-icon">
          <i class="pi pi-send text-sky"></i>
          <h3>الرسائل النصية SMS</h3>
        </div>
        <router-link to="/sms" class="card-action">مركز الرسائل</router-link>
      </div>
      <div class="sms-widget-grid">
        <div class="sms-widget-stat" v-for="stat in smsWidgetStats" :key="stat.label">
          <span class="sms-widget-value" :class="stat.class">{{ stat.value }}</span>
          <span class="sms-widget-label">{{ stat.label }}</span>
        </div>
      </div>
      <div class="sms-widget-actions">
        <router-link to="/sms/bulk" class="btn-xs-primary"><i class="pi pi-megaphone"></i> إرسال جماعي</router-link>
        <router-link to="/sms/templates" class="btn-xs-secondary"><i class="pi pi-file-edit"></i> القوالب</router-link>
        <router-link to="/sms/logs" class="btn-xs-secondary"><i class="pi pi-list"></i> السجل</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { formatCurrency } from '@/utils/currency'
import { useAppStore } from '@/stores/app'

import {
  Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement
} from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const appStore = useAppStore()

function format(val) {
  return formatCurrency(val || 0, appStore.selectedCurrency)
}

const dashboardData = ref({
  total_units: 0,
  total_buildings: 0,
  occupied_units: 0,
  vacant_units: 0,
  maintenance_units: 0,
  monthly_income: 0,
  monthly_expenses: 0,
  collection_rate: 0,
  outstanding_amount: 0,
  occupancy_rate: 0,
  net_profit: 0,
  late_payments: [],
  maintenance_requests: [],
  upcoming_renewals: [],
  monthly_cash_flow: [],
  open_maintenance_count: 0,
  urgent_maintenance_count: 0
})

const loading = ref(true)

const executiveKpis = computed(() => {
  const d = dashboardData.value
  const netProfit = d.monthly_income - d.monthly_expenses
  const profitMargin = d.monthly_income > 0 ? Math.round((netProfit / d.monthly_income) * 100) : 0

  return [
    { label: 'إجمالي الوحدات العقارية', value: `${d.total_units} وحدة`, icon: 'pi pi-building', iconBg: 'var(--info-bg)', iconColor: 'var(--info)', trend: `${d.total_buildings} مبنى`, trendClass: 'text-success', trendIcon: 'pi pi-check', subtext: `${d.total_buildings} مبنى ومجمع سكني` },
    { label: 'نسبة الإشغال الكلية', value: `${d.occupancy_rate}%`, icon: 'pi pi-chart-line', iconBg: 'var(--success-bg)', iconColor: 'var(--success)', trend: d.occupancy_rate > 0 ? `نشط` : '-', trendClass: 'text-success', trendIcon: 'pi pi-arrow-up', subtext: `${d.occupied_units} وحدة مؤجرة` },
    { label: 'الإيراد الشهري الإجمالي', value: format(d.monthly_income), icon: 'pi pi-wallet', iconBg: 'var(--success-bg)', iconColor: 'var(--success)', trend: d.monthly_income > 0 ? 'محصل' : '-', trendClass: 'text-success', trendIcon: 'pi pi-check', subtext: 'تحصيلات الدفعات الإيجارية' },
    { label: 'المصروفات التشغيلية', value: format(d.monthly_expenses), icon: 'pi pi-minus-circle', iconBg: 'var(--warning-bg)', iconColor: 'var(--warning)', trend: 'شهري', trendClass: 'text-muted', trendIcon: 'pi pi-minus', subtext: 'صيانة وفواتير ومرافق' },
    { label: 'صافي الأرباح التشغيلية', value: format(netProfit), icon: 'pi pi-dollar', iconBg: 'var(--primary-50)', iconColor: 'var(--accent)', trend: d.monthly_income > 0 ? `${profitMargin}%` : '-', trendClass: profitMargin >= 50 ? 'text-success' : 'text-warning', trendIcon: profitMargin >= 50 ? 'pi pi-arrow-up' : 'pi pi-minus', subtext: `هامش ربح ${profitMargin}%` },
    { label: 'معدل التحصيل الفعلي', value: `${d.collection_rate}%`, icon: 'pi pi-percentage', iconBg: 'var(--bg-subtle)', iconColor: 'var(--text-secondary)', trend: d.collection_rate >= 90 ? 'ممتاز' : d.collection_rate >= 70 ? 'جيد' : 'بحاجة تحسين', trendClass: d.collection_rate >= 90 ? 'text-success' : d.collection_rate >= 70 ? 'text-warning' : 'text-danger', trendIcon: 'pi pi-minus', subtext: 'من إجمالي الفواتير' },
    { label: 'المبالغ غير المحصلة', value: format(d.outstanding_amount), icon: 'pi pi-exclamation-circle', iconBg: 'var(--danger-bg)', iconColor: 'var(--danger)', trend: `${d.late_payments.length} فواتير`, trendClass: d.late_payments.length > 0 ? 'text-danger' : 'text-success', trendIcon: d.late_payments.length > 0 ? 'pi pi-exclamation-triangle' : 'pi pi-check', subtext: 'بحاجة لمتابعة' },
    { label: 'طلبات الصيانة المفتوحة', value: `${d.open_maintenance_count} طلبات`, icon: 'pi pi-wrench', iconBg: 'var(--warning-bg)', iconColor: 'var(--warning)', trend: d.urgent_maintenance_count > 0 ? `${d.urgent_maintenance_count} طارئة` : 'لا يوجد', trendClass: d.urgent_maintenance_count > 0 ? 'text-danger' : 'text-success', trendIcon: d.urgent_maintenance_count > 0 ? 'pi pi-clock' : 'pi pi-check', subtext: 'قيد التنفيذ' }
  ]
})

const cashFlowChartData = computed(() => {
  const flow = dashboardData.value.monthly_cash_flow
  if (!flow || flow.length === 0) {
    return { labels: [], datasets: [] }
  }
  return {
    labels: flow.map(m => m.label),
    datasets: [
      { label: 'الإيرادات', backgroundColor: '#4F46E5', borderRadius: 6, data: flow.map(m => m.income) },
      { label: 'المصروفات', backgroundColor: '#EF4444', borderRadius: 6, data: flow.map(m => m.expenses) },
      { label: 'صافي الربح', backgroundColor: '#10B981', borderRadius: 6, data: flow.map(m => m.net_profit) }
    ]
  }
})

const occupancyData = computed(() => {
  const d = dashboardData.value
  return {
    labels: ['مؤجرة', 'شاغرة', 'تحت الصيانة'],
    datasets: [{
      backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
      data: [d.occupied_units, d.vacant_units, d.maintenance_units],
      borderWidth: 0
    }]
  }
})

const vacantPercent = computed(() => {
  const d = dashboardData.value
  return d.total_units > 0 ? Math.round((d.vacant_units / d.total_units) * 100) : 0
})

const maintenancePercent = computed(() => {
  const d = dashboardData.value
  return d.total_units > 0 ? Math.round((d.maintenance_units / d.total_units) * 100) : 0
})

const insights = computed(() => {
  const d = dashboardData.value
  const result = []
  if (d.upcoming_renewals && d.upcoming_renewals.length > 0) {
    const totalRent = d.upcoming_renewals.reduce((sum, r) => sum + (r.rent_amount || 0), 0)
    result.push({
      type: 'warning',
      icon: 'pi pi-exclamation-triangle',
      text: `تحذير سيولة: ${d.upcoming_renewals.length} عقد ${totalRent > 0 ? `بقيمة ${format(totalRent)}` : ''} تنتهي خلال 30 يوماً بحاجة للتجديد`
    })
  }
  if (d.occupancy_rate > 85) {
    result.push({
      type: 'info',
      icon: 'pi pi-chart-line',
      text: `توصية: معدل الإشغال وصل ${d.occupancy_rate}%، يُوصى بزيادة الإيجار للوحدات الشاغرة`
    })
  } else if (d.vacant_units > 0) {
    result.push({
      type: 'info',
      icon: 'pi pi-info-circle',
      text: `هنالك ${d.vacant_units} وحدة شاغرة، يُوصى بتفعيل حملات تسويقية`
    })
  }
  if (d.late_payments && d.late_payments.length > 0) {
    result.push({
      type: 'warning',
      icon: 'pi pi-clock',
      text: `لديك ${d.late_payments.length} فاتورة متأخرة بقيمة ${format(d.outstanding_amount)}`
    })
  }
  return result
})

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'top' } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: 'var(--border)' } } }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } }
}

const latePayments = computed(() => dashboardData.value.late_payments || [])
const maintenanceRequestsList = computed(() => dashboardData.value.maintenance_requests || [])
const upcomingRenewals = computed(() => dashboardData.value.upcoming_renewals || [])

const smsWidgetStats = ref([
  { label: 'رسائل الشهر', value: '—', class: '' },
  { label: 'معدل التسليم', value: '—', class: '' },
  { label: 'فاشلة', value: '—', class: 'text-rose' },
  { label: 'بانتظار الإرسال', value: '—', class: 'text-amber' }
])

function sendReminder(item) {
  alert(`تم إرسال تذكير الدفع للمستأجر: ${item.tenant}`)
}

function renewContract(cnt) {
  alert(`جاري البدء في إجراءات تجديد العقد #${cnt.contractNumber}`)
}

onMounted(async () => {
  try {
    const { data } = await api.get('/reports/dashboard')
    if (data?.data) Object.assign(dashboardData.value, data.data)
  } catch {
    // سيتم عرض بيانات فارغة في حال عدم وجود اتصال
  } finally {
    loading.value = false
  }

  try {
    const { data } = await api.get('/sms/statistics/overview')
    const ov = data?.data?.month || {}
    smsWidgetStats.value = [
      { label: 'رسائل الشهر', value: ov.total ?? '—', class: '' },
      { label: 'معدل التسليم', value: ov.delivery_rate != null ? ov.delivery_rate + '٪' : '—', class: '' },
      { label: 'فاشلة', value: ov.failed ?? '—', class: 'text-rose' },
      { label: 'بانتظار الإرسال', value: ov.pending ?? '—', class: 'text-amber' }
    ]
  } catch {
    // بيانات الرسائل غير متاحة
  }
})
</script>

<style scoped>
.executive-dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.executive-hero-banner {
  background: linear-gradient(135deg, var(--bg-active) 0%, var(--bg-surface) 100%);
  color: var(--text-primary);
  padding: 24px 28px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  box-shadow: var(--shadow-md);
}
.hero-title {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--text-primary);
}
.hero-subtitle {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 4px;
}

.smart-insights-pills {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-width: 480px;
}
.insight-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 500;
}
.insight-pill.warning {
  background: var(--warning-bg);
  color: var(--warning-contrast);
  border: 1px solid var(--warning-border);
}
.insight-pill.info {
  background: var(--info-bg);
  color: var(--info-contrast);
  border: 1px solid var(--info-border);
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}
.kpi-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
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
.kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}
.kpi-trend {
  font-size: 11.5px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
}
.text-success { color: var(--success); }
.text-warning { color: var(--warning); }
.text-danger { color: var(--danger); }
.text-muted { color: var(--text-muted); }

.kpi-value {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-primary);
}
.kpi-label {
  font-size: 12.5px;
  color: var(--text-secondary);
}
.kpi-footer {
  font-size: 11.5px;
  color: var(--text-muted);
  border-top: 1px solid var(--border-light);
  padding-top: 6px;
}

.analytics-grid {
  display: grid;
  grid-template-columns: 2.2fr 1fr;
  gap: 20px;
}
.chart-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.chart-title {
  font-size: 15px;
  font-weight: 700;
}
.chart-subtitle {
  font-size: 12px;
  color: var(--text-secondary);
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
  padding: 4px 10px;
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

.chart-body {
  height: 280px;
  position: relative;
}
.pie-wrapper {
  height: 200px;
}

.occupancy-stats-footer {
  display: flex;
  justify-content: space-around;
  font-size: 12px;
  font-weight: 600;
  border-top: 1px solid var(--border-light);
  padding-top: 12px;
}
.occ-stat {
  display: flex;
  align-items: center;
  gap: 6px;
}
.dot { width: 8px; height: 8px; border-radius: 50%; }
.bg-emerald { background: #10B981; }
.bg-amber { background: #F59E0B; }
.bg-rose { background: #EF4444; }

.command-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 20px;
}
.command-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border-light);
  padding-bottom: 10px;
}
.title-with-icon {
  display: flex;
  align-items: center;
  gap: 8px;
}
.title-with-icon h3 {
  font-size: 14px;
  font-weight: 700;
}
.card-action {
  font-size: 12px;
  color: var(--accent-hover);
  text-decoration: none;
  font-weight: 600;
}

.timeline-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.timeline-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: var(--bg-subtle);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
}
.item-status-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}
.item-status-icon.danger { background: var(--danger-bg); color: var(--danger-contrast); }
.item-status-icon.warning { background: var(--warning-bg); color: var(--warning-contrast); }
.item-status-icon.info { background: var(--info-bg); color: var(--info-contrast); }

.item-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.item-title {
  font-size: 13px;
  font-weight: 600;
}
.item-sub {
  font-size: 11.5px;
  color: var(--text-secondary);
}
.item-value-action {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}
.item-amount {
  font-size: 13px;
  font-weight: 700;
}

.btn-xs-primary {
  background: var(--accent);
  color: var(--text-on-accent);
  border: none;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
}
.btn-xs-secondary {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
}

.priority-badge {
  padding: 3px 8px;
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 600;
}
.p-danger { background: var(--danger-bg); color: var(--danger-contrast); }
.p-warning { background: var(--warning-bg); color: var(--warning-contrast); }

.sms-widget {
  grid-column: 1 / -1;
}
.sms-widget-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 14px;
  padding: 14px 4px;
}
.sms-widget-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px;
  background: var(--bg-subtle);
  border-radius: var(--radius-sm);
}
.sms-widget-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text-primary);
}
.sms-widget-value.text-rose { color: var(--danger-contrast); }
.sms-widget-value.text-amber { color: var(--warning-contrast); }
.sms-widget-label {
  font-size: 11.5px;
  color: var(--text-secondary);
}
.sms-widget-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  border-top: 1px solid var(--border);
  padding-top: 12px;
}
.text-sky { color: #0EA5E9; }

@media (max-width: 1024px) {
  .analytics-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .executive-hero-banner {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
    padding: 18px 16px;
  }
  .smart-insights-pills {
    max-width: none;
  }
  .command-grid {
    grid-template-columns: repeat(auto-fit, minmax(min(320px, 100%), 1fr));
    gap: 16px;
  }
  .analytics-grid {
    gap: 16px;
  }
  .chart-card {
    padding: 16px;
  }
  .chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  .chart-body {
    height: 240px;
  }
  .occupancy-stats-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .btn-xs-primary,
  .btn-xs-secondary {
    min-height: 40px;
    padding: 6px 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .horizon-btn {
    padding: 8px 12px;
  }
}

@media (max-width: 480px) {
  .timeline-item {
    flex-wrap: wrap;
  }
  .item-value-action {
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
  .sms-widget-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .sms-widget-actions a {
    flex: 1;
  }
}
</style>
