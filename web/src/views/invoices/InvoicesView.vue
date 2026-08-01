<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="بحث برقم الفاتورة، المستأجر، أو الوحدة..."
      emptyTitle="لا توجد فواتير مسجلة"
      emptySubtitle="لم يتم العثور على أي فواتير تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.status"
          :options="statusFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع حالات الفواتير"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إنشاء فاتورة جديدة
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('id')" field="id" header="رقم الفاتورة" sortable>
          <template #body="slotProps">
            <span class="invoice-code">INV-{{ String(slotProps.data.id).padStart(4, '0') }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('contract.tenant.first_name')" field="contract.tenant.first_name" header="المستأجر والوحدة" sortable>
          <template #body="slotProps">
            <div class="tenant-cell">
              <div class="user-avatar-circle">
                <span>{{ slotProps.data.contract?.tenant?.first_name?.charAt(0).toUpperCase() || 'M' }}</span>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.contract?.tenant?.first_name }} {{ slotProps.data.contract?.tenant?.last_name }}</span>
                <span class="sub-text">وحدة #{{ slotProps.data.contract?.unit?.unit_number || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('issue_date')" field="issue_date" header="التواريخ" sortable>
          <template #body="slotProps">
            <div class="date-cell">
              <span>إصدار: {{ slotProps.data.issue_date || '—' }}</span>
              <span class="sub-text">استحقاق: {{ slotProps.data.due_date || '—' }}</span>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('total_amount')" field="total_amount" header="المبلغ الإجمالي" sortable>
          <template #body="slotProps">
            <span class="total-amount">{{ formatCurrency(slotProps.data.total_amount) }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('status')" field="status" header="حالة السداد" sortable>
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="getStatusClass(slotProps.data.status)"
            >
              {{ statusLabels[slotProps.data.status] || slotProps.data.status }}
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

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل الفاتورة' : 'إنشاء فاتورة جديدة'"
      modal
      :style="{ width: '640px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Contract & Dates -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-file"></i>
            <span>العقد وتواريخ الفاتورة</span>
          </div>

          <FormField
            label="العقد"
            required
            forId="inv-contract"
            :errorMessage="errors.contract_id"
            helpText="اختر العقد مباشرة، أو استخدم الفلاتر أدناه لتضييق النتائج"
          >
            <Select
              id="inv-contract"
              v-model="form.contract_id"
              :options="filteredContracts"
              optionLabel="label"
              optionValue="id"
              placeholder="اختر العقد"
              class="w-full"
              filter
              @change="onContractChange"
            />
          </FormField>

          <!-- Selected Contract Details -->
          <div v-if="selectedContractInfo" class="contract-details-card">
            <div class="cd-row">
              <span class="cd-label">الموقع</span>
              <span class="cd-value">{{ selectedContractInfo.location }}</span>
            </div>
            <div class="cd-row">
              <span class="cd-label">العمارة</span>
              <span class="cd-value">{{ selectedContractInfo.building }}</span>
            </div>
            <div class="cd-row">
              <span class="cd-label">الوحدة</span>
              <span class="cd-value">{{ selectedContractInfo.unit }}</span>
            </div>
            <div class="cd-row">
              <span class="cd-label">المستأجر</span>
              <span class="cd-value">{{ selectedContractInfo.tenant }}</span>
            </div>
          </div>

          <!-- Optional Location / Building Filters -->
          <div class="form-grid-3 mt-3">
            <FormField
              label="تصفية بالموقع"
              forId="inv-location"
            >
              <Select
                id="inv-location"
                v-model="selectedLocation"
                :options="locations"
                optionLabel="name"
                optionValue="id"
                placeholder="اختر الموقع للتصفية"
                class="w-full"
                filter
                @change="onLocationChange"
              />
            </FormField>

            <FormField
              label="تصفية بالعمارة"
              forId="inv-building"
            >
              <Select
                id="inv-building"
                v-model="selectedBuilding"
                :options="buildings"
                optionLabel="name"
                optionValue="id"
                placeholder="اختر العمارة للتصفية"
                class="w-full"
                :disabled="!selectedLocation"
                filter
                @change="onBuildingChange"
              />
            </FormField>
          </div>

          <div class="form-grid-2">
            <FormField
              label="تاريخ الإصدار"
              forId="inv-issue-date"
            >
              <DatePicker
                id="inv-issue-date"
                v-model="form.issue_date"
                class="w-full"
                placeholder="اختر التاريخ"
              />
            </FormField>

            <FormField
              label="تاريخ الاستحقاق"
              forId="inv-due-date"
            >
              <DatePicker
                id="inv-due-date"
                v-model="form.due_date"
                class="w-full"
                placeholder="اختر التاريخ"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: Items Breakdown & Amounts -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-calculator"></i>
            <span>تفاصيل وتفكيك البنود المستحقة</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="قيمة الإيجار (₪)"
              forId="inv-rent"
            >
              <InputNumber
                id="inv-rent"
                v-model="form.rent_amount"
                class="w-full"
                :min="0"
              />
            </FormField>

            <FormField
              label="رسوم الكهرباء (₪)"
              forId="inv-elec"
            >
              <InputNumber
                id="inv-elec"
                v-model="form.electricity_amount"
                class="w-full"
                :min="0"
              />
            </FormField>

            <FormField
              label="رسوم المياه (₪)"
              forId="inv-water"
            >
              <InputNumber
                id="inv-water"
                v-model="form.water_amount"
                class="w-full"
                :min="0"
              />
            </FormField>

            <FormField
              label="رسوم الإنترنت (₪)"
              forId="inv-net"
            >
              <InputNumber
                id="inv-net"
                v-model="form.internet_amount"
                class="w-full"
                :min="0"
              />
            </FormField>
          </div>

          <FormField
            label="خدمات وصيانة إضافية (₪)"
            forId="inv-services"
          >
            <InputNumber
              id="inv-services"
              v-model="form.services_amount"
              class="w-full"
              :min="0"
            />
          </FormField>

          <!-- Computed Total Preview -->
          <div class="total-preview-box">
            <span>إجمالي الفاتورة المحسوب:</span>
            <strong class="calculated-sum">{{ formatCurrency(calculatedTotal) }}</strong>
          </div>
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
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const contracts = ref([])
const locations = ref([])
const buildings = ref([])
const selectedLocation = ref(null)
const selectedBuilding = ref(null)
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const filters = reactive({ status: null })

const form = reactive({
  id: null, contract_id: null, issue_date: null, due_date: null,
  rent_amount: 0, electricity_amount: 0, water_amount: 0,
  internet_amount: 0, services_amount: 0
})

const errors = reactive({
  contract_id: ''
})

const initialFormState = JSON.stringify(form)

const calculatedTotal = computed(() => {
  return (Number(form.rent_amount) || 0) +
         (Number(form.electricity_amount) || 0) +
         (Number(form.water_amount) || 0) +
         (Number(form.internet_amount) || 0) +
         (Number(form.services_amount) || 0)
})

const tableColumns = [
  { field: 'id', header: 'رقم الفاتورة' },
  { field: 'contract.tenant.first_name', header: 'المستأجر والوحدة' },
  { field: 'issue_date', header: 'التواريخ' },
  { field: 'total_amount', header: 'المبلغ الإجمالي' },
  { field: 'status', header: 'حالة السداد' }
]

const statusLabels = { unpaid: 'غير مدفوعة', partial: 'مدفوعة جزئياً', paid: 'مدفوعة بالكامل', overdue: 'متأخرة' }

const statusFilterOptions = ref([
  { label: 'غير مدفوعة', value: 'unpaid' },
  { label: 'مدفوعة جزئياً', value: 'partial' },
  { label: 'مدفوعة بالكامل', value: 'paid' },
  { label: 'متأخرة', value: 'overdue' }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.contract_id) {
    errors.contract_id = 'يرجى اختيار عقد الإيجار المرتبط'
    isValid = false
  }

  return isValid
}

function isFormDirty() {
  return JSON.stringify(form) !== initialFormState
}

function getStatusClass(status) {
  switch (status) {
    case 'paid': return 'status-paid'
    case 'unpaid': return 'status-expired'
    case 'partial': return 'status-pending'
    case 'overdue': return 'status-danger'
    default: return 'status-info'
  }
}

function getRowActions(row) {
  return [
    {
      label: 'تعديل الفاتورة',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'طباعة الفاتورة',
      icon: 'pi pi-print',
      command: () => printInvoice(row)
    }
  ]
}

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

const selectedContractInfo = computed(() => {
  if (!form.contract_id) return null
  const c = contracts.value.find(c => c.id === form.contract_id)
  if (!c) return null
  const loc = locations.value.find(l => l.id === c.unit?.building?.location_id)
  return {
    location: loc?.name || c.unit?.building?.location?.name || '—',
    building: c.unit?.building?.name || '—',
    unit: `#${c.unit?.unit_number || '—'}`,
    tenant: c.tenant ? `${c.tenant.first_name} ${c.tenant.last_name}` : '—'
  }
})

const filteredContracts = computed(() => {
  if (!selectedBuilding.value) return contracts.value
  return contracts.value.filter(c => c.unit?.building_id === selectedBuilding.value)
})

onMounted(() => { fetchLocations(); fetchContracts(); fetchItems() })

async function fetchLocations() {
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data || []
  } catch (err) { console.error(err) }
}

