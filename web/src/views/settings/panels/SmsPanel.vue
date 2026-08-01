<template>
  <div class="panels-stack">
    <!-- Connection settings for the selected provider -->
    <SettingsCard
      title="بوابة الرسائل SMS"
      subtitle="تكوين مزود الرسائل النصية القصيرة واختبار الاتصال"
      icon="pi pi-send"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="مزود الرسائل" forId="sms-provider" helpText="اختر المزود النشط لتسليم الرسائل">
          <Select
            id="sms-provider"
            v-model="selectedProviderKey"
            :options="providers"
            optionLabel="name"
            optionValue="key"
            class="w-full"
            filter
            @change="onProviderChange"
          />
        </FormField>

        <FormField label="معرّف المرسل" forId="sms-sender" helpText="اسم أو رقم المرسل الظاهر للمستلم">
          <InputText id="sms-sender" v-model="form.sender_id" class="w-full" placeholder="EMAARPlus" />
        </FormField>

        <FormField label="عنوان API" forId="sms-url" helpText="عنوان بوابة المزود">
          <InputText id="sms-url" v-model="form.api_url" class="w-full" dir="ltr" placeholder="https://api.provider.com/sms" />
        </FormField>

        <FormField label="مفتاح API" forId="sms-key" helpText="اتركه فارغًا للإبقاء على المفتاح الحالي">
          <InputText id="sms-key" v-model="form.api_key" class="w-full" dir="ltr" type="password" :placeholder="hasApiKey ? '••••••••' : ''" />
        </FormField>

        <FormField label="اسم المستخدم" forId="sms-user">
          <InputText id="sms-user" v-model="form.username" class="w-full" dir="ltr" />
        </FormField>

        <FormField label="كلمة المرور" forId="sms-pass" helpText="اتركها فارغة للإبقاء على كلمة المرور الحالية">
          <InputText id="sms-pass" v-model="form.password" class="w-full" dir="ltr" type="password" :placeholder="hasPassword ? '••••••••' : ''" />
        </FormField>

        <FormField label="طريقة HTTP" forId="sms-method">
          <Select
            id="sms-method"
            v-model="form.http_method"
            :options="[{ value: 'POST', label: 'POST' }, { value: 'GET', label: 'GET' }, { value: 'PUT', label: 'PUT' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="نوع المحتوى" forId="sms-content">
          <Select
            id="sms-content"
            v-model="form.content_type"
            :options="contentTypes"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="نوع المصادقة" forId="sms-auth">
          <Select
            id="sms-auth"
            v-model="form.authorization_type"
            :options="authTypes"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="مهلة الاتصال (ثانية)" forId="sms-timeout">
          <InputNumber id="sms-timeout" v-model="form.timeout" class="w-full" :min="1" :max="120" />
        </FormField>

        <FormField label="عدد المحاولات" forId="sms-retries" helpText="عدد مرات إعادة المحاولة عند الفشل">
          <InputNumber id="sms-retries" v-model="form.retries" class="w-full" :min="0" :max="10" />
        </FormField>
      </div>

      <div class="form-section-title" style="margin-top: 24px;">
        <i class="pi pi-tag"></i>
        <span>الترويسات المخصصة</span>
      </div>
      <div class="headers-editor" style="margin-top: 12px;">
        <div v-for="(row, idx) in headerRows" :key="idx" class="header-row">
          <InputText v-model="row.key" class="w-full" dir="ltr" placeholder="X-Custom-Header" />
          <InputText v-model="row.value" class="w-full" dir="ltr" placeholder="القيمة" />
          <button class="btn-icon" @click="headerRows.splice(idx, 1)" title="حذف">
            <i class="pi pi-trash"></i>
          </button>
        </div>
        <button class="btn-secondary btn-sm" @click="headerRows.push({ key: '', value: '' })">
          <i class="pi pi-plus"></i>
          إضافة ترويسة
        </button>
      </div>

      <div class="connection-status" :class="connectionState">
        <div v-if="testing" class="test-loading">
          <i class="pi pi-spin pi-spinner"></i>
          <span>جارٍ اختبار الاتصال...</span>
        </div>
        <template v-else-if="testResult">
          <i :class="testResult.success ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
          <div>
            <strong>{{ testResult.success ? 'تم الاتصال بنجاح' : 'فشل الاتصال' }}</strong>
            <span>{{ testResult.message }}</span>
            <span v-if="testResult.latency_ms" class="test-latency">زمن الاستجابة: {{ testResult.latency_ms }}ms</span>
            <span v-if="testResult.code" class="test-code">HTTP {{ testResult.code }}</span>
          </div>
        </template>
        <template v-else>
          <i class="pi pi-info-circle"></i>
          <span>اختبار الاتصال للتأكد من صحة الإعدادات قبل الحفظ.</span>
        </template>
      </div>

      <div class="test-actions">
        <button class="btn-secondary" :disabled="testing" @click="testConnection">
          <i v-if="testing" class="pi pi-spin pi-spinner"></i>
          <i v-else class="pi pi-bolt"></i>
          <span>اختبار الاتصال</span>
        </button>
        <button class="btn-secondary" :disabled="sendingTest" @click="sendTestSms">
          <i v-if="sendingTest" class="pi pi-spin pi-spinner"></i>
          <i v-else class="pi pi-phone"></i>
          <span>إرسال رسالة اختبار</span>
        </button>
        <button class="btn-secondary" @click="resetProviderForm">
          <i class="pi pi-undo"></i>
          <span>إعادة تعيين</span>
        </button>
      </div>
    </SettingsCard>

    <!-- Providers list -->
    <SettingsCard
      title="مزودو الرسائل المتاحون"
      subtitle="إدارة المزودين المسجلين في النظام"
      icon="pi pi-list"
      icon-tone="green"
      :show-footer="false"
    >
      <div class="provider-list">
        <div v-for="p in providers" :key="p.id" class="provider-row" :class="{ default: p.is_default }">
          <div class="provider-identity">
            <div class="provider-avatar"><i class="pi pi-server"></i></div>
            <div>
              <strong>{{ p.name }}</strong>
              <span>{{ p.key }} — {{ p.is_default ? 'المزود الافتراضي' : 'متاح' }}</span>
            </div>
          </div>
          <div class="provider-actions">
            <Tag :value="p.is_active ? 'مفعّل' : 'معطّل'" :severity="p.is_active ? 'success' : 'secondary'" />
            <button class="btn-secondary btn-sm" @click="selectAndEdit(p)">
              <i class="pi pi-pencil"></i>
              تعديل
            </button>
          </div>
        </div>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import SettingsCard from '@/components/settings/SettingsCard.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])
