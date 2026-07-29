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
          <InputText v-model="searchQuery" placeholder="بحث بالوصف أو رقم الوحدة..." class="search-input-field" />
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
          v-model="filters.priority"
          :options="priorityFilter"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الأولويات"
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
          <i class="pi pi-plus"></i> إضافة طلب صيانة
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
        <Column field="unit.unit_number" header="الوحدة العقارية" sortable>
          <template #body="slotProps">
            <div class="maint-cell">
              <div class="icon-avatar">
                <i class="pi pi-wrench text-purple"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">وحدة #{{ slotProps.data.unit?.unit_number || '—' }}</span>
                <span class="sub-text">{{ slotProps.data.unit?.building?.name || 'مبنى غير محدد' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="description" header="تفاصيل العطل / الطلب">
          <template #body="slotProps">
            <span class="desc-text">{{ slotProps.data.description }}</span>
          </template>
        </Column>

        <Column field="priority" header="مستوى الأولوية" sortable>
          <template #body="slotProps">
            <Tag :value="priorityLabels[slotProps.data.priority]" :severity="prioritySeverity[slotProps.data.priority]" />
          </template>
        </Column>

        <Column field="status" header="الحالة التشغيلية">
          <template #body="slotProps">
            <span :class="'status-badge status-' + slotProps.data.status">
              {{ maintStatusLabels[slotProps.data.status] || slotProps.data.status }}
            </span>
          </template>
        </Column>

        <Column field="created_at" header="تاريخ الطلب" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.created_at?.split('T')[0] || '—' }}</span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 100px; text-align: center;">
          <template #body="slotProps">
            <div class="action-buttons-group">
              <button class="btn-icon" @click="editItem(slotProps.data)" title="تعديل">
                <i class="pi pi-pencil"></i>
              </button>
            </div>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-wrench"></i>
            <p>لا توجد طلبات صيانة مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل طلب الصيانة' : 'إضافة طلب صيانة جديد'"
      modal
      :style="{ width: '550px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-field">
          <label>الوحدة العقارية <span class="required">*</span></label>
          <Select v-model="form.unit_id" :options="units" optionLabel="label" optionValue="id" placeholder="اختر الوحدة المستهدفة" class="w-full" />
        </div>

        <div class="form-field">
          <label>وصف المشكلة / العطل <span class="required">*</span></label>
          <Textarea v-model="form.description" class="w-full" rows="3" placeholder="أدخل تفاصيل العطل أو أعمال الصيانة المطلوبة..." />
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>درجة الأولوية</label>
            <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>حالة الطلب</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ الطلب' }}</span>
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
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const filters = reactive({ status: null, priority: null })

const form = reactive({ id: null, unit_id: null, description: '', priority: 'medium', status: 'pending' })

const priorityLabels = { low: 'منخفضة', medium: 'متوسطة', high: 'عالية', urgent: 'عاجلة' }
const prioritySeverity = { low: 'info', medium: 'warn', high: 'danger', urgent: 'danger' }
const maintStatusLabels = { pending: 'قيد الانتظار', in_progress: 'قيد التنفيذ', completed: 'مكتملة', cancelled: 'ملغية' }

const statusFilter = ref(Object.entries(maintStatusLabels).map(([value, label]) => ({ value, label })))
const statusOptions = ref(Object.entries(maintStatusLabels).map(([value, label]) => ({ value, label })))
const priorityFilter = ref(Object.entries(priorityLabels).map(([value, label]) => ({ value, label })))
const priorityOptions = ref(Object.entries(priorityLabels).map(([value, label]) => ({ value, label })))

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.description?.toLowerCase().includes(q) ||
    item.unit?.unit_number?.toString().toLowerCase().includes(q) ||
    item.unit?.building?.name?.toLowerCase().includes(q)
  )
})

onMounted(() => { fetchUnits(); fetchItems() })

async function fetchUnits() {
  try {
    const { data } = await api.get('/units')
    units.value = data.data.map(u => ({ ...u, label: `وحدة #${u.unit_number} - ${u.building?.name || ''}` }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.priority) params.priority = filters.priority
    const { data } = await api.get('/maintenance', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل طلبات الصيانة: ' + (err.response?.data?.message || err.message)
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
  Object.assign(form, { id: null, unit_id: null, description: '', priority: 'medium', status: 'pending' })
}

async function saveItem() {
  if (!form.unit_id || !form.description) {
    errorMsg.value = 'يرجى اختيار الوحدة وإدخال الوصف'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/maintenance/${form.id}`, form)
      showToast('تم تعديل طلب الصيانة بنجاح')
    } else {
      await api.post('/maintenance', form)
      showToast('تم إضافة طلب الصيانة بنجاح')
    }
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر حفظ البيانات'
  } finally {
    saving.value = false
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

.maint-cell {
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

.desc-text {
  font-size: 13px;
  color: var(--text-primary);
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
