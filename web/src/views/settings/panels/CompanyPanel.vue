<template>
  <div class="panels-stack">
    <SettingsCard
      title="بيانات الشركة"
      subtitle="معلومات الشركة الأساسية التي تظهر في الفواتير والتقارير"
      icon="pi pi-building"
      icon-tone="blue"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="اسم الشركة" required forId="co-name" helpText="يظهر في الترويسات والتقارير">
          <InputText id="co-name" v-model="form.company_name" class="w-full" placeholder="شركة الإعمار للعقارات" />
        </FormField>

        <FormField label="الرقم الضريبي" forId="co-tax" helpText="رقم التسجيل الضريبي للشركة">
          <InputText id="co-tax" v-model="form.tax_number" class="w-full" />
        </FormField>

        <FormField label="السجل التجاري" forId="co-reg">
          <InputText id="co-reg" v-model="form.commercial_registration" class="w-full" />
        </FormField>

        <FormField label="الهاتف الثابت" forId="co-phone">
          <InputText id="co-phone" v-model="form.phone" class="w-full" />
        </FormField>

        <FormField label="الجوال" forId="co-mobile">
          <InputText id="co-mobile" v-model="form.mobile" class="w-full" />
        </FormField>

        <FormField label="البريد الإلكتروني" forId="co-email">
          <InputText id="co-email" v-model="form.email" class="w-full" type="email" />
        </FormField>

        <FormField label="الموقع الإلكتروني" forId="co-website">
          <InputText id="co-website" v-model="form.website" class="w-full" />
        </FormField>

        <FormField label="المدينة" forId="co-city">
          <InputText id="co-city" v-model="form.city" class="w-full" />
        </FormField>

        <FormField label="الدولة" forId="co-country">
          <InputText id="co-country" v-model="form.country" class="w-full" />
        </FormField>

        <FormField label="المنطقة الزمنية" forId="co-tz">
          <Select
            id="co-tz"
            v-model="form.timezone"
            :options="timezones"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            filter
          />
        </FormField>

        <FormField label="صيغة التاريخ" forId="co-datefmt">
          <Select
            id="co-datefmt"
            v-model="form.date_format"
            :options="dateFormats"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="اللغة" forId="co-lang">
          <Select
            id="co-lang"
            v-model="form.language"
            :options="[{ value: 'ar', label: 'العربية' }, { value: 'en', label: 'English' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>
      </div>

      <div class="form-section-title" style="margin-top: 24px;">
        <i class="pi pi-percentage"></i>
        <span>الضرائب وشروط الدفع</span>
      </div>
      <div class="form-grid-2" style="margin-top: 14px;">
        <FormField label="نسبة الضريبة الافتراضية (٪)" forId="co-vat" helpText="نسبة ضريبة القيمة المضافة الافتراضية">
          <InputNumber id="co-vat" v-model="form.default_vat" class="w-full" :min="0" :max="100" :maxFractionDigits="2" suffix=" %" />
        </FormField>

        <FormField label="شروط الدفع الافتراضية" forId="co-terms">
          <InputText id="co-terms" v-model="form.payment_terms" class="w-full" placeholder="الدفع خلال 30 يومًا" />
        </FormField>
      </div>

      <div class="form-section-title" style="margin-top: 24px;">
        <i class="pi pi-paperclip"></i>
        <span>ترويسة وتذييل الفاتورة</span>
      </div>
      <div class="form-field" style="margin-top: 14px;">
        <label for="co-header">ترويسة الفاتورة</label>
        <Textarea id="co-header" v-model="form.invoice_header" rows="2" class="w-full" placeholder="نص يظهر أعلى الفاتورة" />
      </div>
      <div class="form-field" style="margin-top: 14px;">
        <label for="co-footer">تذييل الفاتورة</label>
        <Textarea id="co-footer" v-model="form.invoice_footer" rows="2" class="w-full" placeholder="شكرًا لتعاملكم معنا" />
      </div>
    </SettingsCard>

    <SettingsCard
      title="الشعار والختم"
      subtitle="رفع شعار الشركة وختمها للاستخدام في الفواتير المطبوعة"
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
            {{ form.logo_path ? 'تغيير الشعار' : 'رفع الشعار' }}
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
            {{ form.stamp_path ? 'تغيير الختم' : 'رفع الختم' }}
          </button>
          <input ref="stampInput" type="file" accept="image/*" class="hidden-input" @change="onStampChange" />
        </div>
      </div>
      <div class="media-note">
        <i class="pi pi-info-circle"></i>
        <span>يُفضل صورًا شفافة بصيغة PNG بأبعاد 512×512 أو أكثر.</span>
      </div>
    </SettingsCard>

    <SettingsCard
      title="معاينة الفاتورة"
      subtitle="معاينة مباشرة لترويسة الفاتورة مع بيانات الشركة"
      icon="pi pi-eye"
      icon-tone="amber"
      :show-footer="false"
    >
      <div class="invoice-preview">
        <div class="inv-head">
          <div class="inv-brand">
            <img v-if="logoPreview" :src="logoPreview" class="inv-logo" alt="الشعار" />
            <i v-else class="pi pi-building inv-logo-fallback"></i>
            <div>
              <strong>{{ form.company_name || 'اسم الشركة' }}</strong>
              <span>{{ form.email }}</span>
            </div>
          </div>
          <div class="inv-meta">
            <span>فاتورة #{{ previewInvoiceNumber }}</span>
            <span>{{ previewDate }}</span>
          </div>
        </div>
        <div v-if="form.invoice_header" class="inv-header-text">{{ form.invoice_header }}</div>
        <div class="inv-rows">
          <div class="inv-row"><span>وصف الخدمة</span><span>المبلغ</span></div>
          <div class="inv-row"><span>إيجار شهر {{ previewMonth }}</span><span>{{ formatAmount(1000) }}</span></div>
        </div>
        <div class="inv-total">
          <span>الإجمالي شاملاً الضريبة</span>
          <strong>{{ formatAmount(1170) }}</strong>
        </div>
        <div class="inv-tax" v-if="Number(form.default_vat) > 0">
          <span>ضريبة القيمة المضافة ({{ form.default_vat }}٪)</span>
          <span>{{ formatAmount(170) }}</span>
        </div>
        <div class="inv-stamp-row">
          <img v-if="stampPreview" :src="stampPreview" class="inv-stamp" alt="الختم" />
          <span v-else class="stamp-placeholder">الختم</span>
          <div class="inv-sign">
            <span>التوقيع والختم</span>
          </div>
        </div>
        <div v-if="form.invoice_footer" class="inv-footer">{{ form.invoice_footer }}</div>
        <div v-if="form.company_name" class="inv-stamp-note">{{ form.company_name }} — {{ form.tax_number ? `ضريبة: ${form.tax_number}` : '' }}</div>
      </div>
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
  { value: 'Asia/Doha', label: '(UTC+3) الدوحة' },
  { value: 'Africa/Cairo', label: '(UTC+2) القاهرة' },
  { value: 'Europe/London', label: '(UTC+0) لندن' }
]

