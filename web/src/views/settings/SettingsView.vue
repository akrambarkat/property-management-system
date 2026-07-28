<template>
  <div class="settings-page">
    <Card>
      <template #title>الإعدادات العامة</template>
      <template #content>
        <div class="settings-form">
          <div class="form-field">
            <label>اسم التطبيق</label>
            <InputText v-model="settings.app_name" class="w-full" />
          </div>
          <Button label="حفظ الإعدادات" @click="saveSettings" />
        </div>
      </template>
    </Card>

    <Card>
      <template #title>إدارة العملات</template>
      <template #content>
        <DataTable :value="currencies" stripedRows>
          <Column field="code" header="الكود"></Column>
          <Column field="name" header="الاسم"></Column>
          <Column field="symbol" header="الرمز"></Column>
          <Column field="exchange_rate" header="سعر الصرف (مقابل الشيكل)">
            <template #body="s">
              <InputNumber v-model="s.data.exchange_rate" :min="0.0001" :maxFractionDigits="4" @blur="updateCurrency(s.data)" />
            </template>
          </Column>
          <Column header="افتراضي">
            <template #body="s">
              <RadioButton v-model="defaultCurrency" :inputId="'cur_' + s.data.code" :value="s.data.code" @change="setDefault(s.data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card>
      <template #title>أسعار المرافق</template>
      <template #content>
        <div class="settings-form">
          <div class="form-row">
            <div class="form-field flex-1">
              <label>سعر وحدة الكهرباء (₪)</label>
              <InputNumber v-model="settings.electricity_unit_price" class="w-full" :min="0" />
            </div>
            <div class="form-field flex-1">
              <label>سعر وحدة الماء (₪)</label>
              <InputNumber v-model="settings.water_unit_price" class="w-full" :min="0" />
            </div>
          </div>
          <Button label="حفظ" @click="saveSettings" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const settings = ref({ app_name: 'EMAARPlus', electricity_unit_price: 0.50, water_unit_price: 3.00 })
const currencies = ref([])
const defaultCurrency = ref('ILS')

onMounted(() => { fetchSettings(); fetchCurrencies() })

async function fetchSettings() {
  try { const { data } = await api.get('/settings'); settings.value = { ...settings.value, ...data.data } } catch {}
}

async function fetchCurrencies() {
  try {
    const { data } = await api.get('/currencies')
    currencies.value = data.data
    const def = data.data.find(c => c.is_default)
    if (def) defaultCurrency.value = def.code
  } catch {}
}

async function saveSettings() {
  try { await api.put('/settings', settings.value) } catch { /* */ }
}

async function updateCurrency(currency) {
  try { await api.put(`/currencies/${currency.id}`, { exchange_rate: currency.exchange_rate }) } catch { /* */ }
}

async function setDefault(currency) {
  try { await api.patch(`/currencies/${currency.id}/default`); await fetchCurrencies() } catch { /* */ }
}
</script>

<style scoped>
.settings-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  max-width: 800px;
}

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
</style>
