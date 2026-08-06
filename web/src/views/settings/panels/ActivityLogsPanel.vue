<template>
  <div class="panels-stack">
    <SettingsCard
      title="سجل نشاط النظام"
      subtitle="مراجعة كاملة للتغييرات والأحداث داخل النظام لأغراض التدقيق"
      icon="pi pi-history"
      icon-tone="indigo"
      :show-footer="false"
    >
      <div class="audit-intro">
        <i class="pi pi-shield-check"></i>
        <div>
          <strong>سجل تدقيق غير قابل للتغيير</strong>
          <p>
            يتم تسجيل كل تعديل على الإعدادات والمزودين والقوالب والعمليات المرتبطة بها مع
            المستخدم والوقت والعنوان IP.
          </p>
        </div>
      </div>

      <div class="audit-toolbar">
        <div class="field-card audit-field-card audit-field-card-wide">
          <div class="field-title">البحث</div>
          <div class="field-subtitle">ابحث باسم المستخدم أو الوصف أو عنوان IP</div>
          <InputText
            v-model="search"
            class="audit-search"
            placeholder="اكتب كلمة للبحث"
            @input="debouncedLoad"
          />
        </div>

        <div class="field-card audit-field-card">
          <div class="field-title">المستخدم</div>
          <div class="field-subtitle">اعرض السجلات الخاصة بمستخدم محدد</div>
          <Select
            v-model="filters.user_id"
            :options="users"
            optionLabel="name"
            optionValue="id"
            class="audit-filter"
            placeholder="كل المستخدمين"
            showClear
            filter
            @change="loadLogs"
          />
        </div>

        <div class="field-card audit-field-card">
          <div class="field-title">نوع العملية</div>
          <div class="field-subtitle">فلتر حسب نوع التعديل أو الإجراء</div>
          <Select
            v-model="filters.action"
            :options="actionOptions"
            optionLabel="label"
            optionValue="value"
            class="audit-filter"
            placeholder="كل الإجراءات"
            showClear
            @change="loadLogs"
          />
        </div>

        <div class="field-card audit-field-card">
          <div class="field-title">من تاريخ</div>
          <div class="field-subtitle">حدد بداية الفترة الزمنية</div>
          <DatePicker
            v-model="filters.date_from"
            class="audit-filter"
            dateFormat="yy-mm-dd"
            placeholder="اختر التاريخ"
            @change="loadLogs"
          />
        </div>

        <div class="field-card audit-field-card">
          <div class="field-title">إلى تاريخ</div>
          <div class="field-subtitle">حدد نهاية الفترة الزمنية</div>
          <DatePicker
            v-model="filters.date_to"
            class="audit-filter"
            dateFormat="yy-mm-dd"
            placeholder="اختر التاريخ"
            @change="loadLogs"
          />
        </div>
      </div>

      <div class="audit-list" v-if="!loading && logs.length">
        <div
          v-for="log in paginatedLogs"
          :key="log.id"
          class="audit-row"
          :class="{ clickable: log.new_value }"
          @click="log.new_value ? openDetails(log) : null"
        >
          <div class="audit-action-icon" :class="actionTone(log.action)">
            <i :class="actionIcon(log.action)"></i>
          </div>
          <div class="audit-main">
            <div class="audit-description">{{ log.description || log.action }}</div>
            <div class="audit-meta">
              <span class="audit-user">
                <i class="pi pi-user"></i>{{ log.user?.name || 'النظام' }}
              </span>
              <span class="audit-action-code" dir="ltr">{{ log.action }}</span>
              <span class="audit-ip" dir="ltr" v-if="log.ip_address">{{ log.ip_address }}</span>
            </div>
          </div>
          <div class="audit-side">
            <span class="audit-date">{{ formatDate(log.created_at) }}</span>
            <i v-if="log.new_value" class="pi pi-chevron-left audit-chevron"></i>
          </div>
        </div>
      </div>

      <div v-else-if="!loading" class="audit-empty">
        <div class="empty-icon-circle"><i class="pi pi-inbox"></i></div>
        <h4>لا توجد سجلات نشاط</h4>
        <p>ستظهر هنا تغييرات الإعدادات والإجراءات المهمة عند حدوثها.</p>
      </div>

      <div v-else class="audit-loading">
        <div v-for="i in 6" :key="i" class="skeleton-cell" style="height: 56px; margin-bottom: 10px;"></div>
      </div>

      <div class="audit-footer">
        <span class="audit-count" v-if="logs.length">
          عرض {{ paginatedLogs.length }} من {{ logs.length }} سجل
        </span>
        <div class="audit-footer-actions">
          <button
            class="btn-secondary btn-sm"
            :disabled="clearing"
            @click="confirmClear = true"
          >
            <i v-if="clearing" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-trash"></i>
            <span>تنظيف السجلات</span>
          </button>
        </div>
      </div>
    </SettingsCard>

    <Drawer v-model:visible="showDetails" position="left" :style="{ width: '480px' }" header="تفاصيل الحدث">
      <div v-if="selectedLog" class="drawer-content">
        <div class="detail-item detail-title">
          <span>الإجراء</span>
          <strong>{{ selectedLog.action }}</strong>
        </div>
        <div class="detail-item">
          <span>الوصف</span>
          <strong>{{ selectedLog.description || '—' }}</strong>
        </div>
        <div class="detail-item">
          <span>المستخدم</span>
          <strong>{{ selectedLog.user?.name || 'النظام' }}</strong>
        </div>
        <div class="detail-item">
          <span>الوقت</span>
          <strong>{{ formatDate(selectedLog.created_at, true) }}</strong>
        </div>
        <div class="detail-item">
          <span>العنوان IP</span>
          <strong class="mono" dir="ltr">{{ selectedLog.ip_address || '—' }}</strong>
        </div>
        <div class="detail-item">
          <span>الهدف</span>
          <strong class="mono" dir="ltr">{{ modelShort(selectedLog) }}</strong>
        </div>

        <div v-if="selectedLog.new_value" class="detail-section">
          <h4><i class="pi pi-code"></i> البيانات الجديدة</h4>
          <pre class="json-box">{{ formatJson(selectedLog.new_value) }}</pre>
        </div>
      </div>
    </Drawer>

    <ConfirmModal
      v-model:visible="confirmClear"
      title="تنظيف سجل النشاط"
      message="سيتم حذف جميع سجلات النشاط نهائيًا. هل أنت متأكد؟ يُنصح بتصدير السجلات قبل الحذف."
      variant="danger"
      confirmText="حذف السجلات"
      cancelText="إلغاء"
      @confirm="clearLogs"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'
