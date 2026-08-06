<template>
  <div class="panels-stack">
    <SettingsCard
      title="بوابة الرسائل النصية"
      subtitle="إعداد مزود SMS، حماية بيانات الاعتماد، واختبار الاتصال قبل الاعتماد"
      icon="pi pi-send"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>
          استخدم هذه الصفحة لضبط مزود الرسائل النصية الفعلي. كل حقل له وصف واضح حتى تعرف
          وظيفة الرابط، المصادقة، اسم المرسل، والمهلة قبل إرسال أي طلب.
        </p>
      </div>

      <div class="form-grid-2">
        <div class="field-block">
          <div class="field-title">المزود</div>
          <div class="field-subtitle">اختر مزود الرسائل الذي سيُستخدم في الإرسال</div>
          <FormField label="مزود الرسائل" forId="sms-provider" helpText="اختر المزود الذي سيتم استخدامه لإرسال الرسائل النصية">
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
        </div>

        <div class="field-block">
          <div class="field-title">اسم المرسل</div>
          <div class="field-subtitle">الاسم الذي يظهر للمستلم داخل الرسالة</div>
          <FormField label="اسم المرسل" forId="sms-sender-name" helpText="الاسم التجاري الذي يظهر لدى المستلم" :successMessage="form.sender_name ? 'سيظهر بعد الحفظ' : ''">
            <InputText
              id="sms-sender-name"
              v-model="form.sender_name"
              class="w-full"
              placeholder="AqarMaster"
              @input="clearError('sender_name')"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">معرّف المرسل</div>
          <div class="field-subtitle">الرمز المعتمد لدى المزود لتمييز الجهة المرسلة</div>
          <FormField label="معرّف المرسل" forId="sms-sender-id" helpText="الرمز أو الرقم الذي يعتمد عليه المزود لتعريف الجهة المرسلة">
            <InputText
              id="sms-sender-id"
              v-model="form.sender_id"
              class="w-full"
              placeholder="AQAR-01"
              @input="clearError('sender_id')"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">عنوان API</div>
          <div class="field-subtitle">الرابط الذي يستقبل طلبات الإرسال من النظام</div>
          <FormField label="عنوان API" forId="sms-api-url" helpText="الرابط الذي يستقبل طلبات الإرسال من النظام" :errorMessage="errors.api_url">
            <InputText
              id="sms-api-url"
              v-model="form.api_url"
              class="w-full"
              dir="ltr"
              placeholder="https://api.provider.com/sms/send"
              @input="clearError('api_url')"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">نوع المصادقة</div>
          <div class="field-subtitle">كيف يتم التحقق من هوية النظام عند الطلب</div>
          <FormField label="نوع المصادقة" forId="sms-auth-type" helpText="طريقة التحقق التي يطلبها المزود">
            <Select
              id="sms-auth-type"
              v-model="form.authorization_type"
              :options="authTypes"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">مفتاح API</div>
          <div class="field-subtitle">القيمة السرية التي تربط النظام بالمزود</div>
          <FormField label="مفتاح API" forId="sms-api-key" helpText="المفتاح السري المستخدم لتوثيق الطلبات">
            <InputText
              id="sms-api-key"
              v-model="form.api_key"
              class="w-full"
              dir="ltr"
              type="password"
              :placeholder="hasApiKey ? '**********' : 'أدخل مفتاح API'"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">اسم المستخدم</div>
          <div class="field-subtitle">اسم الحساب المعتمد لدى مزود الخدمة</div>
          <FormField label="اسم المستخدم" forId="sms-username" helpText="اسم الحساب الذي يحدده مزود الخدمة">
            <InputText
              id="sms-username"
              v-model="form.username"
              class="w-full"
              dir="ltr"
              placeholder="provider-user"
              @input="clearError('username')"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">كلمة المرور</div>
          <div class="field-subtitle">اتركها فارغة إذا كنت لا تريد تغيير القيمة الحالية</div>
          <FormField label="كلمة المرور" forId="sms-password" helpText="اتركها فارغة إذا كنت لا تريد تغيير القيمة الحالية">
            <InputText
              id="sms-password"
              v-model="form.password"
              class="w-full"
              dir="ltr"
              type="password"
              :placeholder="hasPassword ? '**********' : 'أدخل كلمة المرور'"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">مهلة الاتصال</div>
          <div class="field-subtitle">أقصى مدة بالثواني قبل اعتبار الطلب فاشلاً</div>
          <FormField label="مهلة الاتصال" forId="sms-timeout" helpText="أقصى مدة بالثواني قبل اعتبار الطلب فاشلاً" :errorMessage="errors.timeout">
            <InputNumber
              id="sms-timeout"
              v-model="form.timeout"
              class="w-full"
              :min="1"
              :max="120"
              @blur="clearError('timeout')"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">عدد المحاولات</div>
          <div class="field-subtitle">كم مرة يعيد النظام المحاولة عند الفشل</div>
          <FormField label="عدد المحاولات" forId="sms-retries" helpText="عدد مرات إعادة المحاولة عند فشل الطلب" :errorMessage="errors.retries">
            <InputNumber
              id="sms-retries"
              v-model="form.retries"
              class="w-full"
              :min="0"
              :max="10"
              @blur="clearError('retries')"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">طريقة HTTP</div>
          <div class="field-subtitle">نوع الطلب المستخدم عند التواصل مع المزود</div>
          <FormField label="طريقة HTTP" forId="sms-http-method" helpText="نوع الطلب المستخدم عند التواصل مع المزود">
            <Select
              id="sms-http-method"
              v-model="form.http_method"
              :options="httpMethods"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </FormField>
        </div>

        <div class="field-block">
          <div class="field-title">نوع المحتوى</div>
          <div class="field-subtitle">صيغة البيانات التي يرسلها النظام في الطلب</div>
          <FormField label="نوع المحتوى" forId="sms-content-type" helpText="صيغة البيانات المرسلة داخل الطلب">
            <Select
              id="sms-content-type"
              v-model="form.content_type"
              :options="contentTypes"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </FormField>
        </div>
      </div>

      <div class="headers-box">
        <div class="headers-title">
          <div>
            <strong>رؤوس HTTP المخصصة</strong>
            <p>أضف أي رأس إضافي يطلبه المزود مثل `X-API-Key` أو رموز التفويض الخاصة.</p>
          </div>
          <button class="btn-secondary btn-sm" @click="headerRows.push({ key: '', value: '' })">
            <i class="pi pi-plus"></i>
            <span>إضافة رأس</span>
          </button>
        </div>

        <div class="headers-list">
          <div v-for="(row, idx) in headerRows" :key="idx" class="header-row">
            <InputText v-model="row.key" class="w-full" dir="ltr" placeholder="X-Custom-Header" />
            <InputText v-model="row.value" class="w-full" dir="ltr" placeholder="القيمة" />
            <button class="btn-icon" @click="headerRows.splice(idx, 1)" title="حذف">
              <i class="pi pi-trash"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="connection-status" :class="connectionState">
        <div v-if="testing" class="status-content">
          <i class="pi pi-spin pi-spinner"></i>
          <div>
            <strong>جارٍ اختبار الاتصال</strong>
            <span>نرسل طلباً تجريبياً إلى المزود للتأكد من صحة الاعتماد.</span>
          </div>
        </div>
        <template v-else-if="testResult">
          <i :class="testResult.success ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
          <div>
            <strong>{{ testResult.success ? 'الاتصال يعمل بشكل صحيح' : 'تعذر الوصول إلى المزود' }}</strong>
            <span>{{ testResult.message }}</span>
            <span v-if="testResult.latency_ms" class="meta-chip">زمن الاستجابة: {{ testResult.latency_ms }}ms</span>
            <span v-if="testResult.code" class="meta-chip">HTTP {{ testResult.code }}</span>
          </div>
        </template>
        <template v-else>
          <i class="pi pi-info-circle"></i>
          <div>
            <strong>حالة الاتصال غير مختبرة بعد</strong>
            <span>اختبر إعدادات المزود قبل الاعتماد عليها في التذكيرات والتنبيهات.</span>
          </div>
        </template>
      </div>

      <div class="action-row">
        <button class="btn-secondary" :disabled="testing" @click="testConnection">
          <i v-if="testing" class="pi pi-spin pi-spinner"></i>
          <i v-else class="pi pi-bolt"></i>
          <span>اختبار الاتصال</span>
        </button>
        <button class="btn-secondary" :disabled="sendingTest" @click="sendTestSms">
          <i v-if="sendingTest" class="pi pi-spin pi-spinner"></i>
          <i v-else class="pi pi-phone"></i>
          <span>إرسال رسالة تجريبية</span>
        </button>
        <button class="btn-secondary" @click="resetProviderForm">
          <i class="pi pi-undo"></i>
          <span>إعادة تعيين</span>
        </button>
      </div>
    </SettingsCard>

    <SettingsCard
      title="المزودون المسجلون"
      subtitle="قائمة مزودات الرسائل المتاحة داخل النظام"
      icon="pi pi-list"
      icon-tone="green"
      :show-footer="false"
    >
      <div class="provider-list">
        <div v-for="provider in providers" :key="provider.id" class="provider-row" :class="{ default: provider.is_default }">
          <div class="provider-identity">
            <div class="provider-avatar"><i class="pi pi-server"></i></div>
            <div>
              <strong>{{ provider.name }}</strong>
              <span>{{ provider.key }} · {{ provider.is_default ? 'المزود الافتراضي' : 'مزود احتياطي' }}</span>
            </div>
          </div>
          <div class="provider-actions">
            <Tag :value="provider.is_active ? 'نشط' : 'معطل'" :severity="provider.is_active ? 'success' : 'secondary'" />
            <button class="btn-secondary btn-sm" @click="selectAndEdit(provider)">
              <i class="pi pi-pencil"></i>
              <span>تعديل</span>
            </button>
          </div>
        </div>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
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
const headerRows = ref([])
const hasApiKey = ref(false)
const hasPassword = ref(false)
const savedFlash = ref(false)
const testing = ref(false)
const sendingTest = ref(false)
const testResult = ref(null)

