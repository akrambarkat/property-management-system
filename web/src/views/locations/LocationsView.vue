<template>
  <div class="page-view">
    <div class="page-toolbar">
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة موقع</button>
    </div>

    <Card>
      <template #content>
        <table class="data-table">
          <thead>
            <tr>
              <th>الاسم</th>
              <th>العنوان</th>
              <th>عدد المباني</th>
              <th>الحالة</th>
              <th style="width:120px">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.address || '-' }}</td>
              <td><span class="badge">{{ item.buildings_count || 0 }}</span></td>
              <td><span class="badge" :class="item.is_active ? 'badge-success' : 'badge-danger'">{{ item.is_active ? 'نشط' : 'غير نشط' }}</span></td>
              <td>
                <button class="btn-icon" @click="editItem(item)"><i class="pi pi-pencil"></i></button>
                <button class="btn-icon btn-danger" @click="deleteItem(item)"><i class="pi pi-trash"></i></button>
              </td>
            </tr>
            <tr v-if="items.length === 0">
              <td colspan="5" class="empty-cell">
                <i class="pi pi-map-marker"></i>
                <p>لا توجد مواقع مسجلة</p>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل موقع' : 'إضافة موقع'" modal :style="{ width: '500px' }">
      <div class="dialog-body">
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
  } catch (e) {
    console.error('Locations fetch error:', e)
    items.value = []
  }
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
      const { data } = await api.put(`/locations/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
    } else {
      const { data } = await api.post('/locations', form)
      items.value.unshift(data.data)
    }
    closeDialog()
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
