<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات الفواتير"
      subtitle="الترقيم والضريبة وشروط إصدار الفواتير"
      icon="pi pi-file-invoice"
      icon-tone="blue"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="بادئة الفواتير" forId="inv-prefix" helpText="مثال: INV-2026-0001">
          <InputText id="inv-prefix" v-model="form.invoice_prefix" class="w-full" />
        </FormField>

        <FormField label="أيام الاستحقاق الافتراضية" forId="inv-due" helpText="عدد الأيام قبل اعتبار الفاتورة متأخرة">
          <InputNumber id="inv-due" v-model="form.invoice_due_days" class="w-full" :min="0" :max="365" />
        </FormField>

        <FormField label="الرقم الضريبي" forId="inv-tax">
          <InputText id="inv-tax" v-model="form.invoice_tax_number" class="w-full" />
        </FormField>

        <FormField label="غرامة التأخير اليومية (٪)" forId="inv-late" helpText="نسبة تُضاف عند التأخر عن السداد">
          <InputNumber id="inv-late" v-model="form.invoice_late_fee" class="w-full" :min="0" :maxFractionDigits="3" />
        </FormField>

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

        <FormField label="الإصدار التلقائي للفواتير" forId="inv-auto">
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
    if (props.settings && val[k] !== props.settings[k]) store.setValue('invoices', k, val[k])
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
