<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="بحث باسم المستخدم، البريد، أو الدور..."
      emptyTitle="لا يوجد مستخدمين مسجلين"
      emptySubtitle="لم يتم العثور على أي مستخدمين يطابقون خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.role"
          :options="roleOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الأدوار"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-user-plus"></i> إضافة مستخدم جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('name')" field="name" header="اسم المستخدم" sortable>
          <template #body="slotProps">
            <div class="user-cell">
              <div class="user-avatar-circle">
                <span>{{ slotProps.data.name?.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.name }}</span>
                <span class="sub-text">{{ slotProps.data.email }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('phone')" field="phone" header="رقم الهاتف">
          <template #body="slotProps">
            <span class="phone-text" v-if="slotProps.data.phone">
              <i class="pi pi-phone text-muted"></i>
              {{ slotProps.data.phone }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('role')" field="role" header="الدور / الصلاحية" sortable>
          <template #body="slotProps">
            <span class="role-badge" :class="'role-' + slotProps.data.role">
              {{ roleLabels[slotProps.data.role] || slotProps.data.role }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('is_active')" field="is_active" header="الحالة" sortable>
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="slotProps.data.is_active ? 'status-active' : 'status-expired'"
            >
              {{ slotProps.data.is_active ? 'نشط' : 'مُعطل' }}
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
      :header="isEditing ? 'تعديل بيانات المستخدم' : 'إضافة مستخدم جديد'"
      modal
      :style="{ width: '580px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Account Info -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-user"></i>
            <span>البيانات الأساسية</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="الاسم الكامل"
              required
              forId="user-name"
              :errorMessage="errors.name"
            >
              <InputText
                id="user-name"
                v-model="form.name"
                placeholder="أدخل اسم المستخدم"
                class="w-full"
                @input="clearFieldError('name')"
              />
            </FormField>

            <FormField
              label="البريد الإلكتروني"
              required
              forId="user-email"
              :errorMessage="errors.email"
            >
              <InputText
                id="user-email"
                v-model="form.email"
                type="email"
                placeholder="example@domain.com"
                class="w-full"
                @input="clearFieldError('email')"
              />
            </FormField>
          </div>

          <div class="form-grid-2">
            <FormField
              :label="isEditing ? 'كلمة المرور (اختياري)' : 'كلمة المرور'"
              :required="!isEditing"
              forId="user-pass"
              :errorMessage="errors.password"
              :helpText="isEditing ? 'اتركه فارغاً للحفاظ على كلمة المرور الحالية' : '8 أحرف على الأقل'"
            >
              <InputText
                id="user-pass"
                v-model="form.password"
                type="password"
                class="w-full"
                :placeholder="isEditing ? '••••••••' : 'أدخل كلمة المرور'"
                @input="clearFieldError('password')"
              />
            </FormField>

            <FormField
              label="رقم الهاتف"
              forId="user-phone"
            >
              <InputText
                id="user-phone"
                v-model="form.phone"
                placeholder="059xxxxxxx"
                class="w-full"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: Roles & Status -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-shield"></i>
            <span>الصلاحيات وحالة الحساب</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="صلاحية الدور"
              forId="user-role"
            >
              <Select
                id="user-role"
                v-model="form.role"
                :options="roleOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                filter
              />
            </FormField>

            <FormField
              label="حالة الحساب"
              forId="user-status"
            >
              <SelectButton
                id="user-status"
                v-model="form.is_active"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
              />
            </FormField>
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

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="تأكيد الحذف"
      :message="`هل أنت متأكد من حذف المستخدم <strong>${ itemToDelete?.name }</strong>؟`"
      variant="danger"
      confirmText="تأكيد الحذف"
      @confirm="deleteItemConfirmed"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ role: null })

const form = reactive({
  id: null, name: '', email: '', password: '', phone: '', role: 'employee', is_active: true
})

const errors = reactive({
  name: '', email: '', password: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'name', header: 'اسم المستخدم' },
  { field: 'phone', header: 'رقم الهاتف' },
  { field: 'role', header: 'الدور / الصلاحية' },
  { field: 'is_active', header: 'الحالة' }
]

const roleLabels = { super_admin: 'مدير النظام', employee: 'موظف إدارة', guard: 'حارس مبنى' }
const roleOptions = ref([
  { label: 'مدير النظام', value: 'super_admin' },
  { label: 'موظف إدارة', value: 'employee' },
  { label: 'حارس مبنى', value: 'guard' }
])

const statusOptions = ref([
  { label: 'نشط', value: true },
  { label: 'مُعطل', value: false }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.name || !form.name.trim()) {
    errors.name = 'يرجى إدخال اسم المستخدم الكامل'
    isValid = false
  }

  if (!form.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'يرجى إدخال بريد إلكتروني صحيح'
    isValid = false
  }

  if (!isEditing.value && (!form.password || form.password.length < 6)) {
    errors.password = 'كلمة المرور يجب ألا تقل عن 6 أحرف'
    isValid = false
  }

  return isValid
}

function isFormDirty() {
  return JSON.stringify(form) !== initialFormState
}

function getRowActions(row) {
  return [
    {
      label: 'تعديل البيانات',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'حذف المستخدم',
      icon: 'pi pi-trash',
      danger: true,
      command: () => confirmDelete(row)
    }
  ]
}

onMounted(() => fetchItems())

async function fetchItems() {
  loading.value = true
  try {
    const params = filters.role ? { role: filters.role } : {}
    const { data } = await api.get('/users', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل قائمة المستخدمين: ' + (err.response?.data?.message || err.message))
    items.value = []
  } finally {
    loading.value = false
  }
}


function openCreateDialog() {
  resetForm()
  showDialog.value = true
}

function editItem(item) {
  resetForm()
  Object.assign(form, { ...item, password: '' })
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  Object.assign(form, { id: null, name: '', email: '', password: '', phone: '', role: 'employee', is_active: true })
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
      const payload = { ...form }
      if (!payload.password) delete payload.password
      const { data } = await api.put(`/users/${form.id}`, payload)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل بيانات المستخدم بنجاح')
    } else {
      const { data } = await api.post('/users', form)
      items.value.unshift(data.data)
      toast.success('تم إضافة المستخدم بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ بيانات المستخدم')
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
    await api.delete(`/users/${itemToDelete.value.id}`)
    toast.success('تم حذف المستخدم بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'خطأ أثناء عملية الحذف')
  }
}
</script>

<style scoped>
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

.phone-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.role-badge {
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}
.role-super_admin { background: #EEF2FF; color: #4F46E5; }
.role-employee { background: #F1F5F9; color: #475569; }
.role-guard { background: #FEF3C7; color: #D97706; }

.filter-select {
  width: 170px !important;
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
