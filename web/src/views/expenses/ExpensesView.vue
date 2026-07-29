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
          <InputText v-model="searchQuery" placeholder="بحث باسم المبنى أو الوصف..." class="search-input-field" />
        </div>
        <Select
          v-model="filters.category"
          :options="categoryFilter"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع التصنيفات"
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
          <i class="pi pi-plus"></i> إضافة مصروف جديد
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
        <Column field="building.name" header="المبنى / العقار" sortable>
          <template #body="slotProps">
            <div class="expense-cell">
              <div class="icon-avatar">
                <i class="pi pi-building text-red"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.building?.name || 'مصروف عام' }}</span>
                <span class="sub-text">تاريخ: {{ slotProps.data.expense_date?.split('T')[0] || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="category" header="التصنيف" sortable>
          <template #body="slotProps">
            <span class="category-pill">
              {{ categoryLabels[slotProps.data.category] || slotProps.data.category }}
            </span>
          </template>
        </Column>

        <Column field="amount" header="المبلغ" sortable>
          <template #body="slotProps">
            <span class="expense-amount">{{ formatCurrency(slotProps.data.amount) }}</span>
          </template>
        </Column>

        <Column field="description" header="بيان المصروف">
          <template #body="slotProps">
            <span class="desc-text">{{ slotProps.data.description || '—' }}</span>
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
            <i class="pi pi-money-bill"></i>
            <p>لا توجد مصروفات مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات المصروف' : 'إضافة مصروف جديد'"
      modal
      :style="{ width: '550px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>المبنى <span class="required">*</span></label>
            <Select v-model="form.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="اختر المبنى" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>التصنيف</label>
            <Select v-model="form.category" :options="categoryOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>المبلغ (₪) <span class="required">*</span></label>
            <InputNumber v-model="form.amount" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>التاريخ</label>
            <DatePicker v-model="form.expense_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
        </div>

        <div class="form-field">
          <label>تفاصيل وبيان المصروف</label>
          <Textarea v-model="form.description" class="w-full" rows="3" placeholder="أدخل تفاصيل وملاحظات المصروف" />
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ المصروف' }}</span>
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog v-model:visible="showDeleteModal" header="تأكيد عملية الحذف" modal :style="{ width: '400px' }">
      <div class="dialog-body text-center">
        <i class="pi pi-exclamation-triangle warning-icon"></i>
        <p class="delete-msg">هل أنت متأكد من حذف المصروف بقيمة <strong>{{ formatCurrency(itemToDelete?.amount) }}</strong>؟</p>
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

const filters = reactive({ category: null })

const form = reactive({ id: null, building_id: null, category: 'general', amount: null, expense_date: null, description: '' })

const categoryLabels = { maintenance: 'صيانة', plumbing: 'سباكة', electrical: 'كهرباء', cleaning: 'نظافة', security: 'أمن', general: 'عام' }
const categoryFilter = ref(Object.entries(categoryLabels).map(([value, label]) => ({ value, label })))
const categoryOptions = ref(Object.entries(categoryLabels).map(([value, label]) => ({ value, label })))

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.building?.name?.toLowerCase().includes(q) ||
    item.description?.toLowerCase().includes(q)
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
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = filters.category ? { category: filters.category } : {}
    const { data } = await api.get('/expenses', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل المصروفات: ' + (err.response?.data?.message || err.message)
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
  Object.assign(form, { ...item, expense_date: item.expense_date?.split('T')[0] })
  isEditing.value = true
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  isEditing.value = false
  Object.assign(form, { id: null, building_id: null, category: 'general', amount: null, expense_date: null, description: '' })
}

async function saveItem() {
  if (!form.building_id || !form.amount) {
    errorMsg.value = 'يرجى تحديد المبنى والمبلغ'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/expenses/${form.id}`, form)
      showToast('تم تعديل المصروف بنجاح')
    } else {
      await api.post('/expenses', form)
      showToast('تمت إضافة المصروف بنجاح')
    }
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر حفظ المصروف'
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
    await api.delete(`/expenses/${itemToDelete.value.id}`)
    showToast('تم حذف المصروف بنجاح')
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

.expense-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: #FEF2F2;
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

.category-pill {
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}

.expense-amount {
  font-weight: 700;
  color: var(--danger);
}

.desc-text {
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
