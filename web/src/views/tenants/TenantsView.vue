<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="البحث باسم المستأجر، رقم الهوية، أو الهاتف..."
      emptyTitle="لا يوجد مستأجرين مسجلين"
      emptySubtitle="لم يتم العثور على أي مستأجرين يطابقون خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
      @row-click="onRowClick"
    >
      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-user-plus"></i> إضافة مستأجر جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('first_name')" field="first_name" header="اسم المستأجر" sortable>
          <template #body="slotProps">
            <div class="tenant-name-cell">
              <div class="user-avatar-circle">
                <span>{{ slotProps.data.first_name?.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.first_name }} {{ slotProps.data.last_name }}</span>
                <span class="sub-text">هوية: {{ slotProps.data.id_number || '—' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('phone')" field="phone" header="رقم الهاتف" sortable>
          <template #body="slotProps">
            <span class="phone-text" v-if="slotProps.data.phone">
              <i class="pi pi-phone text-muted"></i>
              {{ slotProps.data.phone }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('email')" field="email" header="البريد الإلكتروني" sortable>
          <template #body="slotProps">
            <span class="email-text" v-if="slotProps.data.email">
              <i class="pi pi-envelope text-muted"></i>
              {{ slotProps.data.email }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('current_unit')" field="current_unit" header="الوحدة المأجورة">
          <template #body="slotProps">
            <span v-if="slotProps.data.current_unit" class="unit-pill">
              <i class="pi pi-home text-blue"></i>
              وحدة #{{ slotProps.data.current_unit?.unit_number }}
            </span>
            <span v-else class="text-muted font-italic">لا يوجد عقد نشط</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('is_active')" field="is_active" header="الحالة" sortable>
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="slotProps.data.current_unit ? 'status-occupied' : 'status-available'"
            >
              {{ slotProps.data.current_unit ? 'مستأجر حالي' : 'مستأجر سابق' }}
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

    <!-- Side Drawer Inspection -->
    <EntityDrawer
      v-model="showDrawer"
      entityType="tenant"
      :entityId="selectedTenantId"
    />

    <!-- Form Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات المستأجر' : 'إضافة مستأجر جديد'"
      modal
      :style="{ width: '600px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Personal Info -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-user"></i>
            <span>البيانات الشخصية وهوية المستأجر</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="الاسم الأول"
              required
              forId="tenant-firstname"
              :errorMessage="errors.first_name"
            >
              <InputText
                id="tenant-firstname"
                v-model="form.first_name"
                placeholder="مثال: خالد"
                class="w-full"
                @input="clearFieldError('first_name')"
              />
            </FormField>

            <FormField
              label="اسم العائلة"
              required
              forId="tenant-lastname"
              :errorMessage="errors.last_name"
            >
              <InputText
                id="tenant-lastname"
                v-model="form.last_name"
                placeholder="مثال: العلي"
                class="w-full"
                @input="clearFieldError('last_name')"
              />
            </FormField>
          </div>

          <FormField
            label="رقم الهوية الوطنية / رقم الجواز"
            required
            forId="tenant-idnum"
            :errorMessage="errors.id_number"
            helpText="يجب إدخال رقم هويّة رسمية صحيح للتوثيق في العقود"
          >
            <InputText
              id="tenant-idnum"
              v-model="form.id_number"
              placeholder="أدخل رقم الهوية الرسمية (9 أرقام)"
              class="w-full"
              @input="clearFieldError('id_number')"
            />
          </FormField>
        </div>

        <!-- Section: ID Photo Upload -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-id-card"></i>
            <span>صورة الهوية</span>
          </div>

          <div class="photo-upload-wrapper">
            <div class="photo-preview" v-if="idPhotoPreview" @click="$refs.idPhotoInput.click()">
              <img :src="idPhotoPreview" alt="صورة الهوية" />
              <div class="photo-overlay"><i class="pi pi-camera"></i> تغيير</div>
            </div>
            <div class="photo-placeholder" v-else @click="$refs.idPhotoInput.click()">
              <i class="pi pi-id-card"></i>
              <span>إضافة صورة الهوية (اختياري)</span>
              <span class="photo-hint">jpg, png - حد أقصى 2MB</span>
            </div>
            <input
              ref="idPhotoInput"
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/gif"
              @change="onIdPhotoChange"
              style="display: none"
            />
            <button v-if="idPhotoFile" class="btn-xs-text mt-2" @click="removeIdPhoto">
              <i class="pi pi-trash"></i> إزالة الصورة
            </button>
          </div>
        </div>

        <!-- Section 2: Contact Info -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-phone"></i>
            <span>معلومات التواصل والعنوان</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="رقم الهاتف"
              required
              forId="tenant-phone"
              :errorMessage="errors.phone"
              helpText="مثال: 059xxxxxxx"
            >
              <InputText
                id="tenant-phone"
                v-model="form.phone"
                placeholder="059xxxxxxx"
                class="w-full"
                @input="clearFieldError('phone')"
              />
            </FormField>

            <FormField
              label="البريد الإلكتروني"
              forId="tenant-email"
              :errorMessage="errors.email"
              helpText="لإرسال إشعارات الفواتير تلقائياً"
            >
              <InputText
                id="tenant-email"
                v-model="form.email"
                type="email"
                placeholder="example@domain.com"
                class="w-full"
                @input="clearFieldError('email')"
              />
            </FormField>
          </div>

          <FormField
            label="العنوان الدائم / ملاحظات إضافية"
            forId="tenant-address"
          >
            <Textarea
              id="tenant-address"
              v-model="form.address"
              placeholder="أدخل أي عنوان سكن دائم، جهة عمل، أو ملاحظات مرجعية"
              class="w-full"
              rows="2"
            />
          </FormField>
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
    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="تأكيد الحذف"
      :message="`هل أنت متأكد من حذف المستأجر <strong>${ itemToDelete?.first_name } ${ itemToDelete?.last_name }</strong>؟`"
      variant="danger"
      confirmText="تأكيد الحذف"
      @confirm="deleteItemConfirmed"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import EntityDrawer from '@/components/common/EntityDrawer.vue'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const loading = ref(false)
const toast = useToastStore()
const router = useRouter()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const showDrawer = ref(false)
const selectedTenantId = ref(null)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const idPhotoFile = ref(null)
const idPhotoPreview = ref(null)

const tableColumns = [
  { field: 'first_name', header: 'اسم المستأجر' },
  { field: 'phone', header: 'رقم الهاتف' },
  { field: 'email', header: 'البريد الإلكتروني' },
  { field: 'current_unit', header: 'الوحدة المأجورة' },
  { field: 'is_active', header: 'الحالة' }
]

const form = reactive({
  id: null, first_name: '', last_name: '', id_number: '', phone: '', email: '', address: ''
})

const errors = reactive({
  first_name: '', last_name: '', id_number: '', phone: '', email: ''
})

const initialFormState = JSON.stringify(form)

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.first_name || !form.first_name.trim()) {
    errors.first_name = 'يرجى إدخال الاسم الأول'
    isValid = false
  }

  if (!form.last_name || !form.last_name.trim()) {
    errors.last_name = 'يرجى إدخال اسم العائلة'
    isValid = false
  }

  if (!form.id_number || !form.id_number.trim()) {
    errors.id_number = 'يرجى إدخال رقم الهوية الوطنية أو الجواز'
    isValid = false
  }

  if (!form.phone || !form.phone.trim()) {
    errors.phone = 'يرجى إدخال رقم الهاتف للتواصل'
    isValid = false
  }

  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'صيغة البريد الإلكتروني غير صحيحة'
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
      label: 'معاينة الملف الشخصي',
      icon: 'pi pi-eye',
      command: () => router.push(`/tenants/${row.id}`)
    },
    {
      label: 'تعديل البيانات',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'حذف المستأجر',
      icon: 'pi pi-trash',
      danger: true,
      command: () => confirmDelete(row)
    }
  ]
}

