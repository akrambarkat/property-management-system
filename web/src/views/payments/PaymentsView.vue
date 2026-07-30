<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="بحث برقم الإيصال، المستأجر، أو الفاتورة..."
      emptyTitle="لا توجد عمليات سداد مسجلة"
      emptySubtitle="لم يتم العثور على أي دفعات تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> تسجيل دفعة سداد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('receipt_number')" field="receipt_number" header="رقم الإيصال" sortable>
          <template #body="slotProps">
            <span class="receipt-code">{{ slotProps.data.receipt_number || 'REC-' + slotProps.data.id }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('invoice.contract.tenant.first_name')" field="invoice.contract.tenant.first_name" header="المستأجر" sortable>
          <template #body="slotProps">
            <div class="tenant-cell">
              <div class="user-avatar-circle">
                <span>{{ slotProps.data.invoice?.contract?.tenant?.first_name?.charAt(0).toUpperCase() || 'M' }}</span>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.invoice?.contract?.tenant?.first_name }} {{ slotProps.data.invoice?.contract?.tenant?.last_name }}</span>
                <span class="sub-text">فاتورة #INV-{{ slotProps.data.invoice?.id }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('amount')" field="amount" header="المبلغ المدفوع" sortable>
          <template #body="slotProps">
            <span class="paid-amount">{{ formatCurrency(slotProps.data.amount) }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('payment_date')" field="payment_date" header="تاريخ الدفع" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.payment_date || '—' }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('payment_method')" field="payment_method" header="طريقة الدفع" sortable>
          <template #body="slotProps">
            <span class="method-pill">
              <i :class="slotProps.data.payment_method === 'cash' ? 'pi pi-money-bill' : 'pi pi-credit-card'"></i>
              {{ methodLabels[slotProps.data.payment_method] || slotProps.data.payment_method }}
            </span>
          </template>
        </Column>

        <!-- Actions -->
        <Column header="الإجراءات" style="width: 80px; text-align: center;" frozen alignFrozen="right">
          <template #body="slotProps">
            <TableActionMenu :items="getRowActions(slotProps.data)" />
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <!-- Create Payment Dialog -->
    <Dialog
      v-model:visible="showDialog"
      header="تسجيل دفعة سداد جديدة"
      modal
      :style="{ width: '560px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Invoice & Amount -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-receipt"></i>
            <span>الفاتورة والمبلغ</span>
          </div>

          <FormField
            label="الفاتورة المستحقة"
            required
            forId="pay-invoice"
            :errorMessage="errors.invoice_id"
            helpText="اختر الفاتورة المستهدفة بالسداد"
          >
            <Select
              id="pay-invoice"
              v-model="form.invoice_id"
              :options="unpaidInvoices"
              optionLabel="label"
              optionValue="id"
              placeholder="اختر الفاتورة المراد سدادها"
              class="w-full"
              filter
              @change="clearFieldError('invoice_id')"
            />
          </FormField>

          <FormField
            label="المبلغ المدفوع (₪)"
            required
            forId="pay-amount"
            :errorMessage="errors.amount"
            helpText="أدخل المبلغ المسدد للشيكل"
          >
            <InputNumber
              id="pay-amount"
              v-model="form.amount"
              class="w-full"
              :min="1"
              placeholder="أدخل المبلغ المدفوع"
              @input="clearFieldError('amount')"
            />
          </FormField>
        </div>

        <!-- Section 2: Method & Reference -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-wallet"></i>
            <span>تفاصيل عملية الدفع</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="تاريخ الدفع"
              forId="pay-date"
            >
              <DatePicker
                id="pay-date"
                v-model="form.payment_date"
                class="w-full"
                placeholder="اختر التاريخ"
              />
            </FormField>

            <FormField
              label="طريقة الدفع"
              forId="pay-method"
            >
            <Select
              id="pay-method"
              v-model="form.payment_method"
              :options="methodOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              filter
            />
            </FormField>
          </div>

          <FormField
            label="رقم مرجعي / رقم الشيك (اختياري)"
            forId="pay-ref"
            helpText="أدخل رقم الحوالة أو رقم الشيك في حال السداد البنكي"
          >
            <InputText
              id="pay-ref"
              v-model="form.reference_number"
              class="w-full"
              placeholder="أدخل الرقم المرجعي للعملية"
            />
          </FormField>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري التسجيل...' : 'تسجيل الدفعة' }}</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const unpaidInvoices = ref([])
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)

