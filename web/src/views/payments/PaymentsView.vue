<template>
  <div class="page-view">
    <div class="page-toolbar">
      <Button label="تسجيل دفعة" icon="pi pi-plus" @click="showDialog = true" />
    </div>

    <Card>
      <DataTable :value="items" stripedRows paginator :rows="15">
        <Column field="receipt_number" header="رقم الإيصال" sortable></Column>
        <Column field="invoice.invoice_number" header="الفاتورة"></Column>
        <Column header="المستأجر">
          <template #body="s">{{ s.data.invoice?.contract?.tenant?.first_name }} {{ s.data.invoice?.contract?.tenant?.last_name }}</template>
        </Column>
        <Column field="amount" header="المبلغ" sortable>
          <template #body="s">{{ formatCurrency(s.data.amount) }}</template>
        </Column>
        <Column field="payment_date" header="تاريخ الدفع" sortable></Column>
        <Column field="payment_method" header="طريقة الدفع">
          <template #body="s">{{ methodLabels[s.data.payment_method] }}</template>
        </Column>
        <Column header="الإجراءات" style="width: 100px">
          <template #body="s">
            <Button icon="pi pi-print" severity="info" text rounded @click="printReceipt(s.data)" tooltip="إيصال" />
          </template>
        </Column>
        <template #empty>
          <div class="empty-state"><i class="pi pi-wallet"></i><p>لا توجد مدفوعات</p></div>
        </template>
      </DataTable>
    </Card>

    <Dialog v-model:visible="showDialog" header="تسجيل دفعة جديدة" modal :style="{ width: '500px' }">
      <form @submit.prevent="saveItem">
        <div class="form-field">
          <label>الفاتورة</label>
          <Select v-model="form.invoice_id" :options="unpaidInvoices" optionLabel="label" optionValue="id" placeholder="اختر الفاتورة" required class="w-full" />
        </div>
        <div class="form-field">
          <label>المبلغ</label>
          <InputNumber v-model="form.amount" class="w-full" :min="1" />
        </div>
        <div class="form-row">
          <div class="form-field flex-1">
            <label>تاريخ الدفع</label>
            <DatePicker v-model="form.payment_date" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>طريقة الدفع</label>
            <Select v-model="form.payment_method" :options="methodOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
        <div class="form-field">
          <label>رقم مرجعي (اختياري)</label>
          <InputText v-model="form.reference_number" class="w-full" />
        </div>
        <div class="form-actions">
          <Button label="إلغاء" severity="secondary" @click="closeDialog" />
          <Button label="تسجيل الدفعة" type="submit" />
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
const unpaidInvoices = ref([])
const showDialog = ref(false)

const form = reactive({ invoice_id: null, amount: null, payment_date: null, payment_method: 'cash', reference_number: '' })

const methodLabels = { cash: 'نقدي', bank_transfer: 'تحويل بنكي', cheque: 'شيك' }
const methodOptions = ref([
  { label: 'نقدي', value: 'cash' },
  { label: 'تحويل بنكي', value: 'bank_transfer' },
  { label: 'شيك', value: 'cheque' }
])

function formatCurrency(amount) { return `${Number(amount).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}` }

onMounted(() => { fetchInvoices(); fetchItems() })

async function fetchInvoices() {
  try {
    const { data } = await api.get('/invoices?status=unpaid')
    unpaidInvoices.value = data.data.map(i => ({ ...i, label: `${i.invoice_number} - ${i.contract?.tenant?.first_name} (${formatCurrency(i.total_amount - i.paid_amount)})` }))
  } catch {}
}

async function fetchItems() {
  try { const { data } = await api.get('/payments'); items.value = data.data } catch { items.value = [] }
}

function closeDialog() {
  showDialog.value = false
  Object.assign(form, { invoice_id: null, amount: null, payment_date: null, payment_method: 'cash', reference_number: '' })
}

async function saveItem() {
  try { await api.post('/payments', form); closeDialog(); await fetchItems(); await fetchInvoices() } catch { /* */ }
}

function printReceipt(payment) { window.open(api.defaults.baseURL + `/payments/${payment.id}/receipt`, '_blank') }
</script>
