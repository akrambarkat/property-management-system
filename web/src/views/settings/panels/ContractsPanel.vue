<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات العقود"
      subtitle="الترقيم والتذكيرات وشروط التجديد"
      icon="pi pi-file-edit"
      icon-tone="purple"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="بادئة العقود" forId="ctr-prefix" helpText="مثال: CTR-2026-0001">
          <InputText id="ctr-prefix" v-model="form.contract_prefix" class="w-full" />
        </FormField>

        <FormField label="أيام التذكير قبل الانتهاء" forId="ctr-remind" helpText="متى يتم تنبيه المستأجرين قبل انتهاء العقد">
          <InputNumber id="ctr-remind" v-model="form.contract_reminder_days" class="w-full" :min="0" :max="365" />
        </FormField>

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

      <div class="form-field" style="margin-top: 16px;">
        <label for="ctr-terms">شروط العقد الافتراضية</label>
        <Textarea id="ctr-terms" v-model="form.contract_terms" rows="5" class="w-full" placeholder="الشروط والأحكام الافتراضية المضمنة في العقود" />
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
    if (props.settings && val[k] !== props.settings[k]) store.setValue('contracts', k, val[k])
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
</style>
