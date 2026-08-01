<template>
  <div class="page-view">
    <EnterpriseTable
      :value="logs"
      :loading="loading"
      searchPlaceholder="بحث في المستلم أو المعرّف أو الرسالة..."
      emptyTitle="لا توجد رسائل"
      emptySubtitle="ستظهر رسائل SMS المرسلة هنا"
      :columns="tableColumns"
      @refresh="fetchLogs"
      @row-click="openDetails"
    >
      <template #filters>
        <Select
          v-model="filters.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          class="filter-select"
          placeholder="الحالة"
          @change="fetchLogs"
        />
        <Select
          v-model="filters.provider_id"
          :options="providers"
          optionLabel="name"
          optionValue="id"
          class="filter-select"
          placeholder="المزود"
          filter
          show-clear
          @change="fetchLogs"
        />
        <DatePicker v-model="filters.date_from" class="filter-select" dateFormat="yy-mm-dd" placeholder="من تاريخ" @change="fetchLogs" />
        <DatePicker v-model="filters.date_to" class="filter-select" dateFormat="yy-mm-dd" placeholder="إلى تاريخ" @change="fetchLogs" />
        <InputText v-model="search" class="filter-select" placeholder="بحث..." dir="rtl" @input="debouncedSearch" />
      </template>

      <template #actions>
        <div class="log-toolbar">
          <Select v-model="exportFormat" :options="[{ value: 'csv', label: 'CSV' }, { value: 'excel', label: 'Excel' }]" optionLabel="label" optionValue="value" class="export-select" />
          <button class="btn-secondary" @click="exportLogs" :disabled="!logs.length">
            <i class="pi pi-download"></i>
            <span>تصدير</span>
          </button>
          <button class="btn-secondary" @click="printLogs">
            <i class="pi pi-print"></i>
            <span>طباعة</span>
          </button>
        </div>
      </template>

      <template #default="{ hiddenColumns }">
        <Column field="status" header="الحالة" style="width: 110px;">
          <template #body="s">
            <span class="status-badge" :class="statusClass(s.data.status)">
              {{ statusLabel(s.data.status) }}
            </span>
          </template>
        </Column>
        <Column field="recipient" header="المستلم" sortable style="min-width: 130px;">
          <template #body="s">
            <span class="recipient-cell" dir="ltr">{{ s.data.recipient }}</span>
          </template>
        </Column>
        <Column field="message" header="الرسالة" style="min-width: 240px;">
          <template #body="s">
            <div class="msg-preview">{{ s.data.message }}</div>
          </template>
        </Column>
        <Column header="المزود" style="min-width: 120px;">
          <template #body="s">
            <span v-if="s.data.provider">{{ s.data.provider.name }}</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>
        <Column field="cost" header="التكلفة" style="width: 90px;">
          <template #body="s">
            {{ Number(s.data.cost).toFixed(3) }}
          </template>
        </Column>
        <Column field="attempts" header="المحاولات" style="width: 90px;">
          <template #body="s">
            <span class="attempt-badge" :class="{ retried: s.data.attempts > 1 }">{{ s.data.attempts }}</span>
          </template>
        </Column>
        <Column field="created_at" header="وقت الإرسال" sortable style="min-width: 160px;">
          <template #body="s">
            <span class="date-cell">{{ formatDate(s.data.sent_at || s.data.created_at) }}</span>
          </template>
        </Column>
        <Column field="duration_ms" header="المدة" style="width: 90px;">
          <template #body="s">
            <span v-if="s.data.duration_ms">{{ s.data.duration_ms }}ms</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>
        <Column field="message_id" header="معرّف الرسالة" style="min-width: 140px;">
          <template #body="s">
            <span v-if="s.data.message_id" class="mono muted">{{ shortId(s.data.message_id) }}</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>
        <Column header="الإجراءات" style="width: 80px; text-align: center;" frozen alignFrozen="right">
          <template #body="s">
            <TableActionMenu :items="getRowActions(s.data)" />
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <!-- Details Drawer -->
    <Drawer v-model:visible="showDetails" position="left" :style="{ width: '520px' }" header="تفاصيل الرسالة" class="log-drawer">
      <div v-if="detailsLoading" class="drawer-loading">
        <div v-for="i in 5" :key="i" class="skeleton-cell" style="height: 50px; margin-bottom: 12px;"></div>
      </div>
      <div v-else-if="selectedLog" class="drawer-content">
        <div class="drawer-head">
          <span class="status-badge" :class="statusClass(selectedLog.status)">{{ statusLabel(selectedLog.status) }}</span>
          <span class="mono muted" v-if="selectedLog.uuid">{{ shortId(selectedLog.uuid, 12) }}</span>
        </div>

        <div class="detail-section">
          <h4><i class="pi pi-phone"></i> المستلم</h4>
          <p class="mono" dir="ltr">{{ selectedLog.recipient }}</p>
        </div>

        <div class="detail-section">
          <h4><i class="pi pi-comment"></i> نص الرسالة</h4>
          <div class="message-box">{{ selectedLog.message }}</div>
        </div>

        <div class="detail-grid">
          <div class="detail-item"><span>المزود</span><strong>{{ selectedLog.provider?.name || '—' }}</strong></div>
          <div class="detail-item"><span>القالب</span><strong>{{ selectedLog.template?.title || '—' }}</strong></div>
          <div class="detail-item"><span>أرسلها</span><strong>{{ selectedLog.creator?.name || 'النظام' }}</strong></div>
          <div class="detail-item"><span>المحاولات</span><strong>{{ selectedLog.attempts }}</strong></div>
          <div class="detail-item"><span>التكلفة</span><strong>{{ selectedLog.cost }} ₪</strong></div>
          <div class="detail-item"><span>المدة</span><strong>{{ selectedLog.duration_ms ? selectedLog.duration_ms + 'ms' : '—' }}</strong></div>
          <div class="detail-item"><span>HTTP</span><strong>{{ selectedLog.http_status || '—' }}</strong></div>
          <div class="detail-item"><span>معرّف الرسالة</span><strong class="mono" dir="ltr">{{ selectedLog.message_id || '—' }}</strong></div>
        </div>

        <div v-if="selectedLog.failure_reason" class="detail-section">
          <h4><i class="pi pi-exclamation-triangle"></i> سبب الفشل</h4>
          <div class="error-box">{{ selectedLog.failure_reason }}</div>
        </div>

        <div v-if="selectedLog.provider_response" class="detail-section">
          <h4><i class="pi pi-server"></i> استجابة المزود</h4>
          <pre class="json-box">{{ formatJson(selectedLog.provider_response) }}</pre>
        </div>

        <div v-if="selectedLog.response_payload" class="detail-section">
          <h4><i class="pi pi-arrow-up"></i> طلب API</h4>
          <pre class="json-box">{{ formatJson(selectedLog.response_payload) }}</pre>
        </div>

        <div v-if="selectedLog.failures?.length" class="detail-section">
          <h4><i class="pi pi-history"></i> الجدول الزمني</h4>
          <div class="timeline">
            <div v-for="(f, i) in selectedLog.failures" :key="i" class="timeline-item">
              <div class="timeline-dot"><i class="pi pi-times"></i></div>
              <div>
                <strong>محاولة {{ f.attempt }}</strong>
                <span>{{ f.error_message }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="drawer-actions">
          <button v-if="canRetry(selectedLog)" class="btn-primary" @click="retryLog">
            <i class="pi pi-refresh"></i>
            <span>إعادة المحاولة</span>
          </button>
        </div>
      </div>
    </Drawer>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const logs = ref([])
const providers = ref([])
const loading = ref(false)
const detailsLoading = ref(false)
const showDetails = ref(false)
const selectedLog = ref(null)
const search = ref('')
const exportFormat = ref('csv')

const filters = reactive({ status: null, provider_id: null, date_from: null, date_to: null })
let searchTimer = null

const statusOptions = [
  { value: null, label: 'كل الحالات' },
  { value: 'pending', label: 'قيد الانتظار' },
  { value: 'queued', label: 'في قائمة الانتظار' },
  { value: 'sent', label: 'مرسلة' },
  { value: 'delivered', label: 'تم التسليم' },
  { value: 'failed', label: 'فاشلة' },
  { value: 'retrying', label: 'إعادة محاولة' }
]

const tableColumns = [
  { field: 'status', header: 'الحالة' },
  { field: 'recipient', header: 'المستلم' },
  { field: 'message', header: 'الرسالة' },
  { field: 'cost', header: 'التكلفة' },
  { field: 'attempts', header: 'المحاولات' },
  { field: 'created_at', header: 'وقت الإرسال' }
]

const statusLabels = {
  pending: 'قيد الانتظار', queued: 'في الانتظار', sent: 'مرسلة',
  delivered: 'تم التسليم', failed: 'فاشلة', retrying: 'إعادة محاولة'
}

onMounted(() => { fetchLogs(); fetchProviders() })

function buildParams() {
  const params = {}
  if (filters.status) params.status = filters.status
  if (filters.provider_id) params.provider_id = filters.provider_id
  if (filters.date_from) params.date_from = formatDateParam(filters.date_from)
  if (filters.date_to) params.date_to = formatDateParam(filters.date_to)
  if (search.value) params.search = search.value
  return params
}

async function fetchLogs() {
  loading.value = true
  try {
    const { data } = await api.get('/sms/logs', { params: buildParams() })
    logs.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل السجلات: ' + (err.response?.data?.message || err.message))
  } finally {
    loading.value = false
  }
}

async function fetchProviders() {
  try {
    const { data } = await api.get('/sms/providers')
    providers.value = data.data
  } catch (err) { /* providers optional */ }
}

function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchLogs, 400)
}

