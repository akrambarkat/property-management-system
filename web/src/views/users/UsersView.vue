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
          <InputText v-model="searchQuery" placeholder="بحث بالاسم أو البريد الإلكتروني..." class="search-input-field" />
        </div>
        <Select
          v-model="filters.role"
          :options="roleFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الأدوار"
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
          <i class="pi pi-user-plus"></i> إضافة مستخدم جديد
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
        <Column field="name" header="اسم المستخدم" sortable>
          <template #body="slotProps">
            <div class="user-cell">
              <div class="user-avatar-circle">
                <span>{{ slotProps.data.name?.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.name }}</span>
                <span class="sub-text">هاتف: {{ slotProps.data.phone || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="email" header="البريد الإلكتروني" sortable>
          <template #body="slotProps">
            <span class="email-text"><i class="pi pi-envelope text-muted"></i> {{ slotProps.data.email }}</span>
          </template>
        </Column>

        <Column field="role" header="صلاحية الدور" sortable>
          <template #body="slotProps">
            <Tag :value="roleLabels[slotProps.data.role]" :severity="roleSeverity[slotProps.data.role]" />
          </template>
        </Column>

        <Column header="حالة الحساب">
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="slotProps.data.is_active ? 'status-available' : 'status-expired'"
            >
              {{ slotProps.data.is_active ? 'نشط' : 'معطل' }}
            </span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 120px; text-align: center;">
          <template #body="slotProps">
            <div class="action-buttons-group">
              <button class="btn-icon" @click="editItem(slotProps.data)" title="تعديل">
                <i class="pi pi-pencil"></i>
              </button>
              <button
                class="btn-icon"
                @click="toggleStatus(slotProps.data)"
                :title="slotProps.data.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب'"
              >
                <i :class="slotProps.data.is_active ? 'pi pi-ban text-danger' : 'pi pi-check text-success'"></i>
              </button>
            </div>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-users"></i>
            <p>لا يوجد مستخدمين مسجلين يطابقون البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات المستخدم' : 'إضافة مستخدم جديد'"
      modal
      :style="{ width: '560px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-row">
          <div class="form-field flex-1">
            <label>الاسم الكامل <span class="required">*</span></label>
            <InputText v-model="form.name" placeholder="أدخل اسم المستخدم" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>البريد الإلكتروني <span class="required">*</span></label>
            <InputText v-model="form.email" type="email" placeholder="example@domain.com" class="w-full" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>كلمة المرور {{ isEditing ? '(اختياري)' : '*' }}</label>
            <InputText v-model="form.password" type="password" class="w-full" :placeholder="isEditing ? 'اتركه فارغاً للحفاظ على القديمة' : 'أدخل كلمة المرور'" />
          </div>
          <div class="form-field flex-1">
            <label>رقم الهاتف</label>
            <InputText v-model="form.phone" placeholder="059xxxxxxx" class="w-full" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>صلاحية الدور</label>
            <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>حالة الحساب</label>
            <SelectButton v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" />
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ المستخدم' }}</span>
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
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const filters = reactive({ role: null })

const form = reactive({ id: null, name: '', email: '', password: '', phone: '', role: 'employee', is_active: true })

const roleLabels = { super_admin: 'مدير النظام', employee: 'موظف', guard: 'حارس' }
const roleSeverity = { super_admin: 'danger', employee: 'info', guard: 'warn' }
const roleOptions = ref(Object.entries(roleLabels).map(([value, label]) => ({ value, label })))
const roleFilterOptions = ref(Object.entries(roleLabels).map(([value, label]) => ({ value, label })))
const statusOptions = ref([{ label: 'نشط', value: true }, { label: 'معطل', value: false }])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.name?.toLowerCase().includes(q) ||
    item.email?.toLowerCase().includes(q) ||
    item.phone?.toLowerCase().includes(q)
  )
})

onMounted(() => fetchItems())

async function fetchItems() {
  loading.value = true
  try {
    errorMsg.value = ''
    const { data } = await api.get('/users')
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل قائمة المستخدمين: ' + (err.response?.data?.message || err.message)
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
  form.password = ''
  isEditing.value = true
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  isEditing.value = false
  Object.assign(form, { id: null, name: '', email: '', password: '', phone: '', role: 'employee', is_active: true })
}

async function saveItem() {
  if (!form.name || !form.email) {
    errorMsg.value = 'يرجى تعبئة الاسم والبريد الإلكتروني'
    return
  }

  saving.value = true
  try {
    const payload = { ...form }
    if (!payload.password) delete payload.password
    if (isEditing.value) {
      await api.put(`/users/${form.id}`, payload)
      showToast('تم تعديل بيانات المستخدم بنجاح')
    } else {
      await api.post('/users', payload)
      showToast('تم إضافة المستخدم بنجاح')
    }
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر حفظ المستخدم'
  } finally {
    saving.value = false
  }
}

async function toggleStatus(user) {
  try {
    await api.patch(`/users/${user.id}/toggle-status`)
    showToast(`تم ${user.is_active ? 'تعطيل' : 'تفعيل'} الحساب بنجاح`)
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'خطأ أثناء تغيير حالة الحساب'
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

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.user-avatar-circle {
  width: 36px;
  height: 36px;
  background: #EFF6FF;
  color: var(--accent);
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

.email-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
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

.text-danger {
  color: var(--danger) !important;
}
.text-success {
  color: var(--success) !important;
}
</style>
