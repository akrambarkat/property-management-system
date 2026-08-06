<template>
  <div class="panels-stack">
    <SettingsCard
      title="الإعدادات العامة"
      subtitle="الاسم التجاري، العملة الافتراضية، وبادئات المستندات التشغيلية"
      icon="pi pi-sliders-h"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>تنعكس هذه القيم في الفواتير، التقارير، والمراسلات الداخلية. اضبطها مرة واحدة ثم راجعها عند الحاجة فقط.</p>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">اسم التطبيق</div>
          <div class="field-subtitle">الاسم الظاهر في العناوين والتقارير ورؤوس الصفحات</div>
          <FormField required forId="gen-app-name" label="اسم التطبيق" :errorMessage="errors.app_name" :successMessage="form.app_name ? 'جاهز للاستخدام' : ''">
            <InputText id="gen-app-name" v-model="form.app_name" class="w-full" placeholder="EMAARPlus" @input="clearError('app_name')" />
          </FormField>
        </div>

        <div class="field-card">
          <div class="field-title">العملة الافتراضية</div>
          <div class="field-subtitle">العملة الأساسية المستخدمة في الفواتير والتنبيهات والتقارير</div>
          <FormField forId="gen-currency" label="العملة الافتراضية" :successMessage="form.default_currency ? 'مختارة بنجاح' : ''">
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
        </div>

        <div class="field-card">
          <div class="field-title">سعر وحدة الكهرباء</div>
          <div class="field-subtitle">القيمة المرجعية لكل كيلوواط ساعة للحسابات التشغيلية</div>
          <FormField forId="gen-elec" label="سعر وحدة الكهرباء" :errorMessage="errors.electricity_unit_price">
            <InputNumber id="gen-elec" v-model="form.electricity_unit_price" class="w-full" :min="0" :maxFractionDigits="3" @blur="clearError('electricity_unit_price')" />
          </FormField>
        </div>

        <div class="field-card">
          <div class="field-title">سعر وحدة الماء</div>
          <div class="field-subtitle">القيمة المرجعية لكل متر مكعب للحسابات التشغيلية</div>
          <FormField forId="gen-water" label="سعر وحدة الماء" :errorMessage="errors.water_unit_price">
            <InputNumber id="gen-water" v-model="form.water_unit_price" class="w-full" :min="0" :maxFractionDigits="3" @blur="clearError('water_unit_price')" />
          </FormField>
        </div>
      </div>

      <div class="prefix-grid">
        <div class="field-card">
          <div class="field-title">بادئة الفواتير</div>
          <div class="field-subtitle">تستخدم في أرقام الفواتير مثل INV-2026-0001</div>
          <FormField forId="gen-invoice-prefix" label="بادئة الفواتير">
            <InputText id="gen-invoice-prefix" v-model="form.invoice_prefix" class="w-full" dir="ltr" placeholder="INV" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">بادئة العقود</div>
          <div class="field-subtitle">تستخدم في ترقيم العقود الرسمية</div>
          <FormField forId="gen-contract-prefix" label="بادئة العقود">
            <InputText id="gen-contract-prefix" v-model="form.contract_prefix" class="w-full" dir="ltr" placeholder="CON" />
          </FormField>
        </div>
        <div class="field-card">
          <div class="field-title">بادئة السندات</div>
          <div class="field-subtitle">تستخدم في سندات الدفع والإيصالات</div>
          <FormField forId="gen-receipt-prefix" label="بادئة السندات">
            <InputText id="gen-receipt-prefix" v-model="form.receipt_prefix" class="w-full" dir="ltr" placeholder="REC" />
          </FormField>
        </div>
      </div>
    </SettingsCard>

    <SettingsCard
      title="العملات وسعر الصرف"
      subtitle="مراجعة سريعة للعملات النشطة وضبط السعر المرجعي لكل عملة"
      icon="pi pi-dollar"
      icon-tone="green"
      :show-footer="false"
    >
      <EnterpriseTable
        :value="store.currencies"
        :loading="false"
        :show-search="false"
        searchPlaceholder="بحث"
        emptyTitle="لا توجد عملات"
        emptySubtitle="لم يتم العثور على أي عملات نشطة"
        :columns="currencyColumns"
        :selectable="false"
        @refresh="store.fetchCurrencies"
      >
        <template #default="{ hiddenColumns }">
          <Column v-if="!hiddenColumns.includes('code')" field="code" header="الرمز" sortable>
            <template #body="s"><span class="code-badge">{{ s.data.code }}</span></template>
          </Column>
          <Column v-if="!hiddenColumns.includes('name')" field="name" header="الاسم" sortable />
          <Column v-if="!hiddenColumns.includes('symbol')" field="symbol" header="الرمز" />
          <Column v-if="!hiddenColumns.includes('exchange_rate')" field="exchange_rate" header="سعر الصرف">
            <template #body="s">
              <InputNumber
                v-model="s.data.exchange_rate"
                :min="0.0001"
                :maxFractionDigits="4"
                class="rate-input"
                @blur="updateCurrency(s.data)"
              />
            </template>
          </Column>
          <Column header="افتراضي" style="width: 90px; text-align: center;">
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
const errors = reactive({ app_name: '', default_currency: '', electricity_unit_price: '', water_unit_price: '' })

const currencyColumns = [
  { field: 'code', header: 'الرمز' },
  { field: 'name', header: 'الاسم' },
  { field: 'symbol', header: 'الرمز' },
  { field: 'exchange_rate', header: 'سعر الصرف', tabletHidden: true }
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

function clearError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))

  if (!String(form.app_name || '').trim()) {
    errors.app_name = 'اسم التطبيق مطلوب'
    ok = false
  }
  if (form.electricity_unit_price !== null && form.electricity_unit_price !== undefined && form.electricity_unit_price < 0) {
    errors.electricity_unit_price = 'يجب أن تكون القيمة 0 أو أكبر'
    ok = false
  }
  if (form.water_unit_price !== null && form.water_unit_price !== undefined && form.water_unit_price < 0) {
    errors.water_unit_price = 'يجب أن تكون القيمة 0 أو أكبر'
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

async function updateCurrency(currency) {
  try {
    await api.put(`/currencies/${currency.id}`, { exchange_rate: currency.exchange_rate })
    toast.success(`تم تحديث ${currency.name}`)
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
.panels-stack { display: flex; flex-direction: column; gap: 20px; }
.section-note {
  display: flex; gap: 12px; padding: 14px 16px; border-radius: var(--radius-md);
  background: var(--bg-subtle); border: 1px solid var(--border);
}
.section-note i { color: var(--info-contrast); font-size: 1rem; margin-top: 2px; }
.section-note p { margin: 0; font-size: 12.5px; line-height: 1.8; color: var(--text-secondary); }
.form-grid-2, .prefix-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.prefix-grid { margin-top: 4px; }
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
.code-badge {
  display: inline-flex; padding: 3px 8px; border-radius: var(--radius-full);
  background: var(--bg-subtle); font-size: 12px; font-weight: 600;
}
.rate-input { max-width: 140px; }
@media (max-width: 800px) {
  .form-grid-2, .prefix-grid { grid-template-columns: 1fr; }
}
</style>