import SettingsCard from '@/components/settings/SettingsCard.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const toast = useToastStore()

const logs = ref([])
const users = ref([])
const actionOptions = ref([])
const loading = ref(false)
const clearing = ref(false)
const search = ref('')
const filters = reactive({ user_id: null, action: null, date_from: null, date_to: null })
const showDetails = ref(false)
const confirmClear = ref(false)
const selectedLog = ref(null)

let searchTimer = null
let page = 1
const pageSize = 20

const paginatedLogs = computed(() => logs.value.slice(0, page * pageSize))

onMounted(async () => {
  loadLogs()
  try {
    const { data } = await api.get('/activity-logs/actions')
    actionOptions.value = (data.data || []).map(a => ({ value: a, label: a }))
  } catch (err) {
    // optional
  }
  try {
    const { data } = await api.get('/users', { params: { per_page: 100 } })
    const rows = data.data?.data ?? data.data ?? []
    users.value = rows.map(u => ({ id: u.id, name: u.name }))
  } catch (err) {
    // optional
  }
})

function buildParams() {
  const params = {}
  if (search.value) params.search = search.value
  if (filters.user_id) params.user_id = filters.user_id
  if (filters.action) params.action = filters.action
  if (filters.date_from) params.date_from = fmtDate(filters.date_from)
  if (filters.date_to) params.date_to = fmtDate(filters.date_to)
  return params
}

async function loadLogs() {
  loading.value = true
  try {
    const { data } = await api.get('/activity-logs', { params: buildParams() })
    logs.value = data.data || []
    page = 1
  } catch (err) {
    toast.error('تعذر تحميل سجل النشاط: ' + (err.response?.data?.message || err.message))
  } finally {
    loading.value = false
  }
}

function debouncedLoad() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(loadLogs, 400)
}

function openDetails(log) {
  selectedLog.value = log
  showDetails.value = true
}

async function clearLogs() {
  clearing.value = true
  try {
    const { data } = await api.delete('/activity-logs/clear')
    toast.success(data.message || 'تم تنظيف السجلات')
    confirmClear.value = false
    loadLogs()
  } catch (err) {
    toast.error('تعذر تنظيف السجلات')
  } finally {
    clearing.value = false
  }
}

const actionIcons = {
  'settings.updated': 'pi pi-sliders-h',
  'currency.updated': 'pi pi-dollar',
  'currency.set_default': 'pi pi-check-circle',
  'sms.provider.updated': 'pi pi-server',
  'sms.test_connection': 'pi pi-bolt',
  'sms.sent': 'pi pi-send',
  'sms.failed': 'pi pi-exclamation-triangle',
  'sms.retry': 'pi pi-refresh',
  'sms.template.': 'pi pi-file-edit',
  'sms.job.': 'pi pi-calendar-clock',
  'user.': 'pi pi-user-edit'
}

