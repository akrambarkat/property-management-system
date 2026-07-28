<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.status" :options="statusFilter" optionLabel="label" optionValue="value" placeholder="الحالة" showClear @change="fetchItems" />
        <Select v-model="filters.priority" :options="priorityFilter" optionLabel="label" optionValue="value" placeholder="الأولوية" showClear @change="fetchItems" />
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة طلب</button>
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="unit.unit_number" header="الوحدة">
          <template #body="s">{{ s.data.unit?.unit_number }}</template>
        </Column>
        <Column field="description" header="الوصف"></Column>
        <Column field="priority" header="الأولوية">
          <template #body="s"><Tag :value="priorityLabels[s.data.priority]" :severity="prioritySeverity[s.data.priority]" /></template>
        </Column>
        <Column field="status" header="الحالة">
          <template #body="s"><span :class="'status-badge status-' + s.data.status">{{ maintStatusLabels[s.data.status] }}</span></template>
        </Column>
        <Column field="created_at" header="تاريخ الطلب" sortable></Column>
        <Column header="الإجراءات" style="width: 100px">
          <template #body="s">
            <button class="btn-icon" @click="editItem(s.data)"><i class="pi pi-pencil"></i></button>
          </template>
        </Column>
        <template #empty>
          <div class="empty-state"><i class="pi pi-wrench"></i><p>لا توجد طلبات صيانة</p></div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" header="طلب صيانة" modal :style="{ width: '550px' }">
      <div class="dialog-body">
        <div class="form-field">
          <label>الوحدة</label>
          <Select v-model="form.unit_id" :options="units" optionLabel="label" optionValue="id" placeholder="اختر الوحدة" required class="w-full" />
        </div>
        <div class="form-field">
          <label>الوصف</label>
          <Textarea v-model="form.description" class="w-full" rows="3" required />
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الأولوية</label>
            <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>الحالة</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
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
const units = ref([])
const showDialog = ref(false)
const isEditing = ref(false)
const filters = reactive({ status: null, priority: null })

const form = reactive({ id: null, unit_id: null, description: '', priority: 'medium', status: 'pending' })

const priorityLabels = { low: 'منخفضة', medium: 'متوسطة', high: 'عالية', urgent: 'عاجلة' }
const prioritySeverity = { low: 'info', medium: 'warn', high: 'danger', urgent: 'danger' }
const maintStatusLabels = { pending: 'قيد الانتظار', in_progress: 'قيد التنفيذ', completed: 'مكتملة', cancelled: 'ملغية' }

const statusFilter = ref(Object.entries(maintStatusLabels).map(([value, label]) => ({ value, label })))
const statusOptions = ref(Object.entries(maintStatusLabels).map(([value, label]) => ({ value, label })))
const priorityFilter = ref(Object.entries(priorityLabels).map(([value, label]) => ({ value, label })))
const priorityOptions = ref(Object.entries(priorityLabels).map(([value, label]) => ({ value, label })))

onMounted(() => { fetchUnits(); fetchItems() })

async function fetchUnits() {
  try { const { data } = await api.get('/units'); units.value = data.data.map(u => ({ ...u, label: `#${u.unit_number} - ${u.building?.name}` })) } catch {}
}

async function fetchItems() {
  try {
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.priority) params.priority = filters.priority
    const { data } = await api.get('/maintenance', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function editItem(item) { Object.assign(form, item); isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  Object.assign(form, { id: null, unit_id: null, description: '', priority: 'medium', status: 'pending' })
}

async function saveItem() {
  try {
    if (isEditing.value) await api.put(`/maintenance/${form.id}`, form)
    else await api.post('/maintenance', form)
    closeDialog(); await fetchItems()
  } catch { /* */ }
}
</script>
