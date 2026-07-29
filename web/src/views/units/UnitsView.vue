<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
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

    <!-- Page Toolbar with SaaS Filter & Actions -->
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <div class="search-input-wrapper">
          <i class="pi pi-search search-icon"></i>
          <InputText v-model="searchQuery" placeholder="البحث برقم الوحدة، المبنى..." class="search-input-field" />
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
        <Select
          v-model="filters.building_id"
          :options="buildings"
          optionLabel="name"
          optionValue="id"
          placeholder="جميع المباني"
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
          <i class="pi pi-plus"></i> إضافة وحدة جديدة
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
        <Column field="unit_number" header="رقم / اسم الوحدة" sortable>
          <template #body="slotProps">
            <div class="unit-cell">
              <div class="icon-avatar">
                <i class="pi pi-th-large text-purple"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.unit_number }}</span>
                <span class="sub-text">طابق: {{ slotProps.data.floor || 0 }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="building.name" header="المبنى" sortable>
          <template #body="slotProps">
            <span class="building-name-tag">
              <i class="pi pi-building"></i>
              {{ slotProps.data.building?.name || 'غير محدد' }}
            </span>
          </template>
        </Column>

        <Column field="unit_type" header="نوع الوحدة" sortable>
          <template #body="slotProps">
            <span class="type-pill">
              {{ typeLabels[slotProps.data.unit_type] || slotProps.data.unit_type }}
            </span>
          </template>
        </Column>

        <Column field="area" header="المساحة (م²)" sortable>
          <template #body="slotProps">
            <span>{{ slotProps.data.area ? slotProps.data.area + ' م²' : '—' }}</span>
          </template>
        </Column>

        <Column field="rent_amount" header="قيمة الإيجار" sortable>
          <template #body="slotProps">
            <span class="rent-amount">{{ formatCurrency(slotProps.data.rent_amount) }}</span>
          </template>
        </Column>

        <Column header="الحالة التشغيلية">
          <template #body="slotProps">
            <span :class="'status-badge status-' + slotProps.data.status">
              {{ statusLabels[slotProps.data.status] || slotProps.data.status }}
            </span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 100px; text-align: center;">
          <template #body="slotProps">
            <div class="action-buttons-group">
              <button class="btn-icon" @click="editItem(slotProps.data)" title="تعديل">
                <i class="pi pi-pencil"></i>
              </button>
              <button class="btn-icon btn-danger" @click="confirmDelete(slotProps.data)" title="حذف">
                <i class="pi pi-trash"></i>
              </button>
            </div>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-th-large"></i>
            <p>لا توجد وحدات عقارية مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات الوحدة' : 'إضافة وحدة عقارية جديدة'"
      modal
      :style="{ width: '560px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>المبنى / البرج <span class="required">*</span></label>
            <Select v-model="form.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="اختر المبنى" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>رقم / رمز الوحدة <span class="required">*</span></label>
            <InputText v-model="form.unit_number" placeholder="مثال: شقة 104 أو محل 2" class="w-full" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>نوع الوحدة</label>
            <Select v-model="form.unit_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>الطابق</label>
            <InputNumber v-model="form.floor" class="w-full" :min="0" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>المساحة (م²)</label>
            <InputNumber v-model="form.area" class="w-full" :min="1" />
          </div>
          <div class="form-field flex-1">
            <label>قيمة الإيجار الشهري (₪)</label>
            <InputNumber v-model="form.rent_amount" class="w-full" :min="0" />
          </div>
        </div>

        <div class="form-field">
          <label>حالة الوحدة</label>
          <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
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

    <!-- Delete Confirmation Modal -->
    <Dialog v-model:visible="showDeleteModal" header="تأكيد عملية الحذف" modal :style="{ width: '400px' }">
      <div class="dialog-body text-center">
        <i class="pi pi-exclamation-triangle warning-icon"></i>
        <p class="delete-msg">هل أنت متأكد من حذف الوحدة <strong>{{ itemToDelete?.unit_number }}</strong>؟</p>
        <span class="delete-sub">لا يمكن التراجع عن هذه العملية بعد الحذف.</span>
        <div class="form-actions center-actions">
          <button class="btn-secondary" @click="showDeleteModal = false">إلغاء</button>
          <button class="btn-primary btn-danger-action" @click="deleteItemConfirmed">تأكيد الحذف</button>
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
const buildings = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ status: null, building_id: null })

const form = reactive({
  id: null, building_id: null, unit_number: '', unit_type: 'apartment',
  floor: 0, area: null, rent_amount: null, status: 'available'
})

const typeLabels = { apartment: 'شقة سكنية', shop: 'محل تجاري', warehouse: 'مخزن' }
const statusLabels = { available: 'متاحة (شاغرة)', occupied: 'مشغولة (مؤجرة)', maintenance: 'تحت الصيانة' }

const typeOptions = ref([
  { label: 'شقة سكنية', value: 'apartment' },
  { label: 'محل تجاري', value: 'shop' },
  { label: 'مخزن', value: 'warehouse' }
])

const statusFilter = ref([
  { label: 'متاحة (شاغرة)', value: 'available' },
  { label: 'مشغولة (مؤجرة)', value: 'occupied' },
  { label: 'تحت الصيانة', value: 'maintenance' }
])

const statusOptions = ref([
  { label: 'متاحة (شاغرة)', value: 'available' },
  { label: 'مشغولة (مؤجرة)', value: 'occupied' },
  { label: 'تحت الصيانة', value: 'maintenance' }
])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.unit_number?.toLowerCase().includes(q) ||
    item.building?.name?.toLowerCase().includes(q)
  )
})

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchBuildings(); fetchItems() })

async function fetchBuildings() {
  try {
    const { data } = await api.get('/buildings')
    buildings.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل المباني: ' + (err.response?.data?.message || err.message)
  }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.building_id) params.building_id = filters.building_id
    const { data } = await api.get('/units', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل الوحدات: ' + (err.response?.data?.message || err.message)
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
  form.id = null; form.building_id = null; form.unit_number = ''
  form.unit_type = 'apartment'; form.floor = 0; form.area = null
  form.rent_amount = null; form.status = 'available'
}

async function saveItem() {
  if (!form.unit_number || !form.building_id) {
    errorMsg.value = 'يرجى تعبئة الحقول المطلوبة'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/units/${form.id}`, form)
      showToast('تم تعديل بيانات الوحدة بنجاح')
    } else {
      await api.post('/units', form)
      showToast('تم إضافة الوحدة بنجاح')
    }
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر حفظ البيانات'
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  itemToDelete.value = item
  showDeleteModal.value = true
}

async function deleteItemConfirmed() {
  if (!itemToDelete.value) return
  try {
    await api.delete(`/units/${itemToDelete.value.id}`)
    showToast('تم حذف الوحدة بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'خطأ أثناء الحذف'
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
  width: 220px !important;
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

.unit-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: #F3E8FF;
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

.building-name-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  font-size: 13px;
}

.type-pill {
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}

.rent-amount {
  font-weight: 700;
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

.required {
  color: var(--danger);
}

.text-center {
  text-align: center;
}
.warning-icon {
  font-size: 2.5rem;
  color: var(--warning);
  margin-bottom: 12px;
}
.delete-msg {
  font-size: 14.5px;
  color: var(--text-primary);
}
.delete-sub {
  font-size: 12.5px;
  color: var(--text-secondary);
  display: block;
  margin-top: 4px;
}
.center-actions {
  justify-content: center !important;
  margin-top: 16px !important;
}
.btn-danger-action {
  background: var(--danger) !important;
}
.btn-danger-action:hover {
  background: #DC2626 !important;
}
</style>