async function openDetails(event) {
  const logId = event?.data?.id ?? event
  detailsLoading.value = true
  showDetails.value = true
  try {
    const { data } = await api.get(`/sms/logs/${logId}`)
    selectedLog.value = data.data
  } catch (err) {
    toast.error('تعذر تحميل تفاصيل الرسالة')
    showDetails.value = false
  } finally {
    detailsLoading.value = false
  }
}

function getRowActions(log) {
  return [
    { label: 'التفاصيل', icon: 'pi pi-eye', action: () => openDetails(log.id) },
    { label: 'إعادة المحاولة', icon: 'pi pi-refresh', action: () => retryLog(log), disabled: log.status !== 'failed' },
    { label: 'حذف', icon: 'pi pi-trash', action: () => deleteLog(log), danger: true }
  ]
}

function canRetry(log) { return log?.status === 'failed' || log?.status === 'retrying' }

async function retryLog(log) {
  const target = log?.id ? log : selectedLog.value
  try {
    const { data } = await api.post(`/sms/logs/${target.id}/retry`)
    toast.success(data.message)
    showDetails.value = false
    fetchLogs()
  } catch (err) {
    toast.error('تعذر إعادة المحاولة')
  }
}

async function deleteLog(log) {
  toast.info('حذف سجلات الرسائل غير مدعوم — تُحذف تلقائيًا بعد فترة الاحتفاظ')
}