const form = reactive({
  api_url: '',
  api_key: '',
  username: '',
  password: '',
  sender_name: '',
  sender_id: '',
  timeout: 15,
  retries: 3,
  http_method: 'POST',
  content_type: 'application/json',
  authorization_type: 'bearer'
})

const errors = reactive({
  sender_id: '',
  sender_name: '',
  api_url: '',
  timeout: '',
  retries: ''
})

const contentTypes = [
  { value: 'application/json', label: 'JSON' },
  { value: 'application/x-www-form-urlencoded', label: 'URL Encoded' },
  { value: 'multipart/form-data', label: 'Multipart' },
  { value: 'text/xml', label: 'XML' }
]

const httpMethods = [
  { value: 'POST', label: 'POST' },
  { value: 'GET', label: 'GET' },
  { value: 'PUT', label: 'PUT' }
]

const authTypes = [
  { value: 'bearer', label: 'Bearer Token' },
  { value: 'basic', label: 'Basic Auth' },
  { value: 'api_key_header', label: 'X-API-Key Header' },
  { value: 'none', label: 'بدون مصادقة' }
]

const connectionState = computed(() => {
  if (testing.value) return 'state-testing'
  if (testResult.value) return testResult.value.success ? 'state-success' : 'state-error'
  return 'state-idle'
})

