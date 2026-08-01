<template>
  <div class="page-view">
    <div class="bulk-layout">
      <!-- Left: compose -->
      <div class="bulk-main">
        <Card class="saas-card">
          <template #title>
            <div class="card-header-title">
              <i class="pi pi-megaphone text-blue"></i>
              <div>
                <h3>إرسال جماعي</h3>
                <span class="card-sub-title">تكوين الرسالة وتحديد المستلمين</span>
              </div>
            </div>
          </template>
          <template #content>
            <div class="form-field" style="margin-bottom: 16px;">
              <label for="bulk-scope">نطاق المستلمين</label>
              <Select
                id="bulk-scope"
                v-model="scope"
                :options="scopeOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                @change="onScopeChange"
              />
            </div>

            <div v-if="scope === 'building'" class="form-field" style="margin-bottom: 16px;">
              <label for="bulk-building">المبنى</label>
              <Select
                id="bulk-building"
                v-model="buildingId"
                :options="buildings"
                optionLabel="label"
                optionValue="id"
                class="w-full"
                filter
                @change="resolveRecipients"
              />
            </div>

            <div v-if="scope === 'property'" class="form-field" style="margin-bottom: 16px;">
              <label for="bulk-location">العقار</label>
              <Select
                id="bulk-location"
                v-model="locationId"
                :options="locations"
                optionLabel="label"
                optionValue="id"
                class="w-full"
                filter
                @change="resolveRecipients"
              />
            </div>

            <div v-if="scope === 'expiring'" class="form-field" style="margin-bottom: 16px;">
              <label for="bulk-days">عقود تنتهي خلال</label>
              <InputNumber id="bulk-days" v-model="expiringDays" class="w-full" :min="1" :max="365" @change="resolveRecipients" />
            </div>

            <div v-if="scope === 'custom'" class="form-field" style="margin-bottom: 16px;">
              <label for="bulk-phones">أرقام مخصصة (افصل بينها بفاصلة)</label>
              <Textarea id="bulk-phones" v-model="customPhones" rows="3" class="w-full" dir="ltr" placeholder="0599000000, 0599111111, ..." />
              <button class="btn-secondary btn-sm" style="margin-top: 8px;" @click="resolveRecipients">
                <i class="pi pi-search"></i>
                <span>تحليل الأرقام</span>
              </button>
            </div>

            <div class="form-field" style="margin-bottom: 16px;">
              <label for="bulk-template">قالب جاهز (اختياري)</label>
              <Select
                id="bulk-template"
                v-model="templateId"
                :options="templates"
                optionLabel="title"
                optionValue="id"
                class="w-full"
                filter
                show-clear
                @change="applyTemplate"
              />
            </div>

            <div class="form-field" style="margin-bottom: 12px;">
              <label for="bulk-message">نص الرسالة</label>
              <Textarea
                id="bulk-message"
                v-model="message"
                rows="6"
                class="w-full"
                placeholder="عزيزي {{tenant_name}}، ..."
              />
              <span class="char-counter">{{ message.length }}/5000</span>
            </div>

            <div class="preview-box" v-if="message">
              <div class="preview-label"><i class="pi pi-eye"></i> معاينة</div>
              <p>{{ renderedPreview }}</p>
            </div>

            <div class="form-actions" style="margin-top: 18px;">
              <button class="btn-primary" @click="sendBulk" :disabled="sending || !recipients.length || !message.trim()">
                <i v-if="sending" class="pi pi-spin pi-spinner"></i>
                <i v-else class="pi pi-send"></i>
                <span>{{ sending ? 'جارٍ الإرسال...' : `إرسال إلى ${recipients.length} مستلم` }}</span>
              </button>
            </div>
          </template>
        </Card>
      </div>

      <!-- Right: recipients preview -->
      <div class="bulk-side">
        <Card class="saas-card">
          <template #title>
            <div class="card-header-title">
              <i class="pi pi-users text-green"></i>
              <div>
                <h3>معاينة المستلمين</h3>
                <span class="card-sub-title" v-if="resolving">جارٍ التحميل...</span>
                <span class="card-sub-title" v-else>{{ recipients.length }} مستلم</span>
              </div>
            </div>
          </template>
          <template #content>
            <div v-if="resolving" class="side-loading">
              <div v-for="i in 5" :key="i" class="skeleton-cell" style="height: 36px; margin-bottom: 8px;"></div>
            </div>
            <div v-else-if="!recipients.length" class="empty-mini">
              <i class="pi pi-inbox"></i>
              <p>اختر نطاق المستلمين لمعاينتهم قبل الإرسال</p>
            </div>
            <div v-else class="recipient-list">
              <div class="recipient-list-header">
                <span>{{ recipients.length }} رقم</span>
                <button class="btn-link" @click="recipients = []">مسح</button>
              </div>
              <div v-for="(phone, i) in visibleRecipients" :key="i" class="recipient-item">
                <span class="rec-avatar"><i class="pi pi-user"></i></span>
                <span class="mono" dir="ltr">{{ phone }}</span>
                <button class="btn-icon-sm" @click="removeRecipient(i)"><i class="pi pi-times"></i></button>
              </div>
              <div v-if="recipients.length > 12" class="recipient-more">
                +{{ recipients.length - 12 }} مستلم آخر
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const scope = ref('tenants')
const scopesOptions = null
const buildingId = ref(null)
const locationId = ref(null)
const expiringDays = ref(30)
const customPhones = ref('')
const templateId = ref(null)
const message = ref('')
const recipients = ref([])
const resolving = ref(false)
const sending = ref(false)