const store = useSettingsStore()
const toast = useToastStore()

const providers = ref([])
const selectedProviderKey = ref('custom')
const form = reactive({
  api_url: '', api_key: '', username: '', password: '', sender_id: '',
  timeout: 15, retries: 3, http_method: 'POST', content_type: 'application/json',
  authorization_type: 'bearer', custom_headers: {}
})
const headerRows = ref([])
const hasApiKey = ref(false)
const hasPassword = ref(false)
const savedFlash = ref(false)
const testing = ref(false)
const sendingTest = ref(false)
const testResult = ref(null)
const connectionState = computed(() => {
  if (testing.value) return 'state-testing'
  if (testResult.value) return testResult.value.success ? 'state-success' : 'state-error'
  return 'state-idle'
})

const contentTypes = [
  { value: 'application/json', label: 'JSON' },
  { value: 'application/x-www-form-urlencoded', label: 'URL Encoded' },
  { value: 'multipart/form-data', label: 'Multipart' },
  { value: 'text/xml', label: 'XML' }
]

const authTypes = [
  { value: 'bearer', label: 'Bearer Token' },
  { value: 'basic', label: 'Basic Auth' },
  { value: 'api_key_header', label: 'X-API-Key Header' },
  { value: 'none', label: 'بدون مصادقة' }
]

watch(() => props.settings, (val) => {
  const sms = val || {}
  form.api_url = sms.sms_api_url ?? ''
  form.api_key = sms.sms_api_key ?? ''
  form.username = sms.sms_username ?? ''
  form.password = sms.sms_password ?? ''
  form.sender_id = sms.sms_sender_id ?? ''
  form.timeout = sms.sms_timeout ?? 15
  form.retries = sms.sms_retries ?? 3
  form.http_method = sms.sms_http_method ?? 'POST'
  form.content_type = sms.sms_content_type ?? 'application/json'
  form.authorization_type = sms.sms_authorization_type ?? 'bearer'
  form.custom_headers = sms.sms_custom_headers ?? {}
  if (sms.sms_provider) selectedProviderKey.value = sms.sms_provider
}, { immediate: true, deep: true })

watch(form, () => {
  const headers = {}
  headerRows.value.forEach(r => { if (r.key.trim()) headers[r.key.trim()] = r.value })
  Object.keys(form).forEach(k => {
    if (props.settings && form[k] !== props.settings[`sms_${k}`]) store.setValue('sms', `sms_${k}`, form[k])
  })
  store.setValue('sms', 'sms_custom_headers', headers)
  if (props.settings && selectedProviderKey.value !== props.settings.sms_provider) {
    store.setValue('sms', 'sms_provider', selectedProviderKey.value)
  }
}, { deep: true })

onMounted(fetchProviders)

async function fetchProviders() {
  try {
    const { data } = await api.get('/sms/providers')
    providers.value = data.data
  } catch (err) {
    toast.error('تعذر تحميل مزودي الرسائل')
  }
}