function populateFormFromSettings(val) {
  const sms = val || {}
  form.api_url = sms.sms_api_url ?? ''
  form.api_key = sms.sms_api_key ?? ''
  form.username = sms.sms_username ?? ''
  form.password = sms.sms_password ?? ''
  form.sender_name = sms.sms_sender_name ?? ''
  form.sender_id = sms.sms_sender_id ?? ''
  form.timeout = sms.sms_timeout ?? 15
  form.retries = sms.sms_retries ?? 3
  form.http_method = sms.sms_http_method ?? 'POST'
  form.content_type = sms.sms_content_type ?? 'application/json'
  form.authorization_type = sms.sms_authorization_type ?? 'bearer'
  headerRows.value = Object.entries(sms.sms_custom_headers || {}).map(([key, value]) => ({ key, value }))
  if (sms.sms_provider) selectedProviderKey.value = sms.sms_provider
}

function pushFormToStore() {
  const headers = {}
  headerRows.value.forEach(row => {
    const key = String(row.key || '').trim()
    if (key) headers[key] = row.value
  })

  const mapping = {
    api_url: form.api_url,
    api_key: form.api_key,
    username: form.username,
    password: form.password,
    sender_name: form.sender_name,
    sender_id: form.sender_id,
    timeout: form.timeout,
    retries: form.retries,
    http_method: form.http_method,
    content_type: form.content_type,
    authorization_type: form.authorization_type
  }

  Object.entries(mapping).forEach(([key, value]) => {
    if (!props.settings || value !== props.settings[`sms_${key}`]) {
      store.setValue('sms', `sms_${key}`, value)
    }
  })

  store.setValue('sms', 'sms_custom_headers', headers)

  if (!props.settings || selectedProviderKey.value !== props.settings.sms_provider) {
    store.setValue('sms', 'sms_provider', selectedProviderKey.value)
  }
}

