<template>
  <div class="sms-overview">
    <!-- Stat cards -->
    <div class="sms-stats-grid">
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-blue-light"><i class="pi pi-send"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.month.total || 0 }}</span>
          <span class="sms-stat-label">رسائل هذا الشهر</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-success-light"><i class="pi pi-check-circle"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.month.sent || 0 }}</span>
          <span class="sms-stat-label">مرسلة هذا الشهر</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-warning-light"><i class="pi pi-percentage"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.month.delivery_rate || 0 }}٪</span>
          <span class="sms-stat-label">معدل التسليم</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-danger-light"><i class="pi pi-exclamation-circle"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.month.failed || 0 }}</span>
          <span class="sms-stat-label">فاشلة</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-neutral-light"><i class="pi pi-clock"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.month.pending || 0 }}</span>
          <span class="sms-stat-label">قيد الانتظار</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-purple-light"><i class="pi pi-server"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.top_provider || '—' }}</span>
          <span class="sms-stat-label">أفضل مزود</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-amber-light"><i class="pi pi-timer"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.month.avg_duration_ms || 0 }}ms</span>
          <span class="sms-stat-label">متوسط زمن التسليم</span>
        </div>
      </div>
      <div class="sms-stat-card">
        <div class="sms-stat-icon bg-blue-light"><i class="pi pi-phone"></i></div>
        <div>
          <span class="sms-stat-value">{{ overview.today.total || 0 }}</span>
          <span class="sms-stat-label">رسائل اليوم</span>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="sms-charts-grid">
      <div class="sms-chart-card">
        <div class="sms-chart-header">
          <h4><i class="pi pi-chart-bar"></i> الرسائل اليومية</h4>
          <span class="sms-chart-sub">آخر 14 يومًا</span>
        </div>
        <div class="sms-chart-body">
          <Bar v-if="dailyChartData" :data="dailyChartData" :options="chartOptions" />
          <div v-else class="sms-chart-empty">لا توجد بيانات كافية</div>
        </div>
      </div>

      <div class="sms-chart-card">
        <div class="sms-chart-header">
          <h4><i class="pi pi-chart-line"></i> الاستخدام الشهري</h4>
          <span class="sms-chart-sub">آخر 6 أشهر</span>
        </div>
        <div class="sms-chart-body">
          <Line v-if="monthlyChartData" :data="monthlyChartData" :options="chartOptions" />
          <div v-else class="sms-chart-empty">لا توجد بيانات كافية</div>
        </div>
      </div>

      <div class="sms-chart-card">
        <div class="sms-chart-header">
          <h4><i class="pi pi-exclamation-triangle"></i> أسباب الفشل</h4>
          <span class="sms-chart-sub">أكثر الأخطاء شيوعًا</span>
        </div>
        <div class="sms-chart-body">
          <div v-if="failureRows.length" class="failure-list">
            <div v-for="(f, i) in failureRows" :key="i" class="failure-row">
              <span class="failure-reason">{{ f.failure_reason }}</span>
              <span class="failure-count">{{ f.count }}</span>
            </div>
          </div>
          <div v-else class="sms-chart-empty">لا توجد إخفاقات مسجلة 🎉</div>
        </div>
      </div>

      <div class="sms-chart-card">
        <div class="sms-chart-header">
          <h4><i class="pi pi-server"></i> مقارنة المزودين</h4>
          <span class="sms-chart-sub">هذا الشهر</span>
        </div>
        <div class="sms-chart-body">
          <div v-if="providerRows.length" class="provider-compare">
            <div v-for="p in providerRows" :key="p.provider_key" class="provider-row-compare">
              <div class="provider-name">
                <i class="pi pi-server"></i>
                <span>{{ providerLabel(p.provider_key) }}</span>
              </div>
              <div class="provider-bar-track">
                <div class="provider-bar" :style="{ width: providerBarWidth(p) }"></div>
              </div>
              <span class="provider-total">{{ p.sent }}</span>
            </div>
          </div>
          <div v-else class="sms-chart-empty">لا توجد بيانات كافية</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'
import {
  Chart as ChartJS, Title, Tooltip, Legend, BarElement, LineElement,
  PointElement, CategoryScale, LinearScale, Filler
} from 'chart.js'
import { Bar, Line } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const toast = useToastStore()
const loading = ref(false)
const overview = ref({
  today: { total: 0, sent: 0, failed: 0 },
  month: { total: 0, sent: 0, failed: 0, pending: 0, delivery_rate: 0, avg_duration_ms: 0 },
  top_provider: null
})
const daily = ref([])
const monthly = ref([])
const failures = ref([])
const providerStats = ref([])

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } } },
  scales: { y: { beginAtZero: true, grid: { color: 'rgba(120,130,150,0.12)' } }, x: { grid: { display: false } } }
}

