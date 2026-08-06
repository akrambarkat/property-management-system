<template>
  <div class="panels-stack">
    <SettingsCard
      title="بيانات الشركة"
      subtitle="الهوية الرسمية التي تظهر في الفواتير والعقود والتقارير"
      icon="pi pi-building"
      icon-tone="blue"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>هذه البيانات تُستخدم في المستندات الرسمية ورسائل العملاء. أي تعديل هنا ينعكس على الهوية العامة للنظام.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">اسم الشركة</div>
          <div class="field-subtitle">يظهر في رأس الفواتير والتقارير والمستندات الرسمية</div>
          <FormField label="اسم الشركة" required forId="co-name" :errorMessage="errors.company_name" :successMessage="form.company_name ? 'تم إدخال الاسم' : ''">
            <InputText id="co-name" v-model="form.company_name" class="w-full" placeholder="EMAAR Properties" @input="clearError('company_name')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الرقم الضريبي</div>
          <div class="field-subtitle">رقم التسجيل الضريبي المستخدم في الفواتير</div>
          <FormField label="الرقم الضريبي" forId="co-tax" :errorMessage="errors.tax_number">
            <InputText id="co-tax" v-model="form.tax_number" class="w-full" placeholder="510123456" @input="clearError('tax_number')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">السجل التجاري</div>
          <div class="field-subtitle">المرجع القانوني المعتمد للشركة</div>
          <FormField label="السجل التجاري" forId="co-reg" :errorMessage="errors.commercial_registration">
            <InputText id="co-reg" v-model="form.commercial_registration" class="w-full" placeholder="562344789" @input="clearError('commercial_registration')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الهاتف الثابت</div>
          <div class="field-subtitle">رقم المكتب الرئيسي للتواصل</div>
          <FormField label="الهاتف الثابت" forId="co-phone" :errorMessage="errors.phone">
            <InputText id="co-phone" v-model="form.phone" class="w-full" placeholder="02 298 1234" @input="clearError('phone')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الجوال</div>
          <div class="field-subtitle">يُستخدم في رسائل SMS والتنبيهات</div>
          <FormField label="الجوال" forId="co-mobile" :errorMessage="errors.mobile">
            <InputText id="co-mobile" v-model="form.mobile" class="w-full" placeholder="0599 000 000" @input="clearError('mobile')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">البريد الإلكتروني</div>
          <div class="field-subtitle">البريد الرسمي للتواصل والإشعارات</div>
          <FormField label="البريد الإلكتروني" forId="co-email" :errorMessage="errors.email">
            <InputText id="co-email" v-model="form.email" class="w-full" type="email" placeholder="info@company.com" @input="clearError('email')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الموقع الإلكتروني</div>
          <div class="field-subtitle">الرابط الرسمي للشركة ويجب أن يبدأ بالبروتوكول الكامل</div>
          <FormField label="الموقع الإلكتروني" forId="co-website" :errorMessage="errors.website">
            <InputText id="co-website" v-model="form.website" class="w-full" dir="ltr" placeholder="https://www.company.com" @input="clearError('website')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">المدينة</div>
          <div class="field-subtitle">مدينة المقر الرئيسي</div>
          <FormField label="المدينة" forId="co-city" :errorMessage="errors.city">
            <InputText id="co-city" v-model="form.city" class="w-full" placeholder="رام الله" @input="clearError('city')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الدولة</div>
          <div class="field-subtitle">دولة التسجيل أو المقر الرئيسي</div>
          <FormField label="الدولة" forId="co-country" :errorMessage="errors.country">
            <InputText id="co-country" v-model="form.country" class="w-full" placeholder="فلسطين" @input="clearError('country')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">المنطقة الزمنية</div>
          <div class="field-subtitle">تُستخدم في الطوابع الزمنية والإشعارات</div>
          <FormField label="المنطقة الزمنية" forId="co-tz">
            <Select id="co-tz" v-model="form.timezone" :options="timezones" optionLabel="label" optionValue="value" class="w-full" filter />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الضريبة الافتراضية (%)</div>
          <div class="field-subtitle">النسبة المضافة على الفواتير</div>
          <FormField label="الضريبة الافتراضية (%)" forId="co-vat" :errorMessage="errors.default_vat">
            <InputNumber id="co-vat" v-model="form.default_vat" class="w-full" :min="0" :max="100" :maxFractionDigits="2" suffix=" %" @blur="clearError('default_vat')" />
          </FormField>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">شروط الدفع الافتراضية</div>
          <div class="field-subtitle">النص المستخدم كمرجع افتراضي للشروط التجارية</div>
          <FormField label="شروط الدفع الافتراضية" forId="co-terms" :errorMessage="errors.payment_terms">
            <InputText id="co-terms" v-model="form.payment_terms" class="w-full" placeholder="الدفع خلال 30 يوماً" @input="clearError('payment_terms')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">ترويسة الفاتورة</div>
          <div class="field-subtitle">نص قصير يظهر أعلى الفاتورة</div>
          <FormField label="ترويسة الفاتورة" forId="co-header">
            <Textarea id="co-header" v-model="form.invoice_header" rows="3" class="w-full" placeholder="شكرًا لتعاملكم معنا" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">تذييل الفاتورة</div>
          <div class="field-subtitle">ملاحظة مختصرة تظهر أسفل الفاتورة</div>
          <FormField label="تذييل الفاتورة" forId="co-footer">
            <Textarea id="co-footer" v-model="form.invoice_footer" rows="3" class="w-full" placeholder="يرجى السداد خلال المهلة المحددة" />
          </FormField>
        </div>
      </div>
    </SettingsCard>

    <SettingsCard
      title="الشعار والختم"
      subtitle="تحميل الملفات المعتمدة لاستخدامها في المستندات المطبوعة"
      icon="pi pi-image"
      icon-tone="purple"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="media-grid">
        <div class="media-upload">
          <div class="media-preview" :class="{ empty: !logoPreview }">
            <img v-if="logoPreview" :src="logoPreview" alt="شعار الشركة" />
            <i v-else class="pi pi-image"></i>
          </div>
          <button class="btn-secondary btn-sm" @click="pickFile('logo')">
            <i class="pi pi-upload"></i>
            <span>{{ form.logo_path ? 'تغيير الشعار' : 'رفع الشعار' }}</span>
          </button>
          <input ref="logoInput" type="file" accept="image/*" class="hidden-input" @change="onLogoChange" />
        </div>

        <div class="media-upload">
          <div class="media-preview" :class="{ empty: !stampPreview }">
            <img v-if="stampPreview" :src="stampPreview" alt="ختم الشركة" />
            <i v-else class="pi pi-qrcode"></i>
          </div>
          <button class="btn-secondary btn-sm" @click="pickFile('stamp')">
            <i class="pi pi-upload"></i>
            <span>{{ form.stamp_path ? 'تغيير الختم' : 'رفع الختم' }}</span>
          </button>
          <input ref="stampInput" type="file" accept="image/*" class="hidden-input" @change="onStampChange" />
        </div>
      </div>

      <p class="media-note">
        <i class="pi pi-info-circle"></i>
        <span>يفضل استخدام ملفات PNG بخلفية شفافة وجودة عالية حتى تظهر بشكل واضح في الفواتير.</span>
      </p>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import SettingsCard from '@/components/settings/SettingsCard.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])
