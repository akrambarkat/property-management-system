<template>
  <div class="page-view">
    <!-- Feedback Error Banner -->
    <div v-if="errorMsg" class="error-banner">
      <i class="pi pi-exclamation-circle"></i>
      <span>{{ errorMsg }}</span>
      <span class="close-banner" @click="errorMsg = ''">×</span>
    </div>

    <!-- Feedback Toast Banner -->
    <transition name="fade">
      <div v-if="toastMsg" class="toast-banner">
        <i class="pi pi-check-circle"></i>
        <span>{{ toastMsg }}</span>
      </div>
    </transition>

    <!-- Page Toolbar with SaaS Filter & Search -->
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <div class="search-input-wrapper">
          <i class="pi pi-search search-icon"></i>
          <InputText v-model="searchQuery" placeholder="البحث في المباني..." class="search-input-field" />
        </div>
        <Select
          v-model="filters.location_id"
          :options="locations"
          optionLabel="name"
          optionValue="id"
          placeholder="تصفية حسب الموقع"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </div>
      <button class="btn-primary" @click="openCreateDialog">
        <i class="pi pi-plus"></i> إضافة مبنى جديد
      </button>
    </div>

    <!-- Enterprise SaaS Card Table Layout -->
    <div class="table-container-card">
      <DataTable
        :value="filteredItems"
        stripedRows
        paginator
        :rows="12"
        :loading="loading"
        responsiveLayout="scroll"
        class="custom-saas-table"
      >
        <Column field="name" header="اسم المبنى" sortable>
          <template #body="slotProps">
            <div class="building-name-cell">
              <i class="pi pi-building text-amber"></i>
              <span class="font-bold">{{ slotProps.data.name }}</span>
            </div>
          </template>
        </Column>

        <Column field="location.name" header="الموقع العقاري" sortable>
          <template #body="slotProps">
            <span class="location-tag">
              <i class="pi pi-map-marker"></i>
              {{ slotProps.data.location?.name || 'غير محدد' }}
            </span>
          </template>
        </Column>

        <Column field="floors" header="عدد الطوابق" sortable>
          <template #body="slotProps">
            <span>{{ slotProps.data.floors }} طوابق</span>
          </template>
        </Column>

        <Column header="عدد الوحدات">
          <template #body="slotProps">
            <span class="units-count-badge">
              {{ slotProps.data.units_count || 0 }} وحدة
            </span>
          </template>
        </Column>

        <Column header="الحالة">
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="slotProps.data.is_active ? 'status-available' : 'status-expired'"
            >
              {{ slotProps.data.is_active ? 'نشط' : 'غير نشط' }}
            </span>
          </template>
        </Column>

        <!-- Actions Dropdown SaaS Style -->
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
            <i class="pi pi-building"></i>
            <p>لا توجد مباني مسجلة تطابق خيارات البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Form Dialog with Clean SaaS Sections -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات المبنى' : 'إضافة مبنى جديد'"
      modal
      :style="{ width: '520px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-field">
          <label>الموقع العقاري <span class="required">*</span></label>
          <Select
            v-model="form.location_id"
            :options="locations"
            optionLabel="name"
            optionValue="id"
            placeholder="اختر الموقع العقاري"
            class="w-full"
          />
        </div>

        <div class="form-field">
          <label>اسم المبنى / البرج <span class="required">*</span></label>
          <InputText v-model="form.name" placeholder="مثال: برج السلام التنموي" class="w-full" />
        </div>

        <div class="form-field">
          <label>عدد الطوابق</label>
          <InputNumber v-model="form.floors" class="w-full" :min="1" :max="100" />
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
        <p class="delete-msg">هل أنت متأكد من حذف المبنى <strong>{{ itemToDelete?.name }}</strong>؟</p>
        <span class="delete-sub">لا يمكن التراجع عن هذا الإجراء بعد الحذف.</span>
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

const items = ref([])
const locations = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ location_id: null })

const form = reactive({
  id: null, location_id: null, name: '', floors: 1, is_active: true
})

const statusOptions = ref([
  { label: 'نشط', value: true },
  { label: 'غير نشط', value: false }
])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.name?.toLowerCase().includes(q) ||
    item.location?.name?.toLowerCase().includes(q)
  )
})

onMounted(() => { fetchLocations(); fetchItems() })

async function fetchLocations() {
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل المواقع: ' + (err.response?.data?.message || err.message)
  }
}

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = filters.location_id ? { location_id: filters.location_id } : {}
    const { data } = await api.get('/buildings', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل المباني: ' + (err.response?.data?.message || err.message)
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
  form.id = null; form.location_id = null; form.name = ''; form.floors = 1; form.is_active = true
}

async function saveItem() {
  if (!form.name || !form.location_id) {
    errorMsg.value = 'يرجى تعبئة الحقول المطلوبة'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/buildings/${form.id}`, form)
      showToast('تم تعديل بيانات المبنى بنجاح')
    } else {
      await api.post('/buildings', form)
      showToast('تم إضافة المبنى بنجاح')
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
    await api.delete(`/buildings/${itemToDelete.value.id}`)
    showToast('تم حذف المبنى بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'خطأ أثناء عملية الحذف'
  }
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
  width: 200px !important;
}

.table-container-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.building-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}
.font-bold {
  font-weight: 600;
  color: var(--text-primary);
}

.location-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  font-size: 13px;
}

.units-count-badge {
  background: #F1F5F9;
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
