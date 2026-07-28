<template>
  <div class="page-view">
    <div class="page-toolbar">
      <Button label="إضافة موقع" icon="pi pi-plus" @click="showDialog = true" />
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15" :rowsPerPageOptions="[10,15,25,50]">
        <Column field="name" header="الاسم" sortable></Column>
        <Column field="address" header="العنوان" sortable></Column>
        <Column header="عدد المباني">
          <template #body="slotProps">
            <Tag :value="slotProps.data.buildings_count || 0" />
          </template>
        </Column>
        <Column header="الحالة">
          <template #body="slotProps">
            <Tag :value="slotProps.data.is_active ? 'نشط' : 'غير نشط'" :severity="slotProps.data.is_active ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="الإجراءات" style="width: 120px">
          <template #body="slotProps">
            <Button icon="pi pi-pencil" severity="info" text rounded @click="editItem(slotProps.data)" />
            <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteItem(slotProps.data)" />
          </template>
        </Column>
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-map-marker"></i>
            <p>لا توجد مواقع مسجلة</p>
          </div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل موقع' : 'إضافة موقع'" modal :style="{ width: '500px' }">
      <form @submit.prevent="saveItem">
        <div class="form-field">
          <label>اسم الموقع</label>
          <InputText v-model="form.name" class="w-full" required />
        </div>
        <div class="form-field">
          <label>العنوان</label>
          <Textarea v-model="form.address" class="w-full" rows="3" />
        </div>
        <div class="form-field">
          <label>الحالة</label>
          <SelectButton v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" />
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

const items = ref([])
const showDialog = ref(false)
const isEditing = ref(false)

const form = reactive({
  id: null,
  name: '',
  address: '',
  is_active: true
})

const statusOptions = ref([
  { label: 'نشط', value: true },
  { label: 'غير نشط', value: false }
])

onMounted(() => fetchItems())

async function fetchItems() {
  try {
    const { data } = await api.get('/locations')
    items.value = data.data
  } catch { items.value = [] }
}

function editItem(item) {
  Object.assign(form, item)
  isEditing.value = true
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  isEditing.value = false
  form.id = null
  form.name = ''
  form.address = ''
  form.is_active = true
}

async function saveItem() {
  try {
    if (isEditing.value) {
      await api.put(`/locations/${form.id}`, form)
    } else {
      await api.post('/locations', form)
    }
    closeDialog()
    await fetchItems()
  } catch (err) {
    console.error(err)
  }
}

async function deleteItem(item) {
  if (!confirm('هل أنت متأكد من حذف هذا الموقع؟')) return
  try {
    await api.delete(`/locations/${item.id}`)
    await fetchItems()
  } catch (err) {
    console.error(err)
  }
}
</script>
