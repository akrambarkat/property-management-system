<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.status" :options="statusFilter" optionLabel="label" optionValue="value" placeholder="الحالة" showClear @change="fetchItems" />
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة عقد</button>
    </div>

    <Card>
      <template #content>
        <DataTable :value="items" stripedRows paginator :rows="15">
          <Column field="contract_number" header="رقم العقد" sortable></Column>
          <Column field="tenant.first_name" header="المستأجر" sortable>
            <template #body="s">{{ s.data.tenant?.first_name }} {{ s.data.tenant?.last_name }}</template>
          </Column>
          <Column field="unit.unit_number" header="الوحدة">
            <template #body="s">{{ s.data.unit?.unit_number }} - {{ s.data.unit?.building?.name }}</template>
          </Column>
          <Column field="rent_amount" header="الإيجار" sortable>
            <template #body="s">{{ formatCurrency(s.data.rent_amount) }}</template>
          </Column>
          <Column field="start_date" header="تاريخ البداية" sortable></Column>
          <Column field="end_date" header="تاريخ النهاية" sortable></Column>
          <Column field="contract_type" header="النوع">
            <template #body="s"><Tag :value="s.data.contract_type === 'monthly' ? 'شهري' : 'سنوي'" /></template>
          </Column>
          <Column header="الحالة">
            <template #body="s"><span :class="'status-badge status-' + s.data.status">{{ statusLabels[s.data.status] }}</span></template>
          </Column>
          <Column header="الإجراءات" style="width: 120px">
            <template #body="s">
              <button class="btn-icon" @click="editItem(s.data)"><i class="pi pi-pencil"></i></button>
              <button v-if="s.data.status === 'active'" class="btn-icon btn-danger" @click="terminateContract(s.data)" title="إنهاء العقد"><i class="pi pi-times"></i></button>
            </template>
          </Column>
          <template #empty>
            <div class="empty-state"><i class="pi pi-file"></i><p>لا توجد عقود</p></div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showDialog" :header="isEditing ? 'تعديل عقد' : 'إضافة عقد'" modal :style="{ width: '650px' }">
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الوحدة</label>
            <Select v-model="form.unit_id" :options="units" optionLabel="label" optionValue="id" placeholder="اختر الوحدة" required class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>المستأجر</label>
            <Select v-model="form.tenant_id" :options="tenants" optionLabel="label" optionValue="id" placeholder="اختر المستأجر" required class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>تاريخ البداية</label>
            <DatePicker v-model="form.start_date" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>تاريخ النهاية</label>
            <DatePicker v-model="form.end_date" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الإيجار (₪)</label>
            <InputNumber v-model="form.rent_amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>نوع العقد</label>
            <Select v-model="form.contract_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
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
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const items = ref([])
const units = ref([])
const tenants = ref([])
const showDialog = ref(false)
const isEditing = ref(false)
const filters = reactive({ status: null })

const form = reactive({ id: null, unit_id: null, tenant_id: null, start_date: null, end_date: null, rent_amount: null, contract_type: 'monthly' })

const statusLabels = { active: 'نشط', expired: 'منتهي', terminated: 'ملغي', renewed: 'مجدد' }
const statusFilter = ref([{ label: 'نشط', value: 'active' }, { label: 'منتهي', value: 'expired' }, { label: 'ملغي', value: 'terminated' }])
const typeOptions = ref([{ label: 'شهري', value: 'monthly' }, { label: 'سنوي', value: 'yearly' }])

function formatCurrency(amount) { return `${Number(amount).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}` }

onMounted(() => { fetchUnits(); fetchTenants(); fetchItems() })

async function fetchUnits() {
  try { const { data } = await api.get('/units'); units.value = data.data.map(u => ({ ...u, label: `#${u.unit_number} - ${u.building?.name}` })) } catch {}
}

async function fetchTenants() {
  try { const { data } = await api.get('/tenants'); tenants.value = data.data.map(t => ({ ...t, label: `${t.first_name} ${t.last_name}` })) } catch {}
}

async function fetchItems() {
  try {
    const params = filters.status ? { status: filters.status } : {}
    const { data } = await api.get('/contracts', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function editItem(item) { Object.assign(form, item); isEditing.value = true; showDialog.value = true }

function closeDialog() {
  showDialog.value = false; isEditing.value = false
  form.id = null; form.unit_id = null; form.tenant_id = null
  form.start_date = null; form.end_date = null; form.rent_amount = null; form.contract_type = 'monthly'
}

async function saveItem() {
  try {
    if (isEditing.value) await api.put(`/contracts/${form.id}`, form)
    else await api.post('/contracts', form)
    closeDialog(); await fetchItems()
  } catch { /* */ }
}

async function terminateContract(item) {
  if (!confirm('هل أنت متأكد من إنهاء هذا العقد؟')) return
  try { await api.patch(`/contracts/${item.id}/terminate`); await fetchItems() } catch { /* */ }
}
</script>
