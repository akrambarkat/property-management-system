<template>
  <div class="page-view">
    <!-- Feedback Messages -->
    <div v-if="errorMsg" class="error-banner">
      <i class="pi pi-exclamation-circle"></i>
      <span>{{ errorMsg }}</span>
      <span class="close-banner" @click="errorMsg = ''">×</span>
    </div>

    <transition name="fade">
      <div v-if="toastMsg" class="toast-banner">
        <i class="pi pi-check-circle"></i>
        <span>{{ toastMsg }}</span>
      </div>
    </transition>

    <!-- Page Toolbar with Horizontal SaaS Filters & Actions -->
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <div class="search-input-wrapper">
          <i class="pi pi-search search-icon"></i>
          <InputText v-model="searchQuery" placeholder="بحث برقم الإيصال أو المستأجر..." class="search-input-field" />
        </div>
      </div>

      <div class="toolbar-actions">
        <button class="btn-secondary" @click="exportCSV" title="تصدير البيانات">
          <i class="pi pi-download"></i> تصدير
        </button>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> تسجيل دفعة جديدة
        </button>
      </div>
    </div>

    <!-- Enterprise SaaS Card Table Layout -->
    <div class="table-container-card">
      <DataTable
        ref="dt"
        :value="filteredItems"
        stripedRows
        paginator
        :rows="12"
        :loading="loading"
        responsiveLayout="scroll"
        class="custom-saas-table"
      >
        <Column field="receipt_number" header="رقم الإيصال" sortable>
          <template #body="slotProps">
            <div class="payment-cell">
              <div class="icon-avatar">
                <i class="pi pi-wallet text-green"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">#{{ slotProps.data.receipt_number }}</span>
                <span class="sub-text">فاتورة: #{{ slotProps.data.invoice?.invoice_number || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column header="المستأجر" sortable>
          <template #body="slotProps">
            <span class="tenant-name" v-if="slotProps.data.invoice?.contract?.tenant">
              <i class="pi pi-user text-muted"></i>
              {{ slotProps.data.invoice.contract.tenant.first_name }} {{ slotProps.data.invoice.contract.tenant.last_name }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="amount" header="المبلغ المدفوع" sortable>
          <template #body="slotProps">
            <span class="payment-amount">{{ formatCurrency(slotProps.data.amount) }}</span>
          </template>
        </Column>

        <Column field="payment_date" header="تاريخ الدفع" sortable>
          <template #body="slotProps">
            <span class="date-text"><i class="pi pi-calendar text-muted"></i> {{ slotProps.data.payment_date || '—' }}</span>
          </template>
        </Column>

        <Column field="payment_method" header="طريقة الدفع">
          <template #body="slotProps">
            <span class="method-badge">
              {{ methodLabels[slotProps.data.payment_method] || slotProps.data.payment_method }}
            </span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 100px; text-align: center;">
          <template #body="slotProps">
            <div class="action-buttons-group">
              <button class="btn-icon" @click="printReceipt(slotProps.data)" title="طباعة الإيصال">
                <i class="pi pi-print"></i>
              </button>
            </div>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-wallet"></i>
            <p>لا توجد مدفوعات مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create Payment Dialog -->
    <Dialog
      v-model:visible="showDialog"
      header="تسجيل دفعة سداد جديدة"
      modal
      :style="{ width: '540px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-field">
          <label>الفاتورة المستحقة <span class="required">*</span></label>
          <Select v-model="form.invoice_id" :options="unpaidInvoices" optionLabel="label" optionValue="id" placeholder="اختر الفاتورة المراد سدادها" class="w-full" />
        </div>

        <div class="form-field">
          <label>المبلغ المدفوع (₪) <span class="required">*</span></label>
          <InputNumber v-model="form.amount" class="w-full" :min="1" placeholder="أدخل المبلغ" />
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>تاريخ الدفع</label>
            <DatePicker v-model="form.payment_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
          <div class="form-field flex-1">
            <label>طريقة الدفع</label>
            <Select v-model="form.payment_method" :options="methodOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>

        <div class="form-field">
          <label>رقم مرجعي / رقم الشيك (اختياري)</label>
          <InputText v-model="form.reference_number" class="w-full" placeholder="أدخل الرقم المرجعي للعملية" />
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
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/services/api'

const dt = ref(null)
const items = ref([])
const unpaidInvoices = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const form = reactive({ invoice_id: null, amount: null, payment_date: null, payment_method: 'cash', reference_number: '' })

const methodLabels = { cash: 'نقدي', bank_transfer: 'تحويل بنكي', cheque: 'شيك' }
const methodOptions = ref([
  { label: 'نقدي', value: 'cash' },
  { label: 'تحويل بنكي', value: 'bank_transfer' },
  { label: 'شيك', value: 'cheque' }
])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.receipt_number?.toString().toLowerCase().includes(q) ||
    item.invoice?.contract?.tenant?.first_name?.toLowerCase().includes(q) ||
    item.invoice?.contract?.tenant?.last_name?.toLowerCase().includes(q)
  )
})

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchInvoices(); fetchItems() })

