<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="البحث باسم الموقع أو العنوان..."
      emptyTitle="لا توجد مواقع عقارية مسجلة"
      emptySubtitle="لم يتم العثور على أي مواقع تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة موقع جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('name')" field="name" header="اسم الموقع العقاري" sortable>
          <template #body="slotProps">
            <div class="location-name-cell">
              <div class="icon-avatar">
                <i class="pi pi-map-marker text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.name }}</span>
                <span class="sub-text">كود: LOC-{{ slotProps.data.id }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('address')" field="address" header="العنوان">
          <template #body="slotProps">
            <span class="address-text">{{ slotProps.data.address || '—' }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('buildings_count')" field="buildings_count" header="عدد المباني" sortable>
          <template #body="slotProps">
            <span class="buildings-count-pill">
              {{ slotProps.data.buildings_count || 0 }} مباني
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('is_active')" field="is_active" header="الحالة" sortable>
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="slotProps.data.is_active ? 'status-active' : 'status-expired'"
            >
              {{ slotProps.data.is_active ? 'نشط' : 'غير نشط' }}
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

    <!-- Form Dialog with Clean SaaS Sections -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل بيانات الموقع العقاري' : 'إضافة موقع عقاري جديد'"
      modal
      :style="{ width: '520px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Identity & Address -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-map-marker"></i>
            <span>الاسم والعنوان</span>
          </div>

          <FormField
            label="اسم الموقع العقاري"
            required
            forId="loc-name"
            :errorMessage="errors.name"
            helpText="مثال: مجمع الصفوة التجاري أو حي النخيل"
          >
            <InputText
              id="loc-name"
              v-model="form.name"
              placeholder="مثال: مجمع الصفوة التجاري"
              class="w-full"
              @input="clearFieldError('name')"
            />
          </FormField>

          <FormField
            label="العنوان والتفاصيل"
            forId="loc-address"
            helpText="أدخل العنوان التفصيلي أو المرفقات التوضيحية للموقع"
          >
            <Textarea
              id="loc-address"
              v-model="form.address"
              placeholder="أدخل العنوان الحي، الشارع، أو الحي السكني"
              class="w-full"
              rows="3"
            />
          </FormField>
        </div>

        <!-- Section 2: Status -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-cog"></i>
            <span>الحالة التشغيلية</span>
          </div>

          <FormField
            label="حالة التشغيل"
            forId="loc-status"
            helpText="المواقع غير النشطة تكون مخفية عند اختيار المواقع للمباني الجديدة"
          >
            <SelectButton
              id="loc-status"
              v-model="form.is_active"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
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
      :message="`هل أنت متأكد من حذف الموقع <strong>${ itemToDelete?.name }</strong>؟`"
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

const form = reactive({
  id: null, name: '', address: '', is_active: true
})

const errors = reactive({
  name: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'name', header: 'اسم الموقع العقاري' },
  { field: 'address', header: 'العنوان' },
  { field: 'buildings_count', header: 'عدد المباني' },
  { field: 'is_active', header: 'الحالة' }
]

const statusOptions = ref([
  { label: 'نشط', value: true },
  { label: 'غير نشط', value: false }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.name || !form.name.trim()) {
    errors.name = 'يرجى إدخال اسم الموقع العقاري'
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
      label: 'تعديل',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'حذف الموقع',
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
    const { data } = await api.get('/locations')
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل قائمة المواقع: ' + (err.response?.data?.message || err.message))
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
  Object.assign(form, item)
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  form.id = null
  form.name = ''
  form.address = ''
  form.is_active = true
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
      const { data } = await api.put(`/locations/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل بيانات الموقع بنجاح')
    } else {
      const { data } = await api.post('/locations', form)
      items.value.unshift(data.data)
      toast.success('تم إضافة الموقع بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ بيانات الموقع')
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
    toast.success('تم حذف الموقع بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'خطأ أثناء عملية الحذف')
  }
}
</script>

<style scoped>
.location-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: var(--info-bg);
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

.address-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.buildings-count-pill {
  background: var(--bg-subtle);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}

.text-blue {
  color: var(--info-contrast);
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
  background: var(--danger-hover) !important;
}
</style>
