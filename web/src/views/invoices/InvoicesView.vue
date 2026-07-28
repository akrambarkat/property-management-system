<template>
  <div class="page-view">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.status" :options="statusFilter" optionLabel="label" optionValue="value" placeholder="الحالة" showClear @change="fetchItems" />
        <DatePicker v-model="filters.from" placeholder="من تاريخ" @change="fetchItems" />
        <DatePicker v-model="filters.to" placeholder="إلى تاريخ" @change="fetchItems" />
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus"></i> إضافة فاتورة</button>
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="invoice_number" header="رقم الفاتورة" sortable></Column>
        <Column field="contract.tenant.first_name" header="المستأجر">
          <template #body="s">{{ s.data.contract?.tenant?.first_name }} {{ s.data.contract?.tenant?.last_name }}</template>
        </Column>
        <Column field="issue_date" header="تاريخ الإصدار" sortable></Column>
        <Column field="due_date" header="تاريخ الاستحقاق" sortable></Column>
        <Column field="total_amount" header="المجموع" sortable>
          <template #body="s">{{ formatCurrency(s.data.total_amount) }}</template>
        </Column>
        <Column field="paid_amount" header="المدفوع" sortable>
          <template #body="s">{{ formatCurrency(s.data.paid_amount) }}</template>
        </Column>
        <Column header="الحالة">
          <template #body="s"><span :class="'status-badge status-' + s.data.status">{{ invStatusLabels[s.data.status] }}</span></template>
        </Column>
        <Column header="الإجراءات" style="width: 160px">
          <template #body="s">
            <button class="btn-icon" @click="printInvoice(s.data)" title="طباعة"><i class="pi pi-print"></i></button>
            <button v-if="s.data.status !== 'paid'" class="btn-icon" @click="payInvoice(s.data)" title="تسديد"><i class="pi pi-dollar"></i></button>
          </template>
        </Column>
        <template #empty>
          <div class="empty-state"><i class="pi pi-receipt"></i><p>لا توجد فواتير</p></div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" header="إضافة فاتورة" modal :style="{ width: '650px' }">
      <div class="dialog-body">
        <div class="form-field">
          <label>العقد</label>
          <Select v-model="form.contract_id" :options="contracts" optionLabel="label" optionValue="id" placeholder="اختر العقد" required class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>تاريخ الإصدار</label>
            <DatePicker v-model="form.issue_date" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>تاريخ الاستحقاق</label>
            <DatePicker v-model="form.due_date" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الإيجار</label>
            <InputNumber v-model="form.rent_amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>الكهرباء</label>
            <InputNumber v-model="form.electricity_amount" class="w-full" :min="0" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الماء</label>
            <InputNumber v-model="form.water_amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>الإنترنت</label>
            <InputNumber v-model="form.internet_amount" class="w-full" :min="0" />
          </div>
        </div>
        <div class="form-field">
          <label>خدمات إضافية</label>
          <InputNumber v-model="form.services_amount" class="w-full" :min="0" />
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
const contracts = ref([])
const showDialog = ref(false)

const filters = reactive({ status: null, from: null, to: null })

const form = reactive({
  contract_id: null, issue_date: null, due_date: null, rent_amount: 0,
  electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0
})

const invStatusLabels = { paid: 'مدفوعة', unpaid: 'غير مدفوعة', partial: 'جزئية', overdue: 'متأخرة' }
const statusFilter = ref([
  { label: 'مدفوعة', value: 'paid' }, { label: 'غير مدفوعة', value: 'unpaid' },
  { label: 'متأخرة', value: 'overdue' }, { label: 'جزئية', value: 'partial' }
])

function formatCurrency(amount) { return `${Number(amount).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}` }

onMounted(() => { fetchContracts(); fetchItems() })

async function fetchContracts() {
  try { const { data } = await api.get('/contracts?status=active'); contracts.value = data.data.map(c => ({ ...c, label: `${c.contract_number} - ${c.tenant?.first_name}` })) } catch {}
}

async function fetchItems() {
  try {
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    const { data } = await api.get('/invoices', { params })
    items.value = data.data
  } catch { items.value = [] }
}

function closeDialog() {
  showDialog.value = false
  Object.assign(form, { contract_id: null, issue_date: null, due_date: null, rent_amount: 0, electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0 })
}

async function saveItem() {
  try { await api.post('/invoices', form); closeDialog(); await fetchItems() } catch { /* */ }
}

async function printInvoice(inv) { window.open(await api.defaults.baseURL + `/invoices/${inv.id}/pdf`, '_blank') }

async function payInvoice(inv) {
  try { await api.patch(`/invoices/${inv.id}/pay`); await fetchItems() } catch { /* */ }
}
</script>
