<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <IconField>
          <InputIcon><i class="pi pi-search" /></InputIcon>
          <InputText v-model="filters.search" placeholder="بحث بالاسم أو رقم الهوية..." @input="fetchItems" />
        </IconField>
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة مستأجر</button>
    </div>

    <Card>
      <template #content>
        <DataTable :value="items" stripedRows paginator :rows="15">
          <Column field="first_name" header="الاسم" sortable>
            <template #body="slotProps">{{ slotProps.data.first_name }} {{ slotProps.data.last_name }}</template>
          </Column>
          <Column field="id_number" header="رقم الهوية" sortable></Column>
          <Column field="phone" header="الهاتف"></Column>
          <Column field="email" header="البريد الإلكتروني"></Column>
          <Column header="الوحدة الحالية">
            <template #body="slotProps">
              <Tag v-if="slotProps.data.current_unit" :value="slotProps.data.current_unit.unit_number" severity="info" />
              <span v-else class="text-muted">—</span>
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
            <div class="empty-state"><i class="pi pi-users"></i><p>لا يوجد مستأجرين</p></div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل مستأجر' : 'إضافة مستأجر'" modal :style="{ width: '600px' }">
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الاسم الأول</label>
            <InputText v-model="form.first_name" class="w-full" required />
          </div>
          <div class="form-field flex-1">
            <label>اسم العائلة</label>
            <InputText v-model="form.last_name" class="w-full" required />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>رقم الهوية</label>
            <InputText v-model="form.id_number" class="w-full" required />
          </div>
          <div class="form-field flex-1">
            <label>الهاتف</label>
            <InputText v-model="form.phone" class="w-full" />
          </div>
        </div>
        <div class="form-field">
          <label>البريد الإلكتروني</label>
          <InputText v-model="form.email" type="email" class="w-full" />
        </div>
        <div class="form-field">
          <label>العنوان</label>
          <Textarea v-model="form.address" class="w-full" rows="2" />
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
const filters = reactive({ search: '' })

const form = reactive({
  id: null, first_name: '', last_name: '', id_number: '', phone: '', email: '', address: ''
})

onMounted(() => fetchItems())

async function fetchItems() {
  try {
    const params = filters.search ? { search: filters.search } : {}
    const { data } = await api.get('/tenants', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function editItem(item) { Object.assign(form, item); isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  form.id = null; form.first_name = ''; form.last_name = ''; form.id_number = ''
  form.phone = ''; form.email = ''; form.address = ''
}

async function saveItem() {
  try {
    if (isEditing.value) await api.put(`/tenants/${form.id}`, form)
    else await api.post('/tenants', form)
    closeDialog(); await fetchItems()
  } catch { /* */ }
}

async function deleteItem(item) {
  if (!confirm('هل أنت متأكد من حذف هذا المستأجر؟')) return
  try { await api.delete(`/tenants/${item.id}`); await fetchItems() } catch { /* */ }
}
</script>
