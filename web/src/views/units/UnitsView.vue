<template>
  <div class="page-view">
    <div v-if="errorMsg" class="error-banner" style="background: #FEE2E2; color: #DC2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
      <i class="pi pi-exclamation-circle"></i>
      <span>{{ errorMsg }}</span>
    </div>

    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.status" :options="statusFilter" optionLabel="label" optionValue="value" placeholder="الحالة" showClear @change="fetchItems" />
        <Select v-model="filters.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="المبنى" showClear @change="fetchItems" />
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة وحدة</button>
    </div>

    <Card>
      <template #content>
        <DataTable :value="items" stripedRows paginator :rows="15">
          <Column field="unit_number" header="رقم الوحدة" sortable></Column>
          <Column field="building.name" header="المبنى" sortable></Column>
          <Column field="unit_type" header="النوع" sortable>
            <template #body="slotProps">
              <Tag :value="typeLabels[slotProps.data.unit_type]" />
            </template>
          </Column>
          <Column field="floor" header="الطابق" sortable></Column>
          <Column field="area" header="المساحة (م²)" sortable></Column>
          <Column field="rent_amount" header="الإيجار" sortable>
            <template #body="slotProps">{{ formatCurrency(slotProps.data.rent_amount) }}</template>
          </Column>
          <Column header="الحالة">
            <template #body="slotProps">
              <span :class="'status-badge status-' + slotProps.data.status">{{ statusLabels[slotProps.data.status] }}</span>
            </template>
          </Column>
          <Column header="الإجراءات" style="width: 120px">
            <template #body="slotProps">
              <button class="btn-icon" @click="editItem(slotProps.data)"><i class="pi pi-pencil"></i></button>
              <button class="btn-icon btn-danger" @click="deleteItem(slotProps.data)"><i class="pi pi-trash"></i></button>
            </template>
          </Column>
          <template #empty>
            <div class="empty-state"><i class="pi pi-th-large"></i><p>لا توجد وحدات مسجلة</p></div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل وحدة' : 'إضافة وحدة'" modal :style="{ width: '550px' }">
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>المبنى</label>
            <Select v-model="form.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="اختر المبنى" required class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>رقم الوحدة</label>
            <InputText v-model="form.unit_number" class="w-full" required />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>النوع</label>
            <Select v-model="form.unit_type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="اختر النوع" required class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>الطابق</label>
            <InputNumber v-model="form.floor" class="w-full" :min="0" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>المساحة (م²)</label>
            <InputNumber v-model="form.area" class="w-full" :min="1" />
          </div>
          <div class="form-field flex-1">
            <label>الإيجار (₪)</label>
            <InputNumber v-model="form.rent_amount" class="w-full" :min="0" />
          </div>
        </div>
        <div class="form-field">
          <label>الحالة</label>
          <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem">حفظ</button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const items = ref([])
const buildings = ref([])
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')

const filters = reactive({ status: null, building_id: null })

const form = reactive({
  id: null, building_id: null, unit_number: '', unit_type: 'apartment',
  floor: 0, area: null, rent_amount: null, status: 'available'
})

const typeLabels = { apartment: 'شقة', shop: 'محل', warehouse: 'مخزن' }
const statusLabels = { available: 'متاحة', occupied: 'مشغولة', maintenance: 'صيانة' }
const typeOptions = ref([
  { label: 'شقة', value: 'apartment' },
  { label: 'محل', value: 'shop' },
  { label: 'مخزن', value: 'warehouse' }
])
const statusFilter = ref([
  { label: 'متاحة', value: 'available' },
  { label: 'مشغولة', value: 'occupied' },
  { label: 'صيانة', value: 'maintenance' }
])
const statusOptions = ref([
  { label: 'متاحة', value: 'available' },
  { label: 'مشغولة', value: 'occupied' },
  { label: 'صيانة', value: 'maintenance' }
])

function formatCurrency(amount) {
  return `${Number(amount).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}`
}

onMounted(() => { fetchBuildings(); fetchItems() })

async function fetchBuildings() {
  try {
    const { data } = await api.get('/buildings')
    buildings.value = data.data
  } catch (err) {
    console.error('fetchBuildings error:', err)
    errorMsg.value = 'خطأ في تحميل المباني: ' + (err.response?.data?.message || err.message)
  }
}

async function fetchItems() {
  try {
    errorMsg.value = ''
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.building_id) params.building_id = filters.building_id
    const { data } = await api.get('/units', { params })
    items.value = data.data
  } catch (err) {
    console.error('fetchItems error:', err)
    errorMsg.value = 'خطأ في تحميل الوحدات: ' + (err.response?.data?.message || err.message)
    items.value = []
  }
}

function editItem(item) { Object.assign(form, item); isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  form.id = null; form.building_id = null; form.unit_number = ''
  form.unit_type = 'apartment'; form.floor = 0; form.area = null
  form.rent_amount = null; form.status = 'available'
}

async function saveItem() {
  try {
    if (isEditing.value) await api.put(`/units/${form.id}`, form)
    else await api.post('/units', form)
    closeDialog(); await fetchItems()
  } catch (e) {
    console.error('saveItem error:', e.response?.data || e.message)
    alert(e.response?.data?.message || e.response?.data || 'حدث خطأ في الحفظ')
  }
}

async function deleteItem(item) {
  if (!confirm('هل أنت متأكد من حذف هذه الوحدة؟')) return
  try { await api.delete(`/units/${item.id}`); await fetchItems() }
  catch (e) { console.error('deleteItem error:', e); alert('حدث خطأ في الحذف') }
}
</script>
