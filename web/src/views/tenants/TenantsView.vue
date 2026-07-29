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

    <!-- Page Toolbar with SaaS Filter & Search -->
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <div class="search-input-wrapper">
          <i class="pi pi-search search-icon"></i>
          <InputText
            v-model="filters.search"
            placeholder="بحث بالاسم أو رقم الهوية أو الهاتف..."
            class="search-input-field"
            @input="fetchItems"
          />
        </div>
      </div>

      <div class="toolbar-actions">
        <button class="btn-secondary" @click="exportCSV" title="تصدير البيانات">
          <i class="pi pi-download"></i> تصدير
        </button>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-user-plus"></i> إضافة مستأجر جديد
        </button>
      </div>
    </div>

    <!-- Enterprise SaaS Card Table Layout -->
    <div class="table-container-card">
      <DataTable
        ref="dt"
        :value="items"
        stripedRows
        paginator
        :rows="12"
        :loading="loading"
        responsiveLayout="scroll"
        class="custom-saas-table"
      >
        <Column field="first_name" header="اسم المستأجر" sortable>
          <template #body="slotProps">
            <div class="tenant-cell">
              <div class="user-avatar-circle">
                <span>{{ slotProps.data.first_name?.charAt(0) }}</span>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.first_name }} {{ slotProps.data.last_name }}</span>
                <span class="sub-text">هوية: {{ slotProps.data.id_number || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="phone" header="رقم الهاتف">
          <template #body="slotProps">
            <span class="phone-link" v-if="slotProps.data.phone">
              <i class="pi pi-phone text-blue"></i>
              {{ slotProps.data.phone }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="email" header="البريد الإلكتروني">
          <template #body="slotProps">
            <span v-if="slotProps.data.email" class="email-text">{{ slotProps.data.email }}</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column header="الوحدة المأجورة">
          <template #body="slotProps">
            <span v-if="slotProps.data.current_unit" class="unit-badge">
              <i class="pi pi-home"></i>
              وحدة #{{ slotProps.data.current_unit.unit_number }}
            </span>
            <span v-else class="text-muted">لا يوجد عقد نشط</span>
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

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-users"></i>
            <p>لا يوجد مستأجرين مسجلين يطابقون البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Form Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات المستأجر' : 'إضافة مستأجر جديد'"
      modal
      :style="{ width: '580px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الاسم الأول <span class="required">*</span></label>
            <InputText v-model="form.first_name" placeholder="مثال: خالد" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>اسم العائلة <span class="required">*</span></label>
            <InputText v-model="form.last_name" placeholder="مثال: العلي" class="w-full" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>رقم الهوية / الجواز <span class="required">*</span></label>
            <InputText v-model="form.id_number" placeholder="أدخل رقم الهوية الرسمية" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>رقم الهاتف</label>
            <InputText v-model="form.phone" placeholder="059xxxxxxx" class="w-full" />
          </div>
        </div>

        <div class="form-field">
          <label>البريد الإلكتروني</label>
          <InputText v-model="form.email" type="email" placeholder="example@domain.com" class="w-full" />
        </div>

        <div class="form-field">
          <label>العنوان الدائم / الملاحظات</label>
          <Textarea v-model="form.address" placeholder="أدخل أي عنوان سكن دائم أو ملاحظات إضافية" class="w-full" rows="2" />
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
        <p class="delete-msg">هل أنت متأكد من حذف المستأجر <strong>{{ itemToDelete?.first_name }} {{ itemToDelete?.last_name }}</strong>؟</p>
        <span class="delete-sub">لا يمكن التراجع عن هذه العملية إذا كان له عقود مسجلة.</span>
        <div class="form-actions center-actions">
          <button class="btn-secondary" @click="showDeleteModal = false">إلغاء</button>
          <button class="btn-primary btn-danger-action" @click="deleteItemConfirmed">تأكيد الحذف</button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'

const dt = ref(null)
const items = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ search: '' })

const form = reactive({
  id: null, first_name: '', last_name: '', id_number: '', phone: '', email: '', address: ''
})

onMounted(() => fetchItems())

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const params = filters.search ? { search: filters.search } : {}
    const { data } = await api.get('/tenants', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل قائمة المستأجرين: ' + (err.response?.data?.message || err.message)
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
  form.id = null; form.first_name = ''; form.last_name = ''; form.id_number = ''
  form.phone = ''; form.email = ''; form.address = ''
}

async function saveItem() {
  if (!form.first_name || !form.last_name || !form.id_number) {
    errorMsg.value = 'يرجى تعبئة الحقول الأساسية (الاسم ورقم الهوية)'
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/tenants/${form.id}`, form)
      showToast('تم تعديل بيانات المستأجر بنجاح')
    } else {
      await api.post('/tenants', form)
      showToast('تم إضافة المستأجر بنجاح')
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
    await api.delete(`/tenants/${itemToDelete.value.id}`)
    showToast('تم حذف المستأجر بنجاح')
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
  width: 300px !important;
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

.tenant-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.user-avatar-circle {
  width: 36px;
  height: 36px;
  background: #EFF6FF;
  color: #2563EB;
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

.phone-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  direction: ltr;
}

.email-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.unit-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ECFDF5;
  color: #047857;
  border: 1px solid #A7F3D0;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
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