const store = useSettingsStore()
const form = reactive({})
const errors = reactive({
  company_name: '', tax_number: '', commercial_registration: '', phone: '',
  mobile: '', email: '', website: '', city: '', country: '', default_vat: '', payment_terms: ''
})
const savedFlash = ref(false)
const logoInput = ref(null)
const stampInput = ref(null)
const logoPreview = ref(null)
const stampPreview = ref(null)

const timezones = [
  { value: 'Asia/Jerusalem', label: '(UTC+3) القدس' },
  { value: 'Asia/Amman', label: '(UTC+3) عمّان' },
  { value: 'Asia/Riyadh', label: '(UTC+3) الرياض' },
  { value: 'Asia/Dubai', label: '(UTC+4) دبي' },
  { value: 'Asia/Kuwait', label: '(UTC+3) الكويت' },
  { value: 'Asia/Beirut', label: '(UTC+2) بيروت' },
  { value: 'Africa/Cairo', label: '(UTC+2) القاهرة' },
  { value: 'Europe/London', label: '(UTC+0) لندن' }
]

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
  if (val?.logo_path) logoPreview.value = toAssetUrl(val.logo_path)
  if (val?.stamp_path) stampPreview.value = toAssetUrl(val.stamp_path)
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('company', k, val[k])
  })
}, { deep: true })