function exportLogs() {
  const params = buildParams()
  params.format = exportFormat.value
  const qs = new URLSearchParams(params).toString()
  const url = api.defaults.baseURL + '/sms/logs/export?' + qs
  window.open(url, '_blank')
}

function printLogs() {
  const w = window.open('', '_blank')
  const rows = logs.value.map(l => `<tr>
    <td>${statusLabel(l.status)}</td><td>${l.recipient}</td>
    <td>${l.message}</td><td>${l.provider?.name || ''}</td>
    <td>${formatDate(l.created_at)}</td></tr>`).join('')
  w.document.write(`<html dir="rtl"><head><title>سجلات SMS</title></head>
    <body><h2>سجلات الرسائل النصية</h2>
    <table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;font-size:12px">
    <thead><tr><th>الحالة</th><th>المستلم</th><th>الرسالة</th><th>المزود</th><th>التاريخ</th></tr></thead>
    <tbody>${rows}</tbody></table></body></html>`)
  w.document.close()
  w.print()
}

function statusLabel(s) { return statusLabels[s] || s }
function statusClass(s) {
  return {
    sent: 'status-success', delivered: 'status-success', queued: 'status-info',
    pending: 'status-info', retrying: 'status-warning', failed: 'status-danger'
  }[s] || 'status-neutral'
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('ar-EG', { dateStyle: 'short', timeStyle: 'short' })
}
function formatDateParam(d) {
  if (d instanceof Date) return d.toISOString().slice(0, 10)
  return d
}
function shortId(s, n = 8) { return s ? s.slice(0, n) + '…' : '—' }
function formatJson(obj) { return JSON.stringify(obj, null, 2) }
</script>

<style scoped>
.log-toolbar { display: flex; gap: 8px; align-items: center; }
.export-select { width: 110px; }
.recipient-cell { font-weight: 600; color: var(--text-primary); }
.msg-preview {
  font-size: 12.5px; color: var(--text-secondary);
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.attempt-badge {
  display: inline-block; min-width: 22px; text-align: center;
  background: var(--bg-subtle); border-radius: var(--radius-full);
  padding: 1px 8px; font-size: 12px; font-weight: 600;
}
.attempt-badge.retried { background: var(--warning-bg); color: var(--warning-contrast); }
.date-cell { color: var(--text-secondary); font-size: 12.5px; }
.mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; }
.muted, .text-muted { color: var(--text-muted); }

.drawer-content { display: flex; flex-direction: column; gap: 18px; }
.drawer-head { display: flex; align-items: center; justify-content: space-between; }
.detail-section h4 {
  display: flex; align-items: center; gap: 8px;
  font-size: 12.5px; font-weight: 700; color: var(--text-primary);
  margin: 0 0 8px;
}
.detail-section h4 i { color: var(--accent); }
.detail-section p { margin: 0; }
.message-box {
  background: var(--bg-subtle); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 14px;
  white-space: pre-wrap; font-size: 13px; color: var(--text-primary);
}
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.detail-item {
  display: flex; flex-direction: column; gap: 3px;
  background: var(--bg-subtle); border-radius: var(--radius-sm);
  padding: 10px 12px;
}
.detail-item span { font-size: 11.5px; color: var(--text-secondary); }
.detail-item strong { font-size: 13px; color: var(--text-primary); word-break: break-all; }
.error-box {
  background: var(--danger-bg); border: 1px solid var(--danger-border);
  color: var(--danger-contrast); border-radius: var(--radius-sm);
  padding: 12px; font-size: 13px;
}
.json-box {
  background: var(--bg-subtle); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 12px;
  font-size: 11.5px; direction: ltr; text-align: left;
  max-height: 220px; overflow: auto;
}
.timeline { display: flex; flex-direction: column; gap: 12px; }
.timeline-item { display: flex; gap: 10px; align-items: flex-start; }
.timeline-dot {
  width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
  background: var(--danger-bg); color: var(--danger); font-size: 11px;
  display: flex; align-items: center; justify-content: center;
}
.timeline-item strong { display: block; font-size: 13px; }
.timeline-item span { font-size: 12px; color: var(--text-secondary); }
.drawer-actions { margin-top: 8px; }
.drawer-loading { padding: 8px 0; }
</style>
