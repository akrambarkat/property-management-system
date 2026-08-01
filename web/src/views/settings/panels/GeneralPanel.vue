<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات عامة"
      subtitle="الاسم، العملة الافتراضية، وأكواد الترقيم"
      icon="pi pi-sliders-h"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="اسم التطبيق / المنظومة" forId="gen-app-name" helpText="الاسم الذي يظهر في العناوين والتقارير">
          <InputText id="gen-app-name" v-model="form.app_name" class="w-full" placeholder="EMAARPlus" />
        </FormField>

        <FormField label="العملة الافتراضية" forId="gen-currency" helpText="العملة المستخدمة في الحسابات والتقارير">
          <Select
            id="gen-currency"
            v-model="form.default_currency"
            :options="store.currencies"
            optionLabel="name"
            optionValue="code"
            class="w-full"
            filter
          />
        </FormField>

        <FormField label="بادئة الفواتير" forId="gen-inv-prefix" helpText="مثال: INV-2026-0001">
          <InputText id="gen-inv-prefix" v-model="form.invoice_prefix" class="w-full" />
        </FormField>

        <FormField label="بادئة العقود" forId="gen-contract-prefix" helpText="مثال: CTR-2026-0001">
          <InputText id="gen-contract-prefix" v-model="form.contract_prefix" class="w-full" />
        </FormField>

        <FormField label="بادئة الإيصالات" forId="gen-receipt-prefix" helpText="مثال: REC-2026-0001">
          <InputText id="gen-receipt-prefix" v-model="form.receipt_prefix" class="w-full" />
        </FormField>

        <FormField label="سعر وحدة الكهرباء (₪)" forId="gen-elec" helpText="التكلفة لكل كيلوواط">
          <InputNumber id="gen-elec" v-model="form.electricity_unit_price" class="w-full" :min="0" :maxFractionDigits="3" />
        </FormField>

        <FormField label="سعر وحدة الماء (₪)" forId="gen-water" helpText="التكلفة لكل متر مكعب">
          <InputNumber id="gen-water" v-model="form.water_unit_price" class="w-full" :min="0" :maxFractionDigits="3" />
        </FormField>
      </div>
    </SettingsCard>

    <SettingsCard
      title="إدارة العملات وأسعار الصرف"
      subtitle="ضبط أسعار التحويل بين العملات"
      icon="pi pi-dollar"
      icon-tone="green"
      show-footer="false"
    >
      <EnterpriseTable
        :value="store.currencies"
        :loading="false"
        searchPlaceholder="بحث في العملات..."
        emptyTitle="لا توجد عملات"
        emptySubtitle="لم يتم العثور على أي عملات مسجلة"
        :columns="currencyColumns"
        :selectable="false"
        show-search="false"
        @refresh="store.fetchCurrencies"
      >
        <template #default="{ hiddenColumns }">
          <Column v-if="!hiddenColumns.includes('code')" field="code" header="الكود" sortable>
            <template #body="s">
              <span class="code-badge">{{ s.data.code }}</span>
            </template>
          </Column>
          <Column v-if="!hiddenColumns.includes('name')" field="name" header="الاسم" sortable></Column>
          <Column v-if="!hiddenColumns.includes('symbol')" field="symbol" header="الرمز">
            <template #body="s">
              <span class="symbol-badge">{{ s.data.symbol }}</span>
            </template>
          </Column>
          <Column v-if="!hiddenColumns.includes('exchange_rate')" field="exchange_rate" header="سعر الصرف">
            <template #body="s">
              <InputNumber
                v-model="s.data.exchange_rate"
                :min="0.0001"
                :maxFractionDigits="4"
                @blur="updateCurrency(s.data)"
                class="rate-input"
              />
            </template>
          </Column>
          <Column header="افتراضي" style="width: 100px; text-align: center;">
            <template #body="s">
              <RadioButton
                v-model="defaultCurrency"
                :inputId="'cur_' + s.data.code"
                :value="s.data.code"
                @change="setDefault(s.data)"
              />
            </template>
          </Column>
        </template>
      </EnterpriseTable>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import SettingsCard from '@/components/settings/SettingsCard.vue'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])

const store = useSettingsStore()
const toast = useToastStore()
const savedFlash = ref(false)
const defaultCurrency = ref('ILS')

const form = reactive({})

const currencyColumns = [
  { field: 'code', header: 'الكود' },
  { field: 'name', header: 'الاسم' },
  { field: 'symbol', header: 'الرمز' },
  { field: 'exchange_rate', header: 'سعر الصرف' }
]

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('general', k, val[k])
  })
}, { deep: true })

onMounted(() => {
  if (!store.currencies.length) store.fetchCurrencies()
  const def = store.currencies.find(c => c.is_default)
  if (def) defaultCurrency.value = def.code
})

function handleSave() {
  savedFlash.value = true
  setTimeout(() => { savedFlash.value = false }, 2500)
  emit('save')
}

async function updateCurrency(currency) {
  try {
    await api.put(`/currencies/${currency.id}`, { exchange_rate: currency.exchange_rate })
    toast.success(`تم التعديل: ${currency.name}`)
  } catch (err) {
    toast.error('تعذر تحديث سعر الصرف')
  }
}

async function setDefault(currency) {
  try {
    await api.patch(`/currencies/${currency.id}/default`)
    await store.fetchCurrencies()
    defaultCurrency.value = currency.code
    store.setValue('general', 'default_currency', currency.code)
    toast.success(`تم تعيين ${currency.name} كعملة افتراضية`)
  } catch (err) {
    toast.error('تعذر تعيين العملة الافتراضية')
  }
}
</script>

<style scoped>
.panels-stack {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.code-badge, .symbol-badge {
  background: var(--bg-subtle);
  padding: 3px 8px;
  border-radius: var(--radius-xs);
  font-size: 12px;
  font-weight: 600;
}
.rate-input {
  max-width: 140px;
}
</style>