watch(() => props.settings, (val) => {
  populateFormFromSettings(val)
}, { immediate: true, deep: true })

watch([form, headerRows, selectedProviderKey], () => {
  pushFormToStore()
}, { deep: true })

watch([providers, selectedProviderKey], () => {
  const provider = providers.value.find(item => item.key === selectedProviderKey.value)
  if (!provider) return
  hasApiKey.value = !!provider.has_api_key
  hasPassword.value = !!provider.has_password
}, { immediate: true, deep: true })

onMounted(fetchProviders)

async function fetchProviders() {
  try {
    const { data } = await api.get('/sms/providers')
    providers.value = data.data || []
    if (selectedProviderKey.value) {
      applyProviderByKey(selectedProviderKey.value, false)
    }
  } catch (err) {
    toast.error('تعذر تحميل مزودي الرسائل')
  }
}

function clearError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(key => { errors[key] = '' })

  if (form.api_url && !/^https?:\/\//.test(form.api_url)) {
    errors.api_url = 'يجب أن يبدأ عنوان API بـ http:// أو https://'
    ok = false
  }
  if (form.timeout !== null && form.timeout !== undefined && (form.timeout < 1 || form.timeout > 120)) {
    errors.timeout = 'يجب أن تكون المهلة بين 1 و 120 ثانية'
    ok = false
  }
  if (form.retries !== null && form.retries !== undefined && (form.retries < 0 || form.retries > 10)) {
    errors.retries = 'يجب أن يكون عدد المحاولات بين 0 و 10'
    ok = false
  }

  return ok
}

function applyProviderByKey(key, syncListSelection = true) {
  const provider = providers.value.find(item => item.key === key)
  if (!provider) return false

  selectedProviderKey.value = key
  hasApiKey.value = !!provider.has_api_key
  hasPassword.value = !!provider.has_password

  form.api_url = provider.api_url ?? form.api_url
  form.username = provider.username ?? form.username
  form.sender_name = provider.sender_name ?? form.sender_name
  form.sender_id = provider.sender_id ?? form.sender_id
  form.timeout = provider.timeout ?? form.timeout
  form.retries = provider.retries ?? form.retries
  form.http_method = provider.http_method ?? form.http_method
  form.content_type = provider.content_type ?? form.content_type
  form.authorization_type = provider.authorization_type ?? form.authorization_type
  headerRows.value = Object.entries(provider.custom_headers || {}).map(([headerKey, value]) => ({ key: headerKey, value }))

  if (syncListSelection) {
    pushFormToStore()
  }

  return true
}

function onProviderChange() {
  testResult.value = null
  applyProviderByKey(selectedProviderKey.value)
}

function selectAndEdit(provider) {
  selectedProviderKey.value = provider.key
  onProviderChange()
}