async function fetchInvoices() {
  try {
    const { data } = await api.get('/invoices?status=unpaid')
    unpaidInvoices.value = data.data.map(i => ({
      ...i,
      label: `فاتورة #${i.invoice_number} - ${i.contract?.tenant?.first_name || ''} (${formatCurrency(i.total_amount - i.paid_amount)})`
    }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const { data } = await api.get('/payments')
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل قائمة المدفوعات: ' + (err.response?.data?.message || err.message)
    items.value = []
  } finally {
    loading.value = false
  }
}

function showToast(msg) {
  toastMsg.value = msg
  setTimeout(() => { toastMsg.value = '' }, 3000)
}

function openCreateDialog() {
  closeDialog()
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  Object.assign(form, { invoice_id: null, amount: null, payment_date: null, payment_method: 'cash', reference_number: '' })
}

async function saveItem() {
  if (!form.invoice_id || !form.amount) {
    errorMsg.value = 'يرجى اختيار الفاتورة والمبلغ'
    return
  }

  saving.value = true
  try {
    await api.post('/payments', form)
    showToast('تم تسجيل الدفعة بنجاح')
    closeDialog()
    await fetchItems()
    await fetchInvoices()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر تسجيل الدفعة'
  } finally {
    saving.value = false
  }
}

function printReceipt(payment) {
  try {
    const url = api.defaults.baseURL + `/payments/${payment.id}/receipt`
    window.open(url, '_blank')
  } catch (err) { console.error(err) }
}

function exportCSV() {
  if (dt.value) dt.value.exportCSV()
}
</script>

<style scoped>
.error-banner {
  background: var(--danger-bg);
  color: var(--danger);
  border: 1px solid #FECACA;
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13.5px;
}
.close-banner {
  margin-right: auto;
  cursor: pointer;
  font-size: 16px;
}

.toast-banner {
  position: fixed;
  top: 80px;
  left: 30px;
  background: #10B981;
  color: #FFFFFF;
  padding: 12px 20px;
  border-radius: var(--radius-sm);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 2000;
  font-size: 13.5px;
  font-weight: 500;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  right: 12px;
  color: var(--text-muted);
  font-size: 0.9rem;
}
.search-input-field {
  padding-right: 36px !important;
  width: 260px !important;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.table-container-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.payment-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: #ECFDF5;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
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

.tenant-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
  font-weight: 500;
}

.payment-amount {
  font-weight: 700;
  color: var(--success);
}

.date-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.method-badge {
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}

.form-row {
  display: flex;
  gap: 14px;
}

.action-buttons-group {
  display: flex;
  align-items: center;
  gap: 4px;
  justify-content: center;
}

.required {
  color: var(--danger);
}
</style>