const dailyChartData = computed(() => {
  if (!daily.value.length) return null
  return {
    labels: daily.value.map(d => d.date),
    datasets: [
      { label: 'مرسلة', data: daily.value.map(d => d.sent), backgroundColor: 'rgba(79,70,229,0.75)', borderRadius: 6 },
      { label: 'فاشلة', data: daily.value.map(d => d.failed), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 6 }
    ]
  }
})

const monthlyChartData = computed(() => {
  if (!monthly.value.length) return null
  return {
    labels: monthly.value.map(d => d.month),
    datasets: [
      { label: 'مرسلة', data: monthly.value.map(d => d.sent), borderColor: '#4F46E5', backgroundColor: 'rgba(79,70,229,0.12)', fill: true, tension: 0.35 }
    ]
  }
})

const failureRows = computed(() => failures.value.slice(0, 6))
const providerRows = computed(() => providerStats.value)

onMounted(fetchAll)

async function fetchAll() {
  loading.value = true
  try {
    const [ov, dl, ml, fl, pr] = await Promise.all([
      api.get('/sms/statistics/overview'),
      api.get('/sms/statistics/daily'),
      api.get('/sms/statistics/monthly'),
      api.get('/sms/statistics/failures'),
      api.get('/sms/statistics/providers')
    ])
    overview.value = ov.data.data
    daily.value = dl.data.data
    monthly.value = ml.data.data
    failures.value = fl.data.data
    providerStats.value = pr.data.data
  } catch (err) {
    toast.error('تعذر تحميل إحصائيات الرسائل')
  } finally {
    loading.value = false
  }
}

function providerLabel(key) {
  return { custom: 'مخصص', twilio: 'Twilio', jawwal: 'Jawwal', vonage: 'Vonage', messagebird: 'MessageBird', ooredoo: 'Ooredoo' }[key] || key
}

function providerBarWidth(p) {
  const max = Math.max(1, ...providerRows.value.map(x => x.sent))
  return Math.max(4, Math.round((p.sent / max) * 100)) + '%'
}
</script>

<style scoped>
.sms-overview { display: flex; flex-direction: column; gap: 20px; }
.sms-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
.sms-stat-card {
  display: flex; align-items: center; gap: 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 18px;
}
.sms-stat-icon {
  width: 46px; height: 46px; border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; flex-shrink: 0;
}
.sms-stat-value { display: block; font-size: 1.4rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
.sms-stat-label { display: block; font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

.sms-charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.sms-chart-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 20px;
}
.sms-chart-card.wide { grid-column: 1 / -1; }
.sms-chart-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.sms-chart-header h4 {
  display: flex; align-items: center; gap: 8px;
  margin: 0; font-size: 14px; font-weight: 700; color: var(--text-primary);
}
.sms-chart-header h4 i { color: var(--accent); }
.sms-chart-sub { font-size: 11.5px; color: var(--text-muted); }
.sms-chart-body { height: 260px; position: relative; }
.sms-chart-empty {
  height: 100%; display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); font-size: 13px;
}

.failure-list { display: flex; flex-direction: column; gap: 10px; }
.failure-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 10px 12px; background: var(--bg-subtle); border-radius: var(--radius-sm);
}
.failure-reason { font-size: 12.5px; color: var(--text-secondary); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.failure-count {
  background: var(--danger-bg); color: var(--danger-contrast);
  border-radius: var(--radius-full); padding: 2px 10px; font-size: 12px; font-weight: 700;
}
.provider-compare { display: flex; flex-direction: column; gap: 12px; }
.provider-row-compare { display: flex; align-items: center; gap: 12px; }
.provider-name { display: flex; align-items: center; gap: 8px; width: 130px; font-size: 12.5px; font-weight: 600; }
.provider-name i { color: var(--accent); }
.provider-bar-track { flex: 1; height: 10px; background: var(--bg-subtle); border-radius: var(--radius-full); overflow: hidden; }
.provider-bar { height: 100%; background: linear-gradient(90deg, var(--accent), #818CF8); border-radius: var(--radius-full); transition: width 0.5s ease; }
.provider-total { font-weight: 700; font-size: 12.5px; min-width: 32px; text-align: left; }

@media (max-width: 1200px) {
  .sms-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 800px) {
  .sms-charts-grid { grid-template-columns: 1fr; }
  .sms-stats-grid { grid-template-columns: 1fr; }
}
</style>