function resetProviderForm() {
  const restored = applyProviderByKey(selectedProviderKey.value, false)
  if (!restored) {
    populateFormFromSettings(props.settings)
  }
  testResult.value = null
  toast.info('تمت إعادة تعيين الحقول')
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  try {
    const provider = providers.value.find(item => item.key === selectedProviderKey.value)
    if (!provider) throw new Error('no-provider')
    await saveProvider()
    const { data } = await api.post(`/sms/providers/${provider.id}/test`, {})
    testResult.value = data
    if (data.success) {
      toast.success(data.message || 'الاتصال يعمل بشكل صحيح')
    } else {
      toast.error(data.message || 'تعذر الوصول إلى المزود')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر اختبار الاتصال')
    testResult.value = { success: false, message: 'تعذر الوصول إلى المزود' }
  } finally {
    testing.value = false
  }
}

async function sendTestSms() {
  const phone = prompt('أدخل رقم الهاتف لإرسال رسالة تجريبية:')
  if (!phone) return

  sendingTest.value = true
  try {
    const provider = providers.value.find(item => item.key === selectedProviderKey.value)
    if (!provider) throw new Error('no-provider')
    const { data } = await api.post(`/sms/providers/${provider.id}/test-send`, { recipient: phone })
    toast.success(data.message || 'تم إرسال الرسالة التجريبية')
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر إرسال الرسالة التجريبية')
  } finally {
    sendingTest.value = false
  }
}

async function handleSave() {
  if (!validateForm()) return
  try {
    await saveProvider()
    savedFlash.value = true
    setTimeout(() => { savedFlash.value = false }, 2500)
    emit('save')
  } catch (err) {
    // Toast is already handled in saveProvider.
  }
}

async function saveProvider() {
  const provider = providers.value.find(item => item.key === selectedProviderKey.value)
  if (!provider) return

  const headers = {}
  headerRows.value.forEach(row => {
    const key = String(row.key || '').trim()
    if (key) headers[key] = row.value
  })

  const payload = {
    api_url: form.api_url,
    username: form.username,
    sender_name: form.sender_name,
    sender_id: form.sender_id,
    timeout: form.timeout,
    retries: form.retries,
    http_method: form.http_method,
    content_type: form.content_type,
    authorization_type: form.authorization_type,
    custom_headers: headers
  }

  if (form.api_key) payload.api_key = form.api_key
  if (form.password) payload.password = form.password

  try {
    await api.put(`/sms/providers/${provider.id}`, payload)
    toast.success('تم حفظ إعدادات المزود بنجاح')
    await fetchProviders()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ إعدادات المزود')
    throw err
  }
}
</script>

<style scoped>
.panels-stack {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-note {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  background: var(--bg-subtle);
  border: 1px solid var(--border);
}

.section-note i {
  color: var(--info-contrast);
  font-size: 1rem;
  margin-top: 2px;
}

.section-note p {
  margin: 0;
  font-size: 12.5px;
  line-height: 1.8;
  color: var(--text-secondary);
}

.form-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.field-block {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 14px 16px;
  border: 1px solid var(--border-light);
  border-radius: 18px;
  background: var(--bg-surface);
}

.field-title {
  font-size: 14px;
  font-weight: 800;
  color: var(--text-primary);
}

.field-subtitle {
  font-size: 12px;
  line-height: 1.7;
  color: var(--text-secondary);
}

.headers-box {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 4px;
}

.headers-title {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.headers-title strong {
  display: block;
  font-size: 13px;
  color: var(--text-primary);
}

.headers-title p {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--text-secondary);
}

.headers-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.header-row {
  display: grid;
  grid-template-columns: 1fr 1fr 36px;
  gap: 8px;
  align-items: center;
}

.btn-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: none;
  color: var(--danger);
  cursor: pointer;
}

.btn-icon:hover {
  background: var(--danger-bg);
}

.btn-sm {
  padding: 7px 14px;
  font-size: 12.5px;
}

.connection-status {
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  font-size: 13px;
  border: 1px solid var(--border);
}

.connection-status i {
  font-size: 1.3rem;
}

.status-content,
.connection-status div {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.connection-status span {
  font-size: 12.5px;
  color: var(--text-secondary);
}

.connection-status strong {
  font-size: 13px;
}

.state-idle {
  background: var(--bg-subtle);
  color: var(--text-secondary);
}

.state-success {
  background: var(--success-bg);
  color: var(--success-contrast);
  border-color: var(--success-border);
}

.state-error {
  background: var(--danger-bg);
  color: var(--danger-contrast);
  border-color: var(--danger-border);
}

.state-testing {
  background: var(--info-bg);
  color: var(--info-contrast);
  border-color: var(--info-border);
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  background: rgba(255, 255, 255, 0.35);
  color: inherit !important;
  font-size: 11.5px !important;
}

.action-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 8px;
}

.provider-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.provider-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--bg-surface);
}

.provider-row.default {
  border-color: var(--accent);
  background: var(--accent-light);
}

.provider-identity {
  display: flex;
  align-items: center;
  gap: 12px;
}

.provider-avatar {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  background: var(--bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
}

.provider-identity strong {
  display: block;
  font-size: 13.5px;
  color: var(--text-primary);
}

.provider-identity span {
  font-size: 12px;
  color: var(--text-secondary);
}

.provider-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

@media (max-width: 900px) {
  .form-grid-2,
  .header-row {
    grid-template-columns: 1fr;
  }

  .provider-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
