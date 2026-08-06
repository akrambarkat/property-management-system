<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات الفواتير"
      subtitle="الترقيم، الضرائب، وسياسات الإصدار"
      icon="pi pi-file-invoice"
      icon-tone="blue"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>هذه القيم تتحكم في شكل الفاتورة وسلوكها. تأكد من توافقها مع السياسة المالية للشركة قبل الاعتماد النهائي.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">بادئة الفواتير</div>
          <div class="field-subtitle">تُضاف قبل رقم الفاتورة مثل INV-2026-0001</div>
          <FormField label="بادئة الفواتير" forId="inv-prefix" :errorMessage="errors.invoice_prefix">
            <InputText id="inv-prefix" v-model="form.invoice_prefix" class="w-full" placeholder="INV-2026-" @input="clearError('invoice_prefix')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">بادئة الإيصالات</div>
          <div class="field-subtitle">تُضاف قبل رقم الإيصال</div>
          <FormField label="بادئة الإيصالات" forId="inv-receipt-prefix" :errorMessage="errors.receipt_prefix">
            <InputText id="inv-receipt-prefix" v-model="form.receipt_prefix" class="w-full" placeholder="REC-2026-" @input="clearError('receipt_prefix')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">أيام الاستحقاق الافتراضية</div>
          <div class="field-subtitle">عدد الأيام قبل اعتبار الفاتورة مستحقة</div>
          <FormField label="أيام الاستحقاق الافتراضية" forId="inv-due" :errorMessage="errors.invoice_due_days">
            <InputNumber id="inv-due" v-model="form.invoice_due_days" class="w-full" :min="0" :max="365" @blur="clearError('invoice_due_days')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الرقم الضريبي</div>
          <div class="field-subtitle">الرقم الذي يظهر في الفواتير الرسمية</div>
          <FormField label="الرقم الضريبي" forId="inv-tax" :errorMessage="errors.invoice_tax_number">
            <InputText id="inv-tax" v-model="form.invoice_tax_number" class="w-full" placeholder="510123456" @input="clearError('invoice_tax_number')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">غرامة التأخير</div>
          <div class="field-subtitle">النسبة المضافة عند التأخر في السداد</div>
          <FormField label="غرامة التأخير" forId="inv-late" :errorMessage="errors.invoice_late_fee">
            <InputNumber id="inv-late" v-model="form.invoice_late_fee" class="w-full" :min="0" :max="100" :maxFractionDigits="2" suffix=" %" @blur="clearError('invoice_late_fee')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">إظهار الخصومات</div>
          <div class="field-subtitle">إظهار سطر الخصم داخل تفاصيل الفاتورة</div>
          <FormField label="إظهار الخصومات" forId="inv-disc">
            <Select
              id="inv-disc"
              :model-value="form.invoice_show_discount ? 'yes' : 'no'"
              :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              @change="form.invoice_show_discount = $event.value === 'yes'"
            />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">الإصدار التلقائي</div>
          <div class="field-subtitle">إنشاء الفواتير تلقائياً عند الاستحقاق الشهري</div>
          <FormField label="الإصدار التلقائي" forId="inv-auto">
            <Select
              id="inv-auto"
              :model-value="form.invoice_auto_generate ? 'yes' : 'no'"
              :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              @change="form.invoice_auto_generate = $event.value === 'yes'"
            />
          </FormField>
        </div>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import SettingsCard from '@/components/settings/SettingsCard.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])
const store = useSettingsStore()
const savedFlash = ref(false)
const form = reactive({})
const errors = reactive({ invoice_prefix: '', receipt_prefix: '', invoice_due_days: '', invoice_tax_number: '', invoice_late_fee: '' })

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('invoices', k, val[k])
  })
}, { deep: true })

function clearError(field) { if (errors[field]) errors[field] = '' }

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (form.invoice_prefix && form.invoice_prefix.length > 20) { errors.invoice_prefix = 'الحد الأقصى 20 حرفاً'; ok = false }
  if (form.receipt_prefix && form.receipt_prefix.length > 20) { errors.receipt_prefix = 'الحد الأقصى 20 حرفاً'; ok = false }
  if (form.invoice_due_days !== null && form.invoice_due_days !== undefined && (form.invoice_due_days < 0 || form.invoice_due_days > 365)) { errors.invoice_due_days = 'يجب أن يكون بين 0 و 365'; ok = false }
  if (form.invoice_late_fee !== null && form.invoice_late_fee !== undefined && form.invoice_late_fee < 0) { errors.invoice_late_fee = 'يجب أن تكون القيمة 0 أو أكبر'; ok = false }
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
.field-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 14px 16px;
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  background: var(--bg-surface);
}
.field-title { font-size: 14px; font-weight: 800; color: var(--text-primary); }
.field-subtitle { font-size: 12px; line-height: 1.7; color: var(--text-secondary); }
@media (max-width: 800px) { .form-grid-2 { grid-template-columns: 1fr; } }
</style>
