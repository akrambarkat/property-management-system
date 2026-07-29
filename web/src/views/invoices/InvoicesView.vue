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
          <InputText v-model="searchQuery" placeholder="بحث برقم الفاتورة أو المستأجر..." class="search-input-field" />
        </div>
        <Select
          v-model="filters.status"
          :options="statusFilter"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الحالات"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
        <DatePicker v-model="filters.from" placeholder="من تاريخ" @change="fetchItems" class="filter-datepicker" />
        <DatePicker v-model="filters.to" placeholder="إلى تاريخ" @change="fetchItems" class="filter-datepicker" />
      </div>

      <div class="toolbar-actions">
        <button class="btn-secondary" @click="exportCSV" title="تصدير البيانات">
          <i class="pi pi-download"></i> تصدير
        </button>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة فاتورة جديد
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
        <Column field="invoice_number" header="رقم الفاتورة" sortable>
          <template #body="slotProps">
            <div class="invoice-cell">
              <div class="icon-avatar">
                <i class="pi pi-receipt text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">#{{ slotProps.data.invoice_number }}</span>
                <span class="sub-text">إصدار: {{ slotProps.data.issue_date || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="contract.tenant.first_name" header="المستأجر" sortable>
          <template #body="slotProps">
            <span class="tenant-name" v-if="slotProps.data.contract?.tenant">
              <i class="pi pi-user text-muted"></i>
              {{ slotProps.data.contract.tenant.first_name }} {{ slotProps.data.contract.tenant.last_name }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="due_date" header="تاريخ الاستحقاق" sortable>
          <template #body="slotProps">
            <span class="date-text"><i class="pi pi-calendar text-muted"></i> {{ slotProps.data.due_date || '—' }}</span>
          </template>
        </Column>

        <Column field="total_amount" header="المبلغ الإجمالي" sortable>
          <template #body="slotProps">
            <span class="amount-total">{{ formatCurrency(slotProps.data.total_amount) }}</span>
          </template>
        </Column>

        <Column field="paid_amount" header="المدفوع" sortable>
          <template #body="slotProps">
            <span class="amount-paid">{{ formatCurrency(slotProps.data.paid_amount) }}</span>
          </template>
        </Column>

        <Column header="الحالة">
          <template #body="slotProps">
            <span :class="'status-badge status-' + slotProps.data.status">
              {{ invStatusLabels[slotProps.data.status] || slotProps.data.status }}
            </span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 120px; text-align: center;">
          <template #body="slotProps">
            <div class="action-buttons-group">
              <button class="btn-icon" @click="printInvoice(slotProps.data)" title="طباعة الفاتورة">
                <i class="pi pi-print"></i>
              </button>
              <button v-if="slotProps.data.status !== 'paid'" class="btn-icon text-success" @click="payInvoice(slotProps.data)" title="تسديد الدفعة">
                <i class="pi pi-dollar"></i>
              </button>
            </div>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-receipt"></i>
            <p>لا توجد فواتير مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create Invoice Dialog -->
    <Dialog
      v-model:visible="showDialog"
      header="إضافة فاتورة تحصيل جديدة"
      modal
      :style="{ width: '640px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-field">
          <label>العقد <span class="required">*</span></label>
          <Select v-model="form.contract_id" :options="contracts" optionLabel="label" optionValue="id" placeholder="اختر العقد والمرتبط به المستأجر" class="w-full" />
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>تاريخ الإصدار</label>
            <DatePicker v-model="form.issue_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
          <div class="form-field flex-1">
            <label>تاريخ الاستحقاق</label>
            <DatePicker v-model="form.due_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>قيمة الإيجار (₪)</label>
            <InputNumber v-model="form.rent_amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>رسوم الكهرباء (₪)</label>
            <InputNumber v-model="form.electricity_amount" class="w-full" :min="0" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>رسوم المياه (₪)</label>
            <InputNumber v-model="form.water_amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>رسوم الإنترنت (₪)</label>
            <InputNumber v-model="form.internet_amount" class="w-full" :min="0" />
          </div>
        </div>

        <div class="form-field">
          <label>خدمات وصيانة إضافية (₪)</label>
          <InputNumber v-model="form.services_amount" class="w-full" :min="0" />
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ الفاتورة' }}</span>
          </button>
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
const dt = ref(null)
const items = ref([])
const contracts = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const filters = reactive({ status: null, from: null, to: null })

const form = reactive({
  contract_id: null, issue_date: null, due_date: null, rent_amount: 0,
  electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0
})

const invStatusLabels = { paid: 'مدفوعة بالكامل', unpaid: 'غير مدفوعة', partial: 'مدفوعة جزئياً', overdue: 'متأخرة عن الاستحقاق' }
const statusFilter = ref([
  { label: 'مدفوعة بالكامل', value: 'paid' },
  { label: 'غير مدفوعة', value: 'unpaid' },
  { label: 'متأخرة عن الاستحقاق', value: 'overdue' },
  { label: 'مدفوعة جزئياً', value: 'partial' }
])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.invoice_number?.toString().toLowerCase().includes(q) ||
    item.contract?.tenant?.first_name?.toLowerCase().includes(q) ||
    item.contract?.tenant?.last_name?.toLowerCase().includes(q)
  )
})

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchContracts(); fetchItems() })

async function fetchContracts() {
  try {
    const { data } = await api.get('/contracts?status=active')
    contracts.value = data.data.map(c => ({
      ...c,
      label: `عقد #${c.contract_number} - ${c.tenant?.first_name || ''} ${c.tenant?.last_name || ''}`
    }))
  } catch (err) {
    console.error(err)
  }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    const { data } = await api.get('/invoices', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل الفواتير: ' + (err.response?.data?.message || err.message)
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
  Object.assign(form, {
    contract_id: null, issue_date: null, due_date: null, rent_amount: 0,
    electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0
  })
}

async function saveItem() {
  if (!form.contract_id) {
    errorMsg.value = 'يرجى اختيار العقد المرتبط'
    return
  }

  saving.value = true
  try {
    await api.post('/invoices', form)
    showToast('تمت إضافة الفاتورة بنجاح')
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر إضافة الفاتورة'
  } finally {
    saving.value = false
  }
}

async function printInvoice(inv) {
  try {
    const url = api.defaults.baseURL + `/invoices/${inv.id}/pdf`
    window.open(url, '_blank')
  } catch (err) {
    console.error(err)
  }
}

async function payInvoice(inv) {
  try {
    await api.patch(`/invoices/${inv.id}/pay`)
    showToast('تم تسديد الفاتورة بنجاح')
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر تسديد الفاتورة'
  }
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
  width: 240px !important;
}

.filter-select {
  width: 170px !important;
}
.filter-datepicker {
  width: 150px !important;
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

.invoice-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: #EFF6FF;
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

.date-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.amount-total {
  font-weight: 700;
  color: var(--text-primary);
}

.amount-paid {
  font-weight: 600;
  color: var(--success);
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

.text-success {
  color: var(--success) !important;
}
.text-success:hover {
  background: var(--success-bg) !important;
}

.required {
  color: var(--danger);
}
</style>