onMounted(() => fetchItems())

function onRowClick(event) {
  if (event.data?.id) {
    router.push(`/tenants/${event.data.id}`)
  }
}

async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get('/tenants')
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل قائمة المستأجرين: ' + (err.response?.data?.message || err.message))
  } finally {
    loading.value = false
  }
}


function openCreateDialog() {
  resetForm()
  showDialog.value = true
}

function onIdPhotoChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  idPhotoFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => { idPhotoPreview.value = e.target.result }
  reader.readAsDataURL(file)
}

function removeIdPhoto() {
  idPhotoFile.value = null
  idPhotoPreview.value = null
  if (form.id_photo_url) form.id_photo_url = null
}

function editItem(item) {
  resetForm()
  Object.assign(form, item)
  if (item.id_photo_url) idPhotoPreview.value = item.id_photo_url
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  Object.assign(form, { id: null, first_name: '', last_name: '', id_number: '', phone: '', email: '', address: '' })
  Object.keys(errors).forEach(key => errors[key] = '')
  idPhotoFile.value = null
  idPhotoPreview.value = null
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

function buildFormData() {
  const fd = new FormData()
  fd.append('first_name', form.first_name)
  fd.append('last_name', form.last_name)
  fd.append('id_number', form.id_number)
  fd.append('phone', form.phone || '')
  fd.append('email', form.email || '')
  fd.append('address', form.address || '')
  if (idPhotoFile.value) fd.append('id_photo', idPhotoFile.value)
  return fd
}

async function saveItem() {
  if (!validateForm()) return

  saving.value = true
  try {
    if (isEditing.value) {
      const fd = buildFormData()
      fd.append('_method', 'PUT')
      const { data } = await api.post(`/tenants/${form.id}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل بيانات المستأجر بنجاح')
    } else {
      const fd = buildFormData()
      const { data } = await api.post('/tenants', fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      items.value.unshift(data.data)
      toast.success('تم إضافة المستأجر بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ البيانات')
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
    toast.success('تم حذف المستأجر بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'خطأ أثناء عملية الحذف')
  }
}
</script>

<style scoped>
.tenant-name-cell {
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

.phone-text, .email-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.unit-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}

.font-italic {
  font-style: italic;
  font-size: 12.5px;
}

.text-blue {
  color: #2563EB;
}

.photo-upload-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.photo-placeholder {
  width: 180px;
  height: 180px;
  border: 2px dashed var(--border, #E2E8F0);
  border-radius: var(--radius-md, 10px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--text-muted, #94A3B8);
  background: var(--bg-subtle, #F8FAFC);
}
.photo-placeholder:hover {
  border-color: var(--accent, #2563EB);
  color: var(--accent, #2563EB);
  background: #EFF6FF;
}
.photo-placeholder i {
  font-size: 2.5rem;
}
.photo-placeholder span {
  font-size: 13px;
  font-weight: 600;
}
.photo-hint {
  font-size: 11px !important;
  font-weight: 400 !important;
  color: var(--text-muted, #94A3B8);
}
.photo-preview {
  width: 180px;
  height: 180px;
  border-radius: var(--radius-md, 10px);
  overflow: hidden;
  position: relative;
  cursor: pointer;
  border: 2px solid var(--border, #E2E8F0);
}
.photo-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.photo-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.5);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 13px;
  opacity: 0;
  transition: opacity 0.2s ease;
}
.photo-preview:hover .photo-overlay {
  opacity: 1;
}
.btn-xs-text {
  background: none;
  border: none;
  color: var(--danger, #EF4444);
  font-size: 12px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.btn-xs-text:hover {
  text-decoration: underline;
}
.mt-2 {
  margin-top: 8px;
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
