<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.category" :options="categoryFilter" optionLabel="label" optionValue="value" placeholder="التصنيف" showClear @change="fetchItems" />
      </div>
      <Button label="إضافة مصروف" icon="pi pi-plus" @click="showDialog = true" />
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="building.name" header="المبنى" sortable></Column>
        <Column field="category" header="التصنيف" sortable>
          <template #body="s"><Tag :value="categoryLabels[s.data.category]" /></template>
        </Column>
        <Column field="amount" header="المبلغ" sortable>
          <template #body="s">{{ formatCurrency(s.data.amount) }}</template>
        </Column>
        <Column field="expense_date" header="التاريخ" sortable></Column>
        <Column field="description" header="الوصف"></Column>
        <Column header="الإجراءات" style="width: 100px">
          <template #body="s">
            <Button icon="pi pi-pencil" severity="info" text rounded @click="editItem(s.data)" />
            <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteItem(s.data)" />
          </template>
        </Column>
        <template #empty>
          <div class="empty-state"><i class="pi pi-money-bill"></i><p>لا توجد مصروفات</p></div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل مصروف' : 'إضافة مصروف'" modal :style="{ width: '550px' }">
      <form @submit.prevent="saveItem">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>المبنى</label>
            <Select v-model="form.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="اختر المبنى" required class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>التصنيف</label>
            <Select v-model="form.category" :options="categoryOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>المبلغ (₪)</label>
            <InputNumber v-model="form.amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>التاريخ</label>
            <DatePicker v-model="form.expense_date" class="w-full" />
          </div>
        </div>
        <div class="form-field">
          <label>الوصف</label>
          <Textarea v-model="form.description" class="w-full" rows="2" />
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
const buildings = ref([])
const showDialog = ref(false)
const isEditing = ref(false)
const filters = reactive({ category: null })

const form = reactive({ id: null, building_id: null, category: 'general', amount: null, expense_date: null, description: '' })

const categoryLabels = { maintenance: 'صيانة', plumbing: 'سباكة', electrical: 'كهرباء', cleaning: 'نظافة', security: 'أمن', general: 'عام' }
const categoryFilter = ref(Object.entries(categoryLabels).map(([value, label]) => ({ value, label })))
const categoryOptions = ref(Object.entries(categoryLabels).map(([value, label]) => ({ value, label })))

function formatCurrency(amount) { return `${Number(amount).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}` }

onMounted(() => { fetchBuildings(); fetchItems() })

async function fetchBuildings() {
  try { const { data } = await api.get('/buildings'); buildings.value = data.data } catch {}
}

async function fetchItems() {
  try {
    const params = filters.category ? { category: filters.category } : {}
    const { data } = await api.get('/expenses', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function editItem(item) { Object.assign(form, { ...item, expense_date: item.expense_date?.split('T')[0] }); isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  Object.assign(form, { id: null, building_id: null, category: 'general', amount: null, expense_date: null, description: '' })
}

async function saveItem() {
  try {
    if (isEditing.value) await api.put(`/expenses/${form.id}`, form)
    else await api.post('/expenses', form)
    closeDialog(); await fetchItems()
  } catch { /* */ }
}

async function deleteItem(item) {
  if (!confirm('هل أنت متأكد من حذف هذا المصروف؟')) return
  try { await api.delete(`/expenses/${item.id}`); await fetchItems() } catch { /* */ }
}
</script>
