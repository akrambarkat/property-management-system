<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات العقود"
      subtitle="الترقيم، التذكير، وسياسات التجديد"
      icon="pi pi-file-edit"
      icon-tone="purple"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>استخدم هذه القيم لتوحيد طريقة إنشاء العقود وتنبيه الأطراف قبل انتهاء المدة. كل ما هو غير ضروري تم حذفه لتبقى اللوحة خفيفة.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">بادئة العقود</div>
          <div class="field-subtitle">تُضاف قبل رقم العقد الرسمي</div>
          <FormField label="بادئة العقود" forId="ctr-prefix" :errorMessage="errors.contract_prefix">
            <InputText id="ctr-prefix" v-model="form.contract_prefix" class="w-full" placeholder="CTR-2026-" @input="clearError('contract_prefix')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">أيام التذكير قبل الانتهاء</div>
          <div class="field-subtitle">عدد الأيام قبل نهاية العقد لإرسال تنبيه</div>
          <FormField label="أيام التذكير قبل الانتهاء" forId="ctr-remind" :errorMessage="errors.contract_reminder_days">
            <InputNumber id="ctr-remind" v-model="form.contract_reminder_days" class="w-full" :min="0" :max="365" @blur="clearError('contract_reminder_days')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">التجديد التلقائي</div>
          <div class="field-subtitle">تجديد العقد تلقائياً عند انتهاء مدته</div>
          <FormField label="التجديد التلقائي" forId="ctr-auto">
            <Select
              id="ctr-auto"
              :model-value="form.contract_auto_renew ? 'yes' : 'no'"
              :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              @change="form.contract_auto_renew = $event.value === 'yes'"
            />
          </FormField>
        </div>
      </div>

      <div class="field-card">
        <div class="field-title">شروط العقد الافتراضية</div>
        <div class="field-subtitle">النص الذي يضاف تلقائياً إلى العقود الجديدة</div>
        <FormField label="شروط العقد الافتراضية" forId="ctr-terms">
          <Textarea id="ctr-terms" v-model="form.contract_terms" rows="5" class="w-full" placeholder="الشروط والأحكام الافتراضية المضمنة في العقود" />
        </FormField>
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
const errors = reactive({ contract_prefix: '', contract_reminder_days: '' })

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('contracts', k, val[k])
  })
}, { deep: true })

function clearError(field) { if (errors[field]) errors[field] = '' }

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (form.contract_prefix && form.contract_prefix.length > 20) { errors.contract_prefix = 'الحد الأقصى 20 حرفاً'; ok = false }
  if (form.contract_reminder_days !== null && form.contract_reminder_days !== undefined && (form.contract_reminder_days < 0 || form.contract_reminder_days > 365)) {
    errors.contract_reminder_days = 'يجب أن يكون بين 0 و 365'; ok = false
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
.section-note i { color: var(--accent); font-size: 1rem; margin-top: 2px; }
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
