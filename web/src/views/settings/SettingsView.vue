<template>
  <div class="settings-page page-view">
    <!-- Feedback Toasts / Banners -->
            <Card class="saas-card">
      <template #title>
        <div class="card-header-title">
          <i class="pi pi-cog text-blue"></i>
          <div>
            <h3>الإعدادات العامة للنظام</h3>
            <span class="card-sub-title">تخصيص الاسم والعملة الرئيسية للتطبيق</span>
          </div>
        </div>
      </template>
      <template #content>
        <div class="settings-form form-section">
          <div class="form-grid-2">
            <FormField
              label="اسم التطبيق / المنظومة"
              required
              forId="set-app-name"
              helpText="الاسم الذي يظهر في العناوين والتقارير المطبوعة"
            >
              <InputText
                id="set-app-name"
                v-model="settings.app_name"
                class="w-full"
                placeholder="أدخل اسم المنظومة"
              />
            </FormField>

            <FormField
              label="العملة المفضلة للعرض"
              forId="set-pref-currency"
              helpText="العملة الافتراضية لعرض الإحصائيات باللوحة"
            >
            <Select
              id="set-pref-currency"
              v-model="preferredCurrency"
              :options="currencies"
              optionLabel="name"
              optionValue="code"
              class="w-full"
              filter
              @change="updatePreferredCurrency"
            />
            </FormField>
          </div>

          <div class="form-actions align-start">
            <button class="btn-primary" @click="saveSettings" :disabled="saving">
              <i v-if="saving" class="pi pi-spin pi-spinner"></i>
              <i v-else class="pi pi-save"></i>
              <span>{{ saving ? 'جاري الحفظ...' : 'حفظ الإعدادات العامة' }}</span>
            </button>
          </div>
        </div>
      </template>
    </Card>

    <Card class="saas-card">
      <template #title>
        <div class="card-header-title">
          <i class="pi pi-dollar text-green"></i>
          <div>
            <h3>إدارة العملات وأسعار الصرف</h3>
            <span class="card-sub-title">ضبط أسعار التحويل بين العملات بالنسبة للعملة الرئيسية</span>
          </div>
        </div>
      </template>
      <template #content>
        <EnterpriseTable
          :value="currencies"
          :loading="loadingCurrencies"
          searchPlaceholder="بحث في العملات..."
          emptyTitle="لا توجد عملات"
          emptySubtitle="لم يتم العثور على أي عملات مسجلة"
          :columns="currencyColumns"
          @refresh="fetchCurrencies"
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
            <Column v-if="!hiddenColumns.includes('exchange_rate')" field="exchange_rate" header="سعر الصرف (مقابل الشيكل)">
              <template #body="s">
                <InputNumber v-model="s.data.exchange_rate" :min="0.0001" :maxFractionDigits="4" @blur="updateCurrency(s.data)" class="rate-input" />
              </template>
            </Column>
            <Column header="افتراضي" style="width: 100px; text-align: center;">
              <template #body="s">
                <RadioButton v-model="defaultCurrency" :inputId="'cur_' + s.data.code" :value="s.data.code" @change="setDefault(s.data)" />
              </template>
            </Column>
          </template>
        </EnterpriseTable>
      </template>
    </Card>

    <Card class="saas-card">
      <template #title>
        <div class="card-header-title">
          <i class="pi pi-bolt text-amber"></i>
          <div>
            <h3>أسعار التعرفة والخدمات</h3>
            <span class="card-sub-title">تعرفة حساب تكاليف استهلاك العدادات التلقائي</span>
          </div>
        </div>
      </template>
      <template #content>
        <div class="settings-form form-section">
          <div class="form-grid-2">
            <FormField
              label="سعر وحدة الكهرباء (₪)"
              forId="set-elec-price"
              helpText="التكلفة بالسنتم/شيكل لكل كيلوواط"
            >
              <InputNumber
                id="set-elec-price"
                v-model="settings.electricity_unit_price"
                class="w-full"
                :min="0"
                :maxFractionDigits="3"
              />
            </FormField>

            <FormField
              label="سعر وحدة الماء (₪)"
              forId="set-water-price"
              helpText="التكلفة لكل متر مكعب من المياه"
            >
              <InputNumber
                id="set-water-price"
                v-model="settings.water_unit_price"
                class="w-full"
                :min="0"
                :maxFractionDigits="3"
              />
            </FormField>
          </div>
          <div class="form-actions align-start">
            <button class="btn-primary" @click="saveSettings" :disabled="saving">
              <i v-if="saving" class="pi pi-spin pi-spinner"></i>
              <i v-else class="pi pi-save"></i>
              <span>{{ saving ? 'جاري الحفظ...' : 'حفظ تعرفة الخدمات' }}</span>
            </button>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const settings = ref({ app_name: 'EMAARPlus', electricity_unit_price: 0.50, water_unit_price: 3.00 })
const currencies = ref([])
const loadingCurrencies = ref(false)
const saving = ref(false)
const defaultCurrency = ref('ILS')
const preferredCurrency = ref(localStorage.getItem('preferred_currency') || 'ILS')

const currencyColumns = [
  { field: 'code', header: 'الكود' },
  { field: 'name', header: 'الاسم' },
  { field: 'symbol', header: 'الرمز' },
  { field: 'exchange_rate', header: 'سعر الصرف' }
]

onMounted(() => { fetchSettings(); fetchCurrencies() })


async function fetchSettings() {
  try {
    const { data } = await api.get('/settings')
    settings.value = { ...settings.value, ...data.data }
  } catch (err) {
    console.error(err)
  }
}

async function fetchCurrencies() {
  loadingCurrencies.value = true
  try {
    const { data } = await api.get('/currencies')
    currencies.value = data.data
    const def = data.data.find(c => c.is_default)
    if (def) defaultCurrency.value = def.code
  } catch (err) {
    console.error(err)
  } finally {
    loadingCurrencies.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await api.put('/settings', settings.value)
    toast.success('تم حفظ إعدادات المنظومة بنجاح')
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ الإعدادات')
  } finally {
    saving.value = false
  }
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
    await fetchCurrencies()
    toast.success(`تم تعيين ${currency.name} كعملة افتراضية`)
  } catch (err) {
    toast.error('تعذر تعيين العملة الافتراضية')
  }
}

function updatePreferredCurrency() {
  localStorage.setItem('preferred_currency', preferredCurrency.value)
  toast.success('تم تغيير عملة العرض المفضلة')
  setTimeout(() => { window.location.reload() }, 800)
}
</script>

<style scoped>
.settings-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.saas-card {
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.card-header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}
.card-header-title i {
  font-size: 1.5rem;
}
.card-header-title h3 {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
}
.card-sub-title {
  font-size: 12.5px;
  color: var(--text-secondary);
  font-weight: 400;
}

.code-badge, .symbol-badge {
  background: #F1F5F9;
  padding: 3px 8px;
  border-radius: var(--radius-xs);
  font-size: 12px;
  font-weight: 600;
}

.rate-input {
  max-width: 140px;
}

.align-start {
  justify-content: flex-start !important;
  margin-top: 16px !important;
}

.text-blue { color: #2563EB; }
.text-green { color: #10B981; }
.text-amber { color: #F59E0B; }
</style>
