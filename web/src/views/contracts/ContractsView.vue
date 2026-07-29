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
          <InputText v-model="searchQuery" placeholder="بحث برقم العقد أو اسم المستأجر..." class="search-input-field" />
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
      </div>

      <div class="toolbar-actions">
        <button class="btn-secondary" @click="exportCSV" title="تصدير البيانات">
          <i class="pi pi-download"></i> تصدير
        </button>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة عقد جديد
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
        <Column field="contract_number" header="رقم العقد" sortable>
          <template #body="slotProps">
            <div class="contract-cell">
              <div class="icon-avatar">
                <i class="pi pi-file text-amber"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">#{{ slotProps.data.contract_number }}</span>
                <span class="sub-text">نوع العقد: {{ slotProps.data.contract_type === 'monthly' ? 'شهري' : 'سنوي' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="tenant.first_name" header="المستأجر" sortable>
          <template #body="slotProps">
            <span class="tenant-name" v-if="slotProps.data.tenant">
              <i class="pi pi-user text-muted"></i>
              {{ slotProps.data.tenant.first_name }} {{ slotProps.data.tenant.last_name }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="unit.unit_number" header="الوحدة العقارية" sortable>
          <template #body="slotProps">
            <span class="unit-tag" v-if="slotProps.data.unit">
              <i class="pi pi-building"></i>
              وحدة {{ slotProps.data.unit.unit_number }} ({{ slotProps.data.unit.building?.name || '—' }})
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="rent_amount" header="قيمة الإيجار" sortable>
          <template #body="slotProps">
            <span class="rent-amount">{{ formatCurrency(slotProps.data.rent_amount) }}</span>
          </template>
        </Column>

        <Column field="start_date" header="تاريخ البداية" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.start_date || '—' }}</span>
          </template>
        </Column>

        <Column field="end_date" header="تاريخ النهاية" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.end_date || '—' }}</span>
          </template>
        </Column>

        <Column header="الحالة">
          <template #body="slotProps">
            <span :class="'status-badge status-' + slotProps.data.status">
              {{ statusLabels[slotProps.data.status] || slotProps.data.status }}
            </span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 120px; text-align: center;">
          <template #body="slotProps">
            <div class="action-buttons-group">
              <button class="btn-icon" @click="editItem(slotProps.data)" title="تعديل">
                <i class="pi pi-pencil"></i>
              </button>
              <button v-if="slotProps.data.status === 'active'" class="btn-icon btn-danger" @click="terminateContract(slotProps.data)" title="إنهاء العقد">
                <i class="pi pi-times-circle"></i>
              </button>
            </div>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-file"></i>
            <p>لا توجد عقود مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات العقد' : 'إضافة عقد إيجار جديد'"
      modal
      :style="{ width: '640px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الوحدة العقارية <span class="required">*</span></label>
            <Select v-model="form.unit_id" :options="units" optionLabel="label" optionValue="id" placeholder="اختر الوحدة" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>المستأجر <span class="required">*</span></label>
            <Select v-model="form.tenant_id" :options="tenants" optionLabel="label" optionValue="id" placeholder="اختر المستأجر" class="w-full" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>تاريخ بداية العقد</label>
            <DatePicker v-model="form.start_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
          <div class="form-field flex-1">
            <label>تاريخ نهاية العقد</label>
            <DatePicker v-model="form.end_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>قيمة الإيجار (₪)</label>
            <InputNumber v-model="form.rent_amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>نوع السداد</label>
            <Select v-model="form.contract_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ البيانات' }}</span>
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
const units = ref([])
const tenants = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const filters = reactive({ status: null })

const form = reactive({ id: null, unit_id: null, tenant_id: null, start_date: null, end_date: null, rent_amount: null, contract_type: 'monthly' })

const statusLabels = { active: 'نشط', expired: 'منتهي', terminated: 'ملغي', renewed: 'مجدد' }
const statusFilter = ref([
  { label: 'نشط', value: 'active' },
  { label: 'منتهي', value: 'expired' },
  { label: 'ملغي', value: 'terminated' }
])
const typeOptions = ref([{ label: 'شهري', value: 'monthly' }, { label: 'سنوي', value: 'yearly' }])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.contract_number?.toString().toLowerCase().includes(q) ||
    item.tenant?.first_name?.toLowerCase().includes(q) ||
    item.tenant?.last_name?.toLowerCase().includes(q) ||
    item.unit?.unit_number?.toString().toLowerCase().includes(q)
  )
})

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchUnits(); fetchTenants(); fetchItems() })

async function fetchUnits() {
  try {
    const { data } = await api.get('/units')
    units.value = data.data.map(u => ({ ...u, label: `وحدة #${u.unit_number} - ${u.building?.name || ''}` }))
  } catch (err) { console.error(err) }
}

async function fetchTenants() {
  try {
    const { data } = await api.get('/tenants')
    tenants.value = data.data.map(t => ({ ...t, label: `${t.first_name} ${t.last_name}` }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = filters.status ? { status: filters.status } : {}
    const { data } = await api.get('/contracts', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل العقود: ' + (err.response?.data?.message || err.message)
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

function editItem(item) {
  Object.assign(form, item)
  isEditing.value = true
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  isEditing.value = false
  form.id = null; form.unit_id = null; form.tenant_id = null
  form.start_date = null; form.end_date = null; form.rent_amount = null; form.contract_type = 'monthly'
}

async function saveItem() {
  if (!form.unit_id || !form.tenant_id) {
    errorMsg.value = 'يرجى اختيار الوحدة والمستأجر'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/contracts/${form.id}`, form)
      showToast('تم تعديل العقد بنجاح')
    } else {
      await api.post('/contracts', form)
      showToast('تم إبرام العقد بنجاح')
    }
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر حفظ العقد'
  } finally {
    saving.value = false
  }
}

async function terminateContract(item) {
  if (!confirm('هل أنت متأكد من إلغاء/إنهاء هذا العقد؟')) return
  try {
    await api.patch(`/contracts/${item.id}/terminate`)
    showToast('تم إنهاء العقد بنجاح')
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'خطأ أثناء عملية الإلغاء'
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
  width: 250px !important;
}

.filter-select {
  width: 170px !important;
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

.contract-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: #FEF3C7;
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

.unit-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.rent-amount {
  font-weight: 700;
  color: var(--success);
}

.date-text {
  font-size: 13px;
  color: var(--text-secondary);
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