async function onProviderChange() {
  testResult.value = null
  const p = providers.value.find(x => x.key === selectedProviderKey.value)
  if (!p) return
  hasApiKey.value = p.has_api_key
  hasPassword.value = p.has_password
  // Populate connection fields from the provider row
  form.api_url = p.api_url ?? ''
  form.username = p.username ?? ''
  form.sender_id = p.sender_id ?? ''
  form.timeout = p.timeout ?? 15
  form.retries = p.retries ?? 3
  form.http_method = p.http_method ?? 'POST'
  form.content_type = p.content_type ?? 'application/json'
  form.authorization_type = p.authorization_type ?? 'bearer'
  if (p.custom_headers) {
    headerRows.value = Object.entries(p.custom_headers).map(([key, value]) => ({ key, value }))
  }
}

function selectAndEdit(p) {
  selectedProviderKey.value = p.key
  onProviderChange()
}

function resetProviderForm() {
  onProviderChange()
  testResult.value = null
  toast.info('تمت إعادة تعيين الحقول')
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  try {
    const provider = providers.value.find(x => x.key === selectedProviderKey.value)
    const { data } = await api.post(`/sms/providers/${provider.id}/test`, {})
    testResult.value = data
    if (data.success) toast.success(data.message || 'تم الاتصال بنجاح')
    else toast.error(data.message || 'فشل الاتصال')
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر اختبار الاتصال')
    testResult.value = { success: false, message: 'تعذر الوصول إلى المزود' }
  } finally {
    testing.value = false
  }
}

async function sendTestSms() {
  const phone = prompt('أدخل رقم الهاتف لإرسال رسالة الاختبار:')
  if (!phone) return
  sendingTest.value = true
  try {
    const provider = providers.value.find(x => x.key === selectedProviderKey.value)
    const { data } = await api.post(`/sms/providers/${provider.id}/test-send`, { recipient: phone })
    toast.success(data.message || 'تمت جدولة رسالة الاختبار')
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر إرسال رسالة الاختبار')
  } finally {
    sendingTest.value = false
  }
}

function handleSave() {
  savedFlash.value = true
  setTimeout(() => { savedFlash.value = false }, 2500)
  emit('save')
}
</script>

<style scoped>
.panels-stack { display: flex; flex-direction: column; gap: 20px; }
.form-section-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; font-weight: 700; color: var(--text-primary);
  padding-bottom: 8px; border-bottom: 1px solid var(--border);
}
.form-section-title i { color: var(--accent); }

.headers-editor { display: flex; flex-direction: column; gap: 8px; }
.header-row { display: grid; grid-template-columns: 1fr 1fr 36px; gap: 8px; align-items: center; }
.btn-icon {
  width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
  border-radius: var(--radius-sm); border: 1px solid var(--border); background: none;
  color: var(--danger); cursor: pointer;
}
.btn-icon:hover { background: var(--danger-bg); }

.connection-status {
  margin-top: 20px;
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-sm);
  font-size: 13px;
}
.connection-status i { font-size: 1.3rem; }
.connection-status div { display: flex; flex-direction: column; gap: 2px; }
.connection-status span { font-size: 12.5px; color: var(--text-secondary); }
.connection-status strong { font-size: 13px; }
.state-idle { background: var(--bg-subtle); color: var(--text-secondary); }
.state-success { background: var(--success-bg); color: var(--success-contrast); border: 1px solid var(--success-border); }
.state-error { background: var(--danger-bg); color: var(--danger-contrast); border: 1px solid var(--danger-border); }
.state-testing { background: var(--info-bg); color: var(--info-contrast); border: 1px solid var(--info-border); }
.test-latency, .test-code {
  background: rgba(255, 255, 255, 0.4);
  padding: 1px 8px; border-radius: var(--radius-full);
  font-size: 11.5px !important;
  width: fit-content;
}
.test-actions {
  display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap;
}
.btn-sm { padding: 7px 14px; font-size: 12.5px; }

.provider-list { display: flex; flex-direction: column; gap: 10px; }
.provider-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
}
.provider-row.default { border-color: var(--accent); background: var(--accent-light); }
.provider-identity { display: flex; align-items: center; gap: 12px; }
.provider-avatar {
  width: 38px; height: 38px; border-radius: var(--radius-sm);
  background: var(--bg-subtle); display: flex; align-items: center; justify-content: center;
  color: var(--accent);
}
.provider-identity strong { display: block; font-size: 13.5px; color: var(--text-primary); }
.provider-identity span { font-size: 12px; color: var(--text-secondary); }
.provider-actions { display: flex; align-items: center; gap: 10px; }

@media (max-width: 640px) {
  .provider-row { flex-direction: column; align-items: flex-start; gap: 10px; }
}
</style>