function actionIcon(action) {
  for (const [prefix, icon] of Object.entries(actionIcons)) {
    if (action && action.startsWith(prefix)) return icon
  }
  return 'pi pi-pencil'
}

function actionTone(action) {
  if (!action) return 'tone-default'
  if (action.includes('failed') || action.includes('deleted')) return 'tone-danger'
  if (action.includes('sent') || action.includes('set_default') || action.includes('created')) return 'tone-success'
  if (action.includes('test_connection')) return 'tone-info'
  return 'tone-default'
}

function fmtDate(d) {
  if (!d) return ''
  if (d instanceof Date) return d.toISOString().slice(0, 10)
  return String(d).slice(0, 10)
}

function formatDate(iso, full = false) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('ar-EG', full ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'short', timeStyle: 'short' })
}

function modelShort(log) {
  if (!log.model_type) return '—'
  const short = log.model_type.split('\\').pop()
  return log.model_id ? `${short} #${log.model_id}` : short
}

function formatJson(obj) {
  if (obj === null || obj === undefined) return ''
  if (typeof obj === 'string') return obj
  return JSON.stringify(obj, null, 2)
}
</script>

<style scoped>
.panels-stack { display: flex; flex-direction: column; gap: 20px; }

.audit-intro {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: var(--bg-subtle);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  margin-bottom: 18px;
}
.audit-intro i { font-size: 1.4rem; color: var(--success); margin-top: 2px; }
.audit-intro strong { display: block; font-size: 13.5px; color: var(--text-primary); }
.audit-intro p { margin: 3px 0 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; }

.audit-toolbar {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}
.audit-field-card {
  min-width: 0;
}
.audit-field-card-wide {
  grid-column: span 1;
}
.audit-search,
.audit-filter {
  width: 100%;
}

.audit-list { display: flex; flex-direction: column; gap: 8px; }
.audit-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  transition: border-color 0.15s ease, background 0.15s ease;
}
.audit-row.clickable { cursor: pointer; }
.audit-row.clickable:hover { border-color: var(--border-hover); background: var(--bg-subtle); }
.audit-action-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}
.tone-default { background: var(--bg-subtle); color: var(--text-secondary); }
.tone-danger { background: var(--danger-bg); color: var(--danger); }
.tone-success { background: var(--success-bg); color: var(--success-contrast); }
.tone-info { background: var(--info-bg); color: var(--info-contrast); }
.audit-main { flex: 1; min-width: 0; }
.audit-description { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
.audit-meta { display: flex; align-items: center; gap: 10px; margin-top: 3px; flex-wrap: wrap; }
.audit-user { font-size: 12px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 4px; }
.audit-user i { font-size: 0.8rem; }
.audit-action-code {
  font-size: 11px;
  color: var(--text-muted);
  background: var(--bg-subtle);
  padding: 1px 8px;
  border-radius: var(--radius-full);
  direction: ltr;
}
.audit-ip { font-size: 11px; color: var(--text-muted); }
.audit-side { display: flex; align-items: center; gap: 8px; }
.audit-date { font-size: 12px; color: var(--text-secondary); white-space: nowrap; }
.audit-chevron { font-size: 0.9rem; color: var(--text-muted); }

.audit-empty { text-align: center; padding: 48px 20px; }
.audit-empty h4 { margin: 10px 0 4px; font-size: 15px; color: var(--text-primary); }
.audit-empty p { margin: 0; font-size: 13px; color: var(--text-secondary); }
.empty-icon-circle {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  margin: 0 auto;
  background: var(--bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--text-secondary);
}
.audit-loading { padding: 4px 0; }
.audit-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid var(--border-light);
  flex-wrap: wrap;
}
.audit-count { font-size: 12.5px; color: var(--text-secondary); }
.audit-footer-actions { display: flex; gap: 8px; }
.btn-sm { padding: 7px 14px; font-size: 12.5px; }

.drawer-content { display: flex; flex-direction: column; gap: 12px; }
.detail-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  background: var(--bg-subtle);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
}
.detail-item span { font-size: 11.5px; color: var(--text-secondary); }
.detail-item strong { font-size: 13px; color: var(--text-primary); word-break: break-all; }
.detail-title { background: var(--accent-light); }
.detail-title span { color: var(--accent); }
.detail-section h4 {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 8px 0;
}
.detail-section h4 i { color: var(--accent); }
.json-box {
  background: var(--bg-subtle);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 12px;
  font-size: 11.5px;
  direction: ltr;
  text-align: left;
  max-height: 320px;
  overflow: auto;
  white-space: pre-wrap;
}
.mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; direction: ltr; }

@media (max-width: 640px) {
  .audit-toolbar { grid-template-columns: 1fr; }
  .audit-field-card-wide { grid-column: auto; }
  .audit-row { flex-wrap: wrap; }
  .audit-side { width: 100%; justify-content: space-between; }
}
</style>
