<template>
  <div class="page-view">
    <div class="page-toolbar">
      <Button label="إضافة مستخدم" icon="pi pi-plus" @click="showDialog = true" />
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="name" header="الاسم" sortable></Column>
        <Column field="email" header="البريد الإلكتروني" sortable></Column>
        <Column field="phone" header="الهاتف"></Column>
        <Column field="role" header="الدور" sortable>
          <template #body="s"><Tag :value="roleLabels[s.data.role]" :severity="roleSeverity[s.data.role]" /></template>
        </Column>
        <Column header="الحالة">
          <template #body="s">
            <Tag :value="s.data.is_active ? 'نشط' : 'معطل'" :severity="s.data.is_active ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="الإجراءات" style="width: 140px">
          <template #body="s">
            <Button icon="pi pi-pencil" severity="info" text rounded @click="editItem(s.data)" />
            <Button
              :icon="s.data.is_active ? 'pi pi-ban' : 'pi pi-check'"
              :severity="s.data.is_active ? 'danger' : 'success'"
              text rounded
              @click="toggleStatus(s.data)"
              :tooltip="s.data.is_active ? 'تعطيل' : 'تفعيل'"
            />
          </template>
        </Column>
        <template #empty>
          <div class="empty-state"><i class="pi pi-users"></i><p>لا توجد مستخدمين</p></div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل مستخدم' : 'إضافة مستخدم'" modal :style="{ width: '550px' }">
      <form @submit.prevent="saveItem">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الاسم</label>
            <InputText v-model="form.name" class="w-full" required />
          </div>
          <div class="form-field flex-1">
            <label>البريد الإلكتروني</label>
            <InputText v-model="form.email" type="email" class="w-full" required />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>كلمة المرور</label>
            <InputText v-model="form.password" type="password" class="w-full" :placeholder="isEditing ? 'اتركه فارغاً إذا لم ترد التغيير' : ''" />
          </div>
          <div class="form-field flex-1">
            <label>رقم الهاتف</label>
            <InputText v-model="form.phone" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الدور</label>
            <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" class="w-full" required />
          </div>
          <div class="form-field flex-1">
            <label>الحالة</label>
            <SelectButton v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" />
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

const items = ref([])
const showDialog = ref(false)
const isEditing = ref(false)

const form = reactive({ id: null, name: '', email: '', password: '', phone: '', role: 'employee', is_active: true })

const roleLabels = { super_admin: 'مدير النظام', employee: 'موظف', guard: 'حارس' }
const roleSeverity = { super_admin: 'danger', employee: 'info', guard: 'warn' }
const roleOptions = ref(Object.entries(roleLabels).map(([value, label]) => ({ value, label })))
const statusOptions = ref([{ label: 'نشط', value: true }, { label: 'معطل', value: false }])

onMounted(() => fetchItems())

async function fetchItems() {
  try { const { data } = await api.get('/users'); items.value = data.data } catch { items.value = [] }
}

function editItem(item) { Object.assign(form, item); form.password = ''; isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  Object.assign(form, { id: null, name: '', email: '', password: '', phone: '', role: 'employee', is_active: true })
}

async function saveItem() {
  try {
    if (!form.password) delete form.password
    if (isEditing.value) await api.put(`/users/${form.id}`, form)
    else await api.post('/users', form)
    closeDialog(); await fetchItems()
  } catch { /* */ }
}

async function toggleStatus(user) {
  try { await api.patch(`/users/${user.id}/toggle-status`); await fetchItems() } catch { /* */ }
}
</script>
