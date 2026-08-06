<template>
  <div class="panels-stack">
    <SettingsCard
      title="الأمان"
      subtitle="سياسات كلمات المرور، مدة الجلسة، والحدود الأمنية"
      icon="pi pi-shield"
      icon-tone="red"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-shield"></i>
        <p>هذه القيم تحدد الحد الأدنى للأمان داخل النظام. لا ترفع القيود أكثر من الحاجة حتى لا تعيق المستخدمين، ولا تخفضها أكثر من اللازم.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">الحد الأدنى لطول كلمة المرور</div>
          <div class="field-subtitle">عدد الأحرف المطلوب لكلمة مرور جديدة</div>
          <FormField label="الحد الأدنى لطول كلمة المرور" forId="sec-min">
            <InputNumber id="sec-min" v-model="form.password_min_length" class="w-full" :min="6" :max="64" @blur="clearError('password_min_length')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">مدة الجلسة</div>
          <div class="field-subtitle">عدد الدقائق قبل تسجيل الخروج التلقائي</div>
          <FormField label="مدة الجلسة" forId="sec-session">
            <InputNumber id="sec-session" v-model="form.session_timeout" class="w-full" :min="1" :max="1440" @blur="clearError('session_timeout')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">عدد محاولات الدخول المسموح بها</div>
          <div class="field-subtitle">عدد المحاولات الفاشلة قبل تقييد الحساب</div>
          <FormField label="عدد محاولات الدخول المسموح بها" forId="sec-attempts">
            <InputNumber id="sec-attempts" v-model="form.lockout_attempts" class="w-full" :min="1" :max="20" @blur="clearError('lockout_attempts')" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">المصادقة الثنائية</div>
          <div class="field-subtitle">تفعيل خطوة تحقق إضافية عند تسجيل الدخول</div>
          <FormField label="المصادقة الثنائية" forId="sec-2fa">
            <Select
              id="sec-2fa"
              :model-value="form.two_factor ? 'yes' : 'no'"
              :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              @change="form.two_factor = $event.value === 'yes'"
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
const errors = reactive({ password_min_length: '', session_timeout: '', lockout_attempts: '' })

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('security', k, val[k])
  })
}, { deep: true })

function clearError(field) { if (errors[field]) errors[field] = '' }

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (form.password_min_length !== null && form.password_min_length !== undefined && (form.password_min_length < 6 || form.password_min_length > 64)) {
    errors.password_min_length = 'يجب أن يكون بين 6 و 64'
    ok = false
  }
  if (form.session_timeout !== null && form.session_timeout !== undefined && (form.session_timeout < 1 || form.session_timeout > 1440)) {
    errors.session_timeout = 'يجب أن يكون بين 1 و 1440 دقيقة'
    ok = false
  }
  if (form.lockout_attempts !== null && form.lockout_attempts !== undefined && (form.lockout_attempts < 1 || form.lockout_attempts > 20)) {
    errors.lockout_attempts = 'يجب أن يكون بين 1 و 20'
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
.section-note i { color: var(--danger-contrast); font-size: 1rem; margin-top: 2px; }
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
