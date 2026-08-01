<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات النظام"
      subtitle="وضع التشغيل والحدود والسجلات"
      icon="pi pi-cog"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="وضع التصحيح" forId="sys-debug" helpText="إظهار تفاصيل الأخطاء الفنية">
          <Select
            id="sys-debug"
            :model-value="form.debug_mode ? 'yes' : 'no'"
            :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="form.debug_mode = $event.value === 'yes'"
          />
        </FormField>

        <FormField label="وضع الصيانة" forId="sys-maint" helpText="إيقاف الوصول مؤقتًا لأغراض الصيانة">
          <Select
            id="sys-maint"
            :model-value="form.maintenance_mode ? 'yes' : 'no'"
            :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="form.maintenance_mode = $event.value === 'yes'"
          />
        </FormField>

        <FormField label="أقصى حجم للرفع (MB)" forId="sys-upload">
          <InputNumber id="sys-upload" v-model="form.max_upload_size" class="w-full" :min="1" />
        </FormField>

        <FormField label="مدة الاحتفاظ بالسجلات (يوم)" forId="sys-logs">
          <InputNumber id="sys-logs" v-model="form.log_retention_days" class="w-full" :min="1" />
        </FormField>
      </div>
    </SettingsCard>

    <SettingsCard
      title="معلومات النظام"
      subtitle="إصدار النظام وحالة الخدمات"
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
        <div class="sys-info-item"><span>حالة الرسائل</span><Tag value="مفعّلة" severity="success" /></div>
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

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('system', k, val[k])
  })
}, { deep: true })

function handleSave() {
  savedFlash.value = true
  setTimeout(() => { savedFlash.value = false }, 2500)
  emit('save')
}
</script>

<style scoped>
.panels-stack { display: flex; flex-direction: column; gap: 20px; }
.sys-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.sys-info-item {
  display: flex; flex-direction: column; gap: 4px;
  padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--radius-sm);
  background: var(--bg-subtle);
}
.sys-info-item span { font-size: 12px; color: var(--text-secondary); }
.sys-info-item strong { font-size: 14px; color: var(--text-primary); }
.sys-info-item .muted { color: var(--text-muted); font-weight: 500; }
@media (max-width: 640px) { .sys-info-grid { grid-template-columns: 1fr 1fr; } }
</style>