const dateFormats = [
  { value: 'd/m/Y', label: '31/12/2026' },
  { value: 'd-m-Y', label: '31-12-2026' },
  { value: 'Y-m-d', label: '2026-12-31' },
  { value: 'd M Y', label: '31 ديسمبر 2026' }
]

const previewInvoiceNumber = computed(() => `${form.invoice_prefix || 'INV'}-2026-0001`)
const previewDate = computed(() => new Date().toLocaleDateString('ar-EG', { year: 'numeric', month: 'long', day: 'numeric' }))
const previewMonth = computed(() => new Date().toLocaleDateString('ar-EG', { month: 'long' }))

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
  reader.onload = () => {
    logoPreview.value = reader.result
    store.setValue('company', 'logo_path', reader.result)
  }
  reader.readAsDataURL(file)
}

function onStampChange(e) {
  const file = e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    stampPreview.value = reader.result
    store.setValue('company', 'stamp_path', reader.result)
  }
  reader.readAsDataURL(file)
}

function formatAmount(n) {
  return new Intl.NumberFormat('ar-EG').format(n) + ' ₪'
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

.media-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.media-upload { display: flex; flex-direction: column; align-items: center; gap: 12px; }
.media-preview {
  width: 160px; height: 160px;
  border: 2px dashed var(--border);
  border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  background: var(--bg-subtle);
  overflow: hidden;
}
.media-preview img { width: 100%; height: 100%; object-fit: contain; }
.media-preview.empty i { font-size: 2.5rem; color: var(--text-muted); }
.hidden-input { display: none; }
.media-note {
  display: flex; align-items: center; gap: 8px;
  margin-top: 16px; font-size: 12.5px; color: var(--text-secondary);
  background: var(--bg-subtle); padding: 10px 14px; border-radius: var(--radius-sm);
}
.btn-sm { padding: 7px 14px; font-size: 12.5px; }

.invoice-preview {
  background: var(--bg-surface-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 24px;
  max-width: 640px;
  margin: 0 auto;
  font-family: 'Cairo', sans-serif;
}
.inv-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
.inv-brand { display: flex; align-items: center; gap: 12px; }
.inv-logo { width: 48px; height: 48px; object-fit: contain; }
.inv-logo-fallback { font-size: 2rem; color: var(--accent); }
.inv-brand strong { display: block; font-size: 15px; color: var(--text-primary); }
.inv-brand span { font-size: 12px; color: var(--text-secondary); }
.inv-meta { display: flex; flex-direction: column; align-items: flex-end; font-size: 12.5px; color: var(--text-secondary); gap: 4px; }
.inv-header-text { background: var(--bg-subtle); padding: 10px; border-radius: var(--radius-xs); margin-bottom: 16px; font-size: 12.5px; }
.inv-rows { border: 1px solid var(--border); border-radius: var(--radius-xs); overflow: hidden; margin-bottom: 14px; }
.inv-row { display: flex; justify-content: space-between; padding: 10px 14px; font-size: 13px; }
.inv-row:first-child { background: var(--bg-subtle); font-weight: 700; }
.inv-row + .inv-row { border-top: 1px solid var(--border-light); }
.inv-total { display: flex; justify-content: space-between; padding: 10px 14px; font-weight: 700; border-top: 2px solid var(--accent); margin-bottom: 4px; }
.inv-tax { display: flex; justify-content: space-between; padding: 4px 14px; font-size: 12.5px; color: var(--text-secondary); }
.inv-stamp-row { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; }
.inv-stamp { width: 80px; height: 80px; object-fit: contain; opacity: 0.9; }
.stamp-placeholder { width: 80px; height: 80px; border: 1px dashed var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 12px; }
.inv-sign { font-size: 12px; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 6px; }
.inv-footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-secondary); }
.inv-stamp-note { margin-top: 12px; text-align: center; font-size: 11px; color: var(--text-muted); }

@media (max-width: 640px) {
  .media-grid { grid-template-columns: 1fr; }
}
</style>