async function onLocationChange() {
  selectedBuilding.value = null
  buildings.value = []
  if (!selectedLocation.value) return
  try {
    const { data } = await api.get('/buildings', { params: { location_id: selectedLocation.value } })
    buildings.value = data.data || []
  } catch (err) { console.error(err) }
}

async function onBuildingChange() {
  if (form.contract_id) {
    const c = contracts.value.find(c => c.id === form.contract_id)
    if (c && c.unit?.building_id !== selectedBuilding.value) {
      form.contract_id = null
    }
  }
}

async function fetchContracts() {
  try {
    const { data } = await api.get('/contracts', { params: { status: 'active' } })
    contracts.value = data.data.map(c => ({
      ...c,
      label: `عقد CNT-${String(c.id).padStart(4, '0')} - ${c.tenant?.first_name || ''} ${c.tenant?.last_name || ''} (وحدة #${c.unit?.unit_number || ''})`
    }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    const params = filters.status ? { status: filters.status } : {}
    const { data } = await api.get('/invoices', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل الفواتير: ' + (err.response?.data?.message || err.message))
    items.value = []
  } finally {
    loading.value = false
  }
}

function onContractChange() {
  clearFieldError('contract_id')
  const contract = contracts.value.find(c => c.id === form.contract_id)
  if (contract && !isEditing.value) {
    form.rent_amount = contract.rent_amount || 0
  }
}


function openCreateDialog() {
  resetForm()
  showDialog.value = true
}

function editItem(item) {
  resetForm()
  Object.assign(form, item)
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  selectedLocation.value = null
  selectedBuilding.value = null
  buildings.value = []
  Object.assign(form, {
    id: null, contract_id: null, issue_date: null, due_date: null,
    rent_amount: 0, electricity_amount: 0, water_amount: 0,
    internet_amount: 0, services_amount: 0
  })
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
    if (isEditing.value) {
      const { data } = await api.put(`/invoices/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل الفاتورة بنجاح')
    } else {
      const { data } = await api.post('/invoices', form)
      items.value.unshift(data.data)
      toast.success('تم إنشاء الفاتورة بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ الفاتورة')
  } finally {
    saving.value = false
  }
}

function printInvoice(invoice) {
  window.open(`${api.defaults.baseURL}/invoices/${invoice.id}/pdf`, '_blank')
}
</script>

<style scoped>
.invoice-code {
  font-family: monospace;
  font-weight: 700;
  color: var(--info-contrast);
  background: var(--info-bg);
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
  background: var(--info-bg);
  color: var(--info-contrast);
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

.date-cell {
  display: flex;
  flex-direction: column;
  font-size: 13px;
}

.total-amount {
  font-weight: 700;
  color: var(--text-primary);
}

.total-preview-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--accent-light);
  border: 1px solid var(--info-border);
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  margin-top: 4px;
  font-size: 13.5px;
  color: var(--text-primary);
}
.calculated-sum {
  font-size: 16px;
  font-weight: 800;
  color: var(--accent-hover);
}

.filter-select {
  width: 180px !important;
}

.contract-details-card {
  background: var(--bg-subtle);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  margin-bottom: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px 24px;
}
.cd-row {
  display: flex;
  gap: 6px;
  font-size: 13px;
}
.cd-label {
  color: var(--text-secondary);
  font-weight: 500;
}
.cd-value {
  color: var(--text-primary);
  font-weight: 700;
}
.mt-3 {
  margin-top: 12px;
}
</style>
