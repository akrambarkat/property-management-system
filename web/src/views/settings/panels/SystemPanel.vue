<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات النظام"
      subtitle="القيود الفنية، وضع الصيانة، وحدود الرفع"
      icon="pi pi-cog"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>هذه الإعدادات يفترض أن تعدّل فقط عند الحاجة التشغيلية. أي قيمة هنا تؤثر مباشرة على سلوك المنصة وسعتها.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card"><div class="field-title">وضع التصحيح</div><div class="field-subtitle">إظهار تفاصيل الأخطاء الفنية أثناء التطوير</div><FormField label="وضع التصحيح" forId="sys-debug"><Select id="sys-debug" :model-value="form.debug_mode ? 'yes' : 'no'" :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]" optionLabel="label" optionValue="value" class="w-full" @change="form.debug_mode = $event.value === 'yes'" /></FormField></div>
        <div class="field-card"><div class="field-title">وضع الصيانة</div><div class="field-subtitle">إيقاف الوصول مؤقتاً أثناء أعمال التحديث</div><FormField label="وضع الصيانة" forId="sys-maint"><Select id="sys-maint" :model-value="form.maintenance_mode ? 'yes' : 'no'" :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]" optionLabel="label" optionValue="value" class="w-full" @change="form.maintenance_mode = $event.value === 'yes'" /></FormField></div>
        <div class="field-card"><div class="field-title">أقصى حجم للرفع</div><div class="field-subtitle">أكبر حجم ملف مسموح به للصور والمرفقات</div><FormField label="أقصى حجم للرفع" forId="sys-upload" :errorMessage="errors.max_upload_size"><InputNumber id="sys-upload" v-model="form.max_upload_size" class="w-full" :min="1" @blur="clearError('max_upload_size')" /></FormField></div>
        <div class="field-card"><div class="field-title">مدة الاحتفاظ بالسجلات</div><div class="field-subtitle">عدد الأيام قبل حذف سجلات النشاط القديمة</div><FormField label="مدة الاحتفاظ بالسجلات" forId="sys-logs" :errorMessage="errors.log_retention_days"><InputNumber id="sys-logs" v-model="form.log_retention_days" class="w-full" :min="1" @blur="clearError('log_retention_days')" /></FormField></div>
      </div>
    </SettingsCard>

    <SettingsCard
      title="معلومات النظام"
      subtitle="مرجع سريع لحالة المنصة والخدمات"
      icon="pi pi-info-circle"
      icon-tone="blue"
      :show-footer="false"
    >
      <div class="sys-info-grid">
        <div class="sys-info-item"><span>إصدار النظام</span><strong>v1.0.0</strong></div>
        <div class="sys-info-item"><span>إصدار الواجهة</span><strong>Vue 3.5</strong></div>
        <div class="sys-info-item"><span>الخادم</span><strong>Laravel 12</strong></div>
        <div class="sys-info-item"><span>قاعدة البيانات</span><strong>MySQL</strong></div>
        <div class="sys-info-item"><span>آخر نسخة احتياطية</span><strong class="muted">غير متاح</strong></div>
        <div class="sys-info-item"><span>حالة الرسائل</span><Tag :value="smsEnabled ? 'مفعلة' : 'معطلة'" :severity="smsEnabled ? 'success' : 'secondary'" /></div>
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
const savedFlash = ref(false)
const form = reactive({})
const errors = reactive({ max_upload_size: '', log_retention_days: '' })

const smsEnabled = computed(() => store.groups.sms?.sms_enabled ?? false)

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('system', k, val[k])
  })
}, { deep: true })

function clearError(field) { if (errors[field]) errors[field] = '' }

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (form.max_upload_size !== null && form.max_upload_size !== undefined && form.max_upload_size < 1) {
    errors.max_upload_size = 'يجب أن تكون القيمة 1 أو أكبر'
    ok = false
  }
  if (form.log_retention_days !== null && form.log_retention_days !== undefined && form.log_retention_days < 1) {
    errors.log_retention_days = 'يجب أن تكون القيمة 1 أو أكبر'
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
.field-card{display:flex;flex-direction:column;gap:8px;padding:14px 16px;border:1px solid var(--border-light);border-radius:var(--radius-md);background:var(--bg-surface);}
.field-title{font-size:14px;font-weight:800;color:var(--text-primary);}
.field-subtitle{font-size:12px;line-height:1.7;color:var(--text-secondary);}
.sys-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.sys-info-item {
  display: flex; flex-direction: column; gap: 4px;
  padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--radius-sm);
  background: var(--bg-subtle);
}
.sys-info-item span { font-size: 12px; color: var(--text-secondary); }
.sys-info-item strong { font-size: 14px; color: var(--text-primary); }
.sys-info-item .muted { color: var(--text-muted); font-weight: 500; }
@media (max-width: 800px) {
  .form-grid-2,
  .sys-info-grid { grid-template-columns: 1fr; }
}
</style>