const form = reactive({ invoice_id: null, amount: null, payment_date: null, payment_method: 'cash', reference_number: '' })

const errors = reactive({
  invoice_id: '', amount: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'receipt_number', header: 'رقم الإيصال' },
  { field: 'invoice.contract.tenant.first_name', header: 'المستأجر' },
  { field: 'amount', header: 'المبلغ المدفوع' },
  { field: 'payment_date', header: 'تاريخ الدفع' },
  { field: 'payment_method', header: 'طريقة الدفع' }
]

const methodLabels = { cash: 'نقدي', bank_transfer: 'تحويل بنكي', cheque: 'شيك' }
const methodOptions = ref([
  { label: 'نقدي', value: 'cash' },
  { label: 'تحويل بنكي', value: 'bank_transfer' },
  { label: 'شيك', value: 'cheque' }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.invoice_id) {
    errors.invoice_id = 'يرجى اختيار الفاتورة المستحقة بالسداد'
    isValid = false
  }

  if (form.amount === null || form.amount === undefined || form.amount <= 0) {
    errors.amount = 'يرجى إدخال مبلغ سداد أهلي أكبر من صفر'
    isValid = false
  }

  return isValid
}

function isFormDirty() {
  return JSON.stringify(form) !== initialFormState
}

function getRowActions(row) {
  return [
    {
      label: 'طباعة الإيصال',
      icon: 'pi pi-print',
      command: () => printReceipt(row)
    }
  ]
}

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchInvoices(); fetchItems() })

async function fetchInvoices() {
  try {
    const { data } = await api.get('/invoices', { params: { status: 'unpaid' } })
    unpaidInvoices.value = data.data.map(i => ({
      ...i,
      label: `فاتورة #INV-${i.id} - المستأجر: ${i.contract?.tenant?.first_name || ''} ${i.contract?.tenant?.last_name || ''} (مستحق: ${formatCurrency(i.total_amount)})`
    }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get('/payments')
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل سجلات الدفع: ' + (err.response?.data?.message || err.message))
    items.value = []
  } finally {
    loading.value = false
  }
}


function openCreateDialog() {
  resetForm()
  showDialog.value = true
}

function resetForm() {
  Object.assign(form, { invoice_id: null, amount: null, payment_date: null, payment_method: 'cash', reference_number: '' })
  Object.keys(errors).forEach(key => errors[key] = '')
}

function closeDialog() {
  if (isFormDirty() && !confirm('لديك تغييرات غير محفوظة، هل أنت متأكد من الإغلاق؟')) {
    return
  }
  showDialog.value = false
  resetForm()
}

function handleDialogHide() {
  resetForm()
}

async function saveItem() {
  if (!validateForm()) return

  saving.value = true
  try {
    await api.post('/payments', form)
    toast.success('تم تسجيل الدفعة بنجاح وإصدار الإيصال')
    showDialog.value = false
    resetForm()
    await fetchItems()
    await fetchInvoices()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر تسجيل الدفعة')
  } finally {
    saving.value = false
  }
}

function printReceipt(payment) {
  window.open(`${api.defaults.baseURL}/payments/${payment.id}/receipt`, '_blank')
}
</script>

<style scoped>
.receipt-code {
  font-family: monospace;
  font-weight: 700;
  color: var(--accent);
  background: #EFF6FF;
  padding: 3px 8px;
  border-radius: var(--radius-xs);
}

.tenant-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.user-avatar-circle {
  width: 36px;
  height: 36px;
  background: #EFF6FF;
  color: var(--accent);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
}
.cell-text {
  display: flex;
  flex-direction: column;
}
.font-bold {
  font-weight: 600;
  color: var(--text-primary);
}
.sub-text {
  font-size: 12px;
  color: var(--text-secondary);
}

.paid-amount {
  font-weight: 700;
  color: var(--success);
}

.date-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.method-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}
</style>
