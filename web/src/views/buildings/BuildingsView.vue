<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.location_id" :options="locations" optionLabel="name" optionValue="id" placeholder="الموقع" showClear @change="fetchItems" />
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة مبنى</button>
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="name" header="اسم المبنى" sortable></Column>
        <Column field="location.name" header="الموقع" sortable></Column>
        <Column field="floors" header="عدد الطوابق" sortable></Column>
        <Column header="عدد الوحدات">
          <template #body="slotProps">
            <Tag :value="slotProps.data.units_count || 0" />
          </template>
        </Column>
        <Column header="الحالة">
          <template #body="slotProps">
            <Tag :value="slotProps.data.is_active ? 'نشط' : 'غير نشط'" :severity="slotProps.data.is_active ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="الإجراءات" style="width: 120px">
          <template #body="slotProps">
            <button class="btn-icon" @click="editItem(slotProps.data)"><i class="pi pi-pencil"></i></button>
            <button class="btn-icon btn-danger" @click="deleteItem(slotProps.data)"><i class="pi pi-trash"></i></button>
          </template>
        </Column>
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-building"></i>
            <p>لا توجد مباني مسجلة</p>
          </div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل مبنى' : 'إضافة مبنى'" modal :style="{ width: '500px' }">
      <div class="dialog-body">
        <div class="form-field">
          <label>الموقع</label>
          <Select v-model="form.location_id" :options="locations" optionLabel="name" optionValue="id" placeholder="اختر الموقع" required class="w-full" />
        </div>
        <div class="form-field">
          <label>اسم المبنى</label>
          <InputText v-model="form.name" class="w-full" required />
        </div>
        <div class="form-field">
          <label>عدد الطوابق</label>
          <InputNumber v-model="form.floors" class="w-full" :min="1" />
        </div>
        <div class="form-field">
          <label>الحالة</label>
          <SelectButton v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" />
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

const items = ref([])
const locations = ref([])
const showDialog = ref(false)
const isEditing = ref(false)

const filters = reactive({ location_id: null })

const form = reactive({
  id: null, location_id: null, name: '', floors: 1, is_active: true
})

const statusOptions = ref([
  { label: 'نشط', value: true },
  { label: 'غير نشط', value: false }
])

onMounted(() => { fetchLocations(); fetchItems() })

async function fetchLocations() {
  try { const { data } = await api.get('/locations'); locations.value = data.data } catch {}
}

async function fetchItems() {
  try {
    const params = filters.location_id ? { location_id: filters.location_id } : {}
    const { data } = await api.get('/buildings', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function editItem(item) { Object.assign(form, item); isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  form.id = null; form.location_id = null; form.name = ''; form.floors = 1; form.is_active = true
}

async function saveItem() {
  try {
    if (isEditing.value) await api.put(`/buildings/${form.id}`, form)
    else await api.post('/buildings', form)
    closeDialog(); await fetchItems()
  } catch { /* */ }
}

async function deleteItem(item) {
  if (!confirm('هل أنت متأكد من حذف هذا المبنى؟')) return
  try { await api.delete(`/buildings/${item.id}`); await fetchItems() } catch { /* */ }
}
</script>
