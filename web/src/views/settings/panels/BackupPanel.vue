<template>
  <div class="panels-stack">
    <SettingsCard
      title="النسخ الاحتياطي والاستعادة"
      subtitle="جدولة النسخ، مدة الاحتفاظ، ووجهة التخزين"
      icon="pi pi-database"
      icon-tone="green"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>هذه القيم تحدد كيف تُحفظ نسخ النظام ومتى تُحذف النسخ القديمة. أبقِها بسيطة وواضحة لتسهيل الاستعادة عند الحاجة.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card"><div class="field-title">تفعيل النسخ الاحتياطي</div><div class="field-subtitle">تشغيل الجدولة التلقائية للنسخ الاحتياطي</div><FormField label="تفعيل النسخ الاحتياطي" forId="bk-enable"><Select id="bk-enable" :model-value="form.backup_enabled ? 'yes' : 'no'" :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]" optionLabel="label" optionValue="value" class="w-full" @change="form.backup_enabled = $event.value === 'yes'" /></FormField></div>
        <div class="field-card"><div class="field-title">تكرار النسخ</div><div class="field-subtitle">كم مرة يتم إنشاء نسخة جديدة</div><FormField label="تكرار النسخ" forId="bk-freq"><Select id="bk-freq" v-model="form.backup_frequency" :options="[{ value: 'daily', label: 'يومي' }, { value: 'weekly', label: 'أسبوعي' }, { value: 'monthly', label: 'شهري' }]" optionLabel="label" optionValue="value" class="w-full" /></FormField></div>
        <div class="field-card"><div class="field-title">مدة الاحتفاظ</div><div class="field-subtitle">عدد الأيام قبل حذف النسخ القديمة</div><FormField label="مدة الاحتفاظ" forId="bk-retain" :errorMessage="errors.backup_retention_days"><InputNumber id="bk-retain" v-model="form.backup_retention_days" class="w-full" :min="1" :max="365" @blur="clearError('backup_retention_days')" /></FormField></div>
        <div class="field-card"><div class="field-title">وجهة النسخ</div><div class="field-subtitle">مكان حفظ النسخة الاحتياطية</div><FormField label="وجهة النسخ" forId="bk-dest"><Select id="bk-dest" v-model="form.backup_destination" :options="[{ value: 'local', label: 'محلي' }, { value: 'cloud', label: 'سحابي' }]" optionLabel="label" optionValue="value" class="w-full" /></FormField></div>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import SettingsCard from '@/components/settings/SettingsCard.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])
const store = useSettingsStore()
const toast = useToastStore()
const savedFlash = ref(false)
const backingUp = ref(false)
const form = reactive({})
const errors = reactive({ backup_retention_days: '' })

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('backup', k, val[k])
  })
}, { deep: true })

function clearError(field) { if (errors[field]) errors[field] = '' }

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (form.backup_retention_days !== null && form.backup_retention_days !== undefined && (form.backup_retention_days < 1 || form.backup_retention_days > 365)) {
    errors.backup_retention_days = 'يجب أن يكون بين 1 و 365 يوم'
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

function runBackup() {
  backingUp.value = true
  setTimeout(() => {
    backingUp.value = false
    toast.success('تم إنشاء النسخة الاحتياطية بنجاح')
  }, 2000)
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
.field-card{display:flex;flex-direction:column;gap:8px;padding:14px 16px;border:1px solid var(--border-light);border-radius:var(--radius-md);background:var(--bg-surface);}
.field-title{font-size:14px;font-weight:800;color:var(--text-primary);}
.field-subtitle{font-size:12px;line-height:1.7;color:var(--text-secondary);}
@media (max-width: 800px) { .form-grid-2 { grid-template-columns: 1fr; } }
</style>