function toAssetUrl(path) {
  if (!path) return null
  if (/^https?:\/\//.test(path)) return path
  const base = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1').replace(/\/api\/v1\/?$/, '')
  return `${base}/storage/${path.replace(/^storage\//, '')}`
}

function pickFile(type) {
  if (type === 'logo') logoInput.value?.click()
  else stampInput.value?.click()
}

function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => { logoPreview.value = reader.result; store.setValue('company', 'logo_path', reader.result) }
  reader.readAsDataURL(file)
}

function onStampChange(e) {
  const file = e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => { stampPreview.value = reader.result; store.setValue('company', 'stamp_path', reader.result) }
  reader.readAsDataURL(file)
}

function clearError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (!String(form.company_name || '').trim()) {
    errors.company_name = 'اسم الشركة مطلوب'
    ok = false
  }
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'صيغة البريد الإلكتروني غير صحيحة'
    ok = false
  }
  if (form.website && !/^https?:\/\//.test(form.website)) {
    errors.website = 'يجب أن يبدأ الرابط بـ http:// أو https://'
    ok = false
  }
  if (form.default_vat !== null && form.default_vat !== undefined && (form.default_vat < 0 || form.default_vat > 100)) {
    errors.default_vat = 'يجب أن تكون النسبة بين 0 و 100'
    ok = false
  }
  return ok
}

function handleSave() {
  if (!validateForm()) return
  savedFlash.value = true
  setTimeout(() => { savedFlash.value = false }, 2500)
  emit('save')
}
</script>

<style scoped>
.panels-stack { display: flex; flex-direction: column; gap: 20px; }
.section-note {
  display: flex; gap: 12px; padding: 14px 16px; border-radius: var(--radius-md);
  background: var(--bg-subtle); border: 1px solid var(--border);
}
.section-note i { color: var(--info-contrast); font-size: 1rem; margin-top: 2px; }
.section-note p { margin: 0; font-size: 12.5px; line-height: 1.8; color: var(--text-secondary); }
.form-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.media-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.media-upload { display: flex; flex-direction: column; align-items: center; gap: 12px; }
.media-preview {
  width: 160px; height: 160px; border: 2px dashed var(--border); border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center; background: var(--bg-subtle); overflow: hidden;
}
.media-preview img { width: 100%; height: 100%; object-fit: contain; }
.media-preview.empty i { font-size: 2.5rem; color: var(--text-muted); }
.hidden-input { display: none; }
.media-note {
  display: flex; align-items: center; gap: 8px; margin-top: 16px;
  font-size: 12.5px; color: var(--text-secondary); background: var(--bg-subtle);
  padding: 10px 14px; border-radius: var(--radius-sm);
}
.media-note i { color: var(--info); }
.btn-sm { padding: 7px 14px; font-size: 12.5px; }
@media (max-width: 800px) {
  .form-grid-2, .media-grid { grid-template-columns: 1fr; }
}
</style>
