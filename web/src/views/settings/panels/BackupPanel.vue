<template>
  <div class="panels-stack">
    <SettingsCard
      title="النسخ الاحتياطي والاستعادة"
      subtitle="جدولة النسخ الاحتياطي لقاعدة البيانات والملفات"
      icon="pi pi-database"
      icon-tone="green"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="تفعيل النسخ الاحتياطي" forId="bk-enable">
          <Select
            id="bk-enable"
            :model-value="form.backup_enabled ? 'yes' : 'no'"
            :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="form.backup_enabled = $event.value === 'yes'"
          />
        </FormField>

        <FormField label="تكرار النسخ" forId="bk-freq">
          <Select
            id="bk-freq"
            v-model="form.backup_frequency"
            :options="[{ value: 'daily', label: 'يومي' }, { value: 'weekly', label: 'أسبوعي' }, { value: 'monthly', label: 'شهري' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="مدة الاحتفاظ (يوم)" forId="bk-retain">
          <InputNumber id="bk-retain" v-model="form.backup_retention_days" class="w-full" :min="1" :max="365" />
        </FormField>

        <FormField label="وجهة النسخ" forId="bk-dest">
          <Select
            id="bk-dest"
            v-model="form.backup_destination"
            :options="[{ value: 'local', label: 'محلي' }, { value: 'cloud', label: 'سحابي' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>
      </div>
    </SettingsCard>

    <SettingsCard
      title="نسخة احتياطية الآن"
      subtitle="إنشاء نسخة احتياطية كاملة يدويًا"
      icon="pi pi-download"
      icon-tone="amber"
      :show-footer="false"
    >
      <div class="backup-actions">
        <button class="btn-primary" :disabled="backingUp" @click="runBackup">
          <i v-if="backingUp" class="pi pi-spin pi-spinner"></i>
          <i v-else class="pi pi-cloud-download"></i>
          <span>{{ backingUp ? 'جارٍ إنشاء النسخة...' : 'إنشاء نسخة احتياطية الآن' }}</span>
        </button>
        <button class="btn-secondary" :disabled="backingUp" @click="toast.info('استعادة النسخة ستتطلب إعادة تشغيل النظام')">
          <i class="pi pi-upload"></i>
          <span>استعادة من نسخة</span>
        </button>
      </div>
      <p class="backup-hint"><i class="pi pi-info-circle"></i> يتم حفظ النسخ الاحتياطية خارج مجلد التطبيق لضمان عدم فقدانها.</p>
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

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('backup', k, val[k])
  })
}, { deep: true })

function handleSave() {
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
.backup-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.backup-hint {
  display: flex; align-items: center; gap: 8px;
  margin-top: 16px; font-size: 12.5px; color: var(--text-secondary);
}
.backup-hint i { color: var(--info); }
</style>
