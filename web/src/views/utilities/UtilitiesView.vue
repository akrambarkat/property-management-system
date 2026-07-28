<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.utility_type" :options="typeFilter" optionLabel="label" optionValue="value" placeholder="النوع" showClear @change="fetchItems" />
      </div>
      <Button label="تسجيل قراءة" icon="pi pi-plus" @click="showDialog = true" />
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="unit.unit_number" header="الوحدة">
          <template #body="s">{{ s.data.unit?.unit_number }} - {{ s.data.unit?.building?.name }}</template>
        </Column>
        <Column field="utility_type" header="النوع">
          <template #body="s">{{ s.data.utility_type === 'electricity' ? 'كهرباء' : 'ماء' }}</template>
        </Column>
        <Column field="reading_date" header="التاريخ" sortable></Column>
        <Column field="previous_reading" header="القراءة السابقة"></Column>
        <Column field="current_reading" header="القراءة الحالية"></Column>
        <Column field="consumption" header="الاستهلاك" sortable></Column>
        <Column field="unit_price" header="سعر الوحدة">
          <template #body="s">{{ formatCurrency(s.data.unit_price) }}</template>
        </Column>
        <Column field="total" header="المجموع" sortable>
          <template #body="s">{{ formatCurrency(s.data.total) }}</template>
        </Column>
        <template #empty>
          <div class="empty-state"><i class="pi pi-bolt"></i><p>لا توجد قراءات</p></div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" header="تسجيل قراءة عداد" modal :style="{ width: '500px' }">
      <form @submit.prevent="saveItem">
        <div class="form-field">
          <label>الوحدة</label>
          <Select v-model="form.unit_id" :options="units" optionLabel="label" optionValue="id" placeholder="اختر الوحدة" required class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>النوع</label>
            <Select v-model="form.utility_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>التاريخ</label>
            <DatePicker v-model="form.reading_date" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>القراءة السابقة</label>
            <InputNumber v-model="form.previous_reading" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>القراءة الحالية</label>
            <InputNumber v-model="form.current_reading" class="w-full" :min="0" />
          </div>
        </div>
        <div class="form-actions">
          <Button label="إلغاء" severity="secondary" @click="closeDialog" />
          <Button label="حفظ" type="submit" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const items = ref([])
const units = ref([])
const showDialog = ref(false)
const filters = reactive({ utility_type: null })

const form = reactive({ unit_id: null, utility_type: 'electricity', reading_date: null, previous_reading: 0, current_reading: 0 })

const typeFilter = ref([{ label: 'كهرباء', value: 'electricity' }, { label: 'ماء', value: 'water' }])
const typeOptions = ref([{ label: 'كهرباء', value: 'electricity' }, { label: 'ماء', value: 'water' }])

function formatCurrency(amount) { return `${Number(amount).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}` }

onMounted(() => { fetchUnits(); fetchItems() })

async function fetchUnits() {
  try { const { data } = await api.get('/units'); units.value = data.data.map(u => ({ ...u, label: `#${u.unit_number} - ${u.building?.name}` })) } catch {}
}

async function fetchItems() {
  try {
    const params = filters.utility_type ? { type: filters.utility_type } : {}
    const { data } = await api.get('/utility-readings', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function closeDialog() {
  showDialog.value = false
  Object.assign(form, { unit_id: null, utility_type: 'electricity', reading_date: null, previous_reading: 0, current_reading: 0 })
}

async function saveItem() {
  try { await api.post('/utility-readings', form); closeDialog(); await fetchItems() } catch { /* */ }
}
</script>
