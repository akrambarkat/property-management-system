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
          <InputText v-model="searchQuery" placeholder="البحث باسم الموقع أو العنوان..." class="search-input-field" />
        </div>
        <Select
          v-model="filters.is_active"
          :options="statusFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="تصفية حسب الحالة"
          showClear
          class="filter-select"
        />
      </div>
      
      <div class="toolbar-actions">
        <button class="btn-secondary" @click="exportCSV" title="تصدير البيانات">
          <i class="pi pi-download"></i> تصدير
        </button>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة موقع جديد
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
        <Column field="name" header="اسم الموقع العقاري" sortable>
          <template #body="slotProps">
            <div class="location-name-cell">
              <div class="icon-avatar">
                <i class="pi pi-map-marker text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.name }}</span>
                <span class="sub-text">{{ slotProps.data.address || 'عنوان غير مسجل' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="buildings_count" header="عدد المباني" sortable>
          <template #body="slotProps">
            <span class="count-badge">
              <i class="pi pi-building text-amber"></i>
              {{ slotProps.data.buildings_count || 0 }} مباني
            </span>
          </template>
        </Column>

        <Column field="is_active" header="الحالة التشغيلية" sortable>
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="slotProps.data.is_active ? 'status-available' : 'status-expired'"
            >
              {{ slotProps.data.is_active ? 'نشط' : 'غير نشط' }}
            </span>
          </template>
        </Column>

        <!-- Actions -->
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

        <!-- Empty & Loading States -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-map-marker"></i>
            <p>لا توجد مواقع عقارية مسجلة تطابق خيارات البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Form Dialog with Clean SaaS Sections -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات الموقع العقاري' : 'إضافة موقع عقاري جديد'"
      modal
      :style="{ width: '500px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-field">
          <label>اسم الموقع العقاري <span class="required">*</span></label>
          <InputText v-model="form.name" placeholder="مثال: مجمع الصفوة التجاري" class="w-full" />
        </div>

        <div class="form-field">
          <label>العنوان والتفاصيل</label>
          <Textarea v-model="form.address" placeholder="أدخل العنوان الحي، الشارع، أو الحي السكني" class="w-full" rows="3" />
        </div>

        <div class="form-field">
          <label>حالة التشغيل</label>
          <SelectButton
            v-model="form.is_active"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
          />
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
    <Dialog
      v-model:visible="showDeleteModal"
      header="تأكيد عملية الحذف"
      modal
      :style="{ width: '400px' }"
    >
      <div class="dialog-body text-center">
        <i class="pi pi-exclamation-triangle warning-icon"></i>
        <p class="delete-msg">هل أنت متأكد من حذف الموقع <strong>{{ itemToDelete?.name }}</strong>؟</p>
        <span class="delete-sub">قد يؤدي حذف هذا الموقع إلى التأثير على المباني والوحدات المرتبطة به.</span>
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
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ is_active: null })

const form = reactive({
  id: null, name: '', address: '', is_active: true
})

const statusOptions = ref([
  { label: 'نشط', value: true },
  { label: 'غير نشط', value: false }
])

const statusFilterOptions = ref([
  { label: 'نشط', value: true },
  { label: 'غير نشط', value: false }
])

const filteredItems = computed(() => {
  return items.value.filter(item => {
    const matchesSearch = !searchQuery.value.trim() ||
      item.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.address?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesStatus = filters.is_active === null || item.is_active === filters.is_active
    return matchesSearch && matchesStatus
  })
})

onMounted(() => { fetchItems() })

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const { data } = await api.get('/locations')
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل المواقع: ' + (err.response?.data?.message || err.message)
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
  form.id = null; form.name = ''; form.address = ''; form.is_active = true
}

async function saveItem() {
  if (!form.name) {
    errorMsg.value = 'يرجى إدخال اسم الموقع العقاري'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      const { data } = await api.put(`/locations/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      showToast('تم تعديل الموقع العقاري بنجاح')
    } else {
      const { data } = await api.post('/locations', form)
      items.value.unshift(data.data)
      showToast('تم إضافة الموقع العقاري بنجاح')
    }
    closeDialog()
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
    await api.delete(`/locations/${itemToDelete.value.id}`)
    showToast('تم حذف الموقع بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'خطأ أثناء عملية الحذف'
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
  width: 260px !important;
}

.filter-select {
  width: 180px !important;
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

.location-name-cell {
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

.count-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #F8FAFC;
  border: 1px solid var(--border);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-primary);
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
