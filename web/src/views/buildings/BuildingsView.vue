<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="البحث باسم المبنى أو الموقع..."
      emptyTitle="لا توجد مباني مسجلة"
      emptySubtitle="لم يتم العثور على أي مباني تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.location_id"
          :options="locations"
          optionLabel="name"
          optionValue="id"
          placeholder="جميع المواقع"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة مبنى جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('name')" field="name" header="اسم المبنى" sortable>
          <template #body="slotProps">
            <div class="building-name-cell">
              <div class="icon-avatar">
                <i class="pi pi-building text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">{{ slotProps.data.name }}</span>
                <span class="sub-text">كود: BLD-{{ slotProps.data.id }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('location.name')" field="location.name" header="الموقع العقاري" sortable>
          <template #body="slotProps">
            <span class="location-tag" v-if="slotProps.data.location">
              <i class="pi pi-map-marker text-muted"></i>
              {{ slotProps.data.location.name }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('floors')" field="floors" header="عدد الطوابق" sortable>
          <template #body="slotProps">
            <span class="floors-text">{{ slotProps.data.floors }} طوابق</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('units_count')" field="units_count" header="عدد الوحدات" sortable>
          <template #body="slotProps">
            <span class="units-count-pill">
              {{ slotProps.data.units_count || 0 }} وحدة
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
      :header="isEditing ? 'تعديل بيانات المبنى' : 'إضافة مبنى جديد'"
      modal
      :style="{ width: '560px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Basic Info -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-building"></i>
            <span>معلومات المبنى الرئيسية</span>
          </div>

          <FormField
            label="اسم المبنى / البرج"
            required
            forId="bldg-name"
            :errorMessage="errors.name"
            helpText="أدخل اسماً مميزاً للمبنى يسهل التعرف عليه"
          >
            <InputText
              id="bldg-name"
              v-model="form.name"
              placeholder="مثال: برج السلام التنموي"
              class="w-full"
              @input="clearFieldError('name')"
            />
          </FormField>

          <FormField
            label="الموقع العقاري المرتبط"
            required
            forId="bldg-location"
            :errorMessage="errors.location_id"
            helpText="حدد المجمع أو المنطقة العقارية التي يتبع لها هذا المبنى"
          >
            <Select
              id="bldg-location"
              v-model="form.location_id"
              :options="locations"
              optionLabel="name"
              optionValue="id"
              placeholder="اختر الموقع العقاري"
              class="w-full"
              filter
              @change="clearFieldError('location_id')"
            />
          </FormField>
        </div>

        <!-- Section 2: Operational Specs -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-cog"></i>
            <span>التفاصيل التشغيلية</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="عدد الطوابق"
              forId="bldg-floors"
              :errorMessage="errors.floors"
              helpText="من 1 إلى 100 طابق"
            >
              <InputNumber
                id="bldg-floors"
                v-model="form.floors"
                class="w-full"
                :min="1"
                :max="100"
              />
            </FormField>

            <FormField
              label="حالة التشغيل"
              forId="bldg-status"
              helpText="المباني غير النشطة لا تظهر في العقود الجديدة"
            >
              <SelectButton
                id="bldg-status"
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
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ البيانات' }}</span>
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="تأكيد الحذف"
      :message="`هل أنت متأكد من حذف المبنى <strong>${ itemToDelete?.name }</strong>؟`"
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
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const locations = ref([])
const loading = ref(false)
const router = useRouter()
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ location_id: null })

const form = reactive({
  id: null, location_id: null, name: '', floors: 1, is_active: true
})

const errors = reactive({
  name: '', location_id: '', floors: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'name', header: 'اسم المبنى' },
  { field: 'location.name', header: 'الموقع العقاري' },
  { field: 'floors', header: 'عدد الطوابق' },
  { field: 'units_count', header: 'عدد الوحدات' },
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
    errors.name = 'يرجى إدخال اسم المبنى'
    isValid = false
  }

  if (!form.location_id) {
    errors.location_id = 'يرجى اختيار الموقع العقاري المرتبط'
    isValid = false
  }

  if (!form.floors || form.floors < 1) {
    errors.floors = 'يجب أن يكون عدد الطوابق طابق واحد على الأقل'
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
      label: 'عرض الملف',
      icon: 'pi pi-eye',
      command: () => router.push(`/buildings/${row.id}`)
    },
    {
      label: 'تعديل',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'حذف المبنى',
      icon: 'pi pi-trash',
      danger: true,
      command: () => confirmDelete(row)
    }
  ]
}

onMounted(() => { fetchLocations(); fetchItems() })

async function fetchLocations() {
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data
  } catch (err) {
    console.error(err)
  }
}

async function fetchItems() {
  loading.value = true
  try {
    const params = filters.location_id ? { location_id: filters.location_id } : {}
    const { data } = await api.get('/buildings', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل قائمة المباني: ' + (err.response?.data?.message || err.message))
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
  Object.assign(form, { ...item, location_id: item.location_id || item.location?.id })
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  form.id = null
  form.location_id = null
  form.name = ''
  form.floors = 1
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
      const { data } = await api.put(`/buildings/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل بيانات المبنى بنجاح')
    } else {
      const { data } = await api.post('/buildings', form)
      items.value.unshift(data.data)
      toast.success('تم إضافة المبنى بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ بيانات المبنى')
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
    toast.success('تم حذف المبنى بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'خطأ أثناء عملية الحذف')
  }
}
</script>

<style scoped>
.building-name-cell {
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

.location-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.floors-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.units-count-pill {
  background: var(--bg-subtle);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}

.text-blue {
  color: var(--info-contrast);
}

.filter-select {
  width: 180px !important;
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