const buildings = ref([])
const locations = ref([])
const templates = ref([])

const scopeOptions = [
  { value: 'tenants', label: 'جميع المستأجرين' },
  { value: 'building', label: 'مبنى محدد' },
  { value: 'property', label: 'عقار (موقع) محدد' },
  { value: 'expiring', label: 'عقود تنتهي قريبًا' },
  { value: 'overdue', label: 'فواتير متأخرة' },
  { value: 'custom', label: 'أرقام مخصصة' }
]

const sampleData = {
  tenant_name: 'أكرم بركات', property: 'الإعمار بلازا', building: 'المبنى أ', unit: '12',
  invoice_number: 'INV-2026-0001', amount: '1,000 ₪', remaining: '400 ₪',
  due_date: '31/08/2026', payment_date: '05/08/2026', contract_end: '31/12/2026',
  company: 'شركة الإعمار للعقارات', phone: '0599000000', website: 'www.emaarplus.com'
}

const renderedPreview = computed(() =>
  message.value.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi, (m, key) => sampleData[key] ?? m)
)

const visibleRecipients = computed(() => recipients.value.slice(0, 12))

onMounted(() => { fetchBuildings(); fetchLocations(); fetchTemplates(); resolveRecipients() })

async function fetchBuildings() {
  try {
    const { data } = await api.get('/buildings')
    buildings.value = data.data.map(b => ({ ...b, label: `${b.name} — ${b.location?.name || ''}` }))
  } catch (err) { toast.error('تعذر تحميل المباني') }
}

async function fetchLocations() {
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data.map(l => ({ ...l, label: l.name }))
  } catch (err) { toast.error('تعذر تحميل المواقع') }
}

async function fetchTemplates() {
  try {
    const { data } = await api.get('/sms/templates')
    templates.value = data.data
  } catch (err) { /* templates optional */ }
}

async function onScopeChange() {
  recipients.value = []
  resolveRecipients()
}

async function resolveRecipients() {
  resolving.value = true
  try {
    const params = { scope: scope.value }
    if (scope.value === 'building') params.building_id = buildingId.value
    if (scope.value === 'property') params.location_id = locationId.value
    if (scope.value === 'expiring') params.days = expiringDays.value
    if (scope.value === 'custom') params.phones = customPhones.value

    const { data } = await api.get('/sms/recipients', { params })
    recipients.value = data.data.recipients
  } catch (err) {
    if (err.response?.data?.message) toast.error(err.response.data.message)
    recipients.value = []
  } finally {
    resolving.value = false
  }
}

function applyTemplate() {
  const t = templates.value.find(x => x.id === templateId.value)
  if (t) message.value = t.message
}

function removeRecipient(index) {
  // index maps into visibleRecipients slice; find actual index
  recipients.value.splice(index, 1)
}

async function sendBulk() {
  sending.value = true
  try {
    const { data } = await api.post('/sms/bulk', {
      recipients: recipients.value,
      message: message.value,
      template_id: templateId.value || null,
      scope: scope.value
    })
    toast.success(data.message || 'تمت جدولة الرسائل')
    recipients.value = []
    message.value = ''
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر إرسال الرسائل')
  } finally {
    sending.value = false
  }
}
</script>

<style scoped>
.bulk-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 20px;
  align-items: start;
}
.saas-card { border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
.card-header-title { display: flex; align-items: center; gap: 12px; }
.card-header-title i { font-size: 1.4rem; }
.card-header-title h3 { font-size: 16px; font-weight: 700; margin: 0; }
.card-sub-title { font-size: 12.5px; color: var(--text-secondary); font-weight: 400; }
.text-blue { color: var(--info-contrast); }
.text-green { color: var(--success-contrast); }

.char-counter { display: block; text-align: left; font-size: 11.5px; color: var(--text-muted); margin-top: 4px; }
.preview-box {
  background: var(--bg-subtle); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 14px; margin-top: 6px;
}
.preview-label { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
.preview-box p { margin: 0; white-space: pre-wrap; font-size: 13px; color: var(--text-primary); }

.side-loading { padding: 4px 0; }
.empty-mini {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  padding: 40px 20px; text-align: center;
}
.empty-mini i { font-size: 2rem; color: var(--text-muted); }
.empty-mini p { margin: 0; font-size: 13px; color: var(--text-secondary); }
.recipient-list { max-height: 480px; overflow-y: auto; }
.recipient-list-header {
  display: flex; align-items: center; justify-content: space-between;
  padding-bottom: 8px; border-bottom: 1px solid var(--border);
  font-size: 12.5px; font-weight: 700; color: var(--text-primary);
}
.btn-link { border: none; background: none; color: var(--danger); font-size: 12.5px; cursor: pointer; font-weight: 600; }
.recipient-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 4px; border-bottom: 1px solid var(--border-light);
}
.rec-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center; font-size: 11px;
}
.mono { font-family: 'JetBrains Mono', monospace; font-size: 12.5px; flex: 1; }
.btn-icon-sm {
  width: 26px; height: 26px; border: none; background: none;
  color: var(--text-muted); border-radius: var(--radius-xs); cursor: pointer;
}
.btn-icon-sm:hover { background: var(--danger-bg); color: var(--danger); }
.recipient-more { padding: 10px; text-align: center; font-size: 12.5px; color: var(--text-secondary); }
.btn-sm { padding: 7px 14px; font-size: 12.5px; }

@media (max-width: 1100px) {
  .bulk-layout { grid-template-columns: 1fr; }
}
</style>
