<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="بحث برقم الوحدة أو المبنى..."
      emptyTitle="لا توجد وحدات عقارية مسجلة"
      emptySubtitle="لم يتم العثور على أي وحدات تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.building_id"
          :options="buildings"
          optionLabel="name"
          optionValue="id"
          placeholder="جميع المباني"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
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
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة وحدة جديدة
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('unit_number')" field="unit_number" header="رقم / اسم الوحدة" sortable>
          <template #body="slotProps">
            <div class="unit-cell">
              <div class="icon-avatar">
                <i class="pi pi-home text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">وحدة #{{ slotProps.data.unit_number }}</span>
                <span class="sub-text">طابق {{ slotProps.data.floor }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('building.name')" field="building.name" header="المبنى" sortable>
          <template #body="slotProps">
            <span class="building-name" v-if="slotProps.data.building">
              <i class="pi pi-building text-muted"></i>
              {{ slotProps.data.building.name }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('unit_type')" field="unit_type" header="نوع الوحدة" sortable>
          <template #body="slotProps">
            <span class="type-badge">
              {{ typeLabels[slotProps.data.unit_type] || slotProps.data.unit_type }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('area')" field="area" header="المساحة (م²)" sortable>
          <template #body="slotProps">
            <span v-if="slotProps.data.area" class="area-text">{{ slotProps.data.area }} م²</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('rent_amount')" field="rent_amount" header="قيمة الإيجار" sortable>
          <template #body="slotProps">
            <span class="rent-amount">{{ formatCurrency(slotProps.data.rent_amount) }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('status')" field="status" header="الحالة التشغيلية" sortable>
          <template #body="slotProps">
            <span
              class="status-badge"
              :class="getStatusClass(slotProps.data.status)"
            >
              {{ statusLabels[slotProps.data.status] || slotProps.data.status }}
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
      :header="isEditing ? 'تعديل بيانات الوحدة العقارية' : 'إضافة وحدة عقارية جديدة'"
      modal
      :style="{ width: '600px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Location & Identity -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-building"></i>
            <span>المبنى ورقم الوحدة</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="المبنى العقاري"
              required
              forId="unit-building"
              :errorMessage="errors.building_id"
              helpText="المبنى الحاوي على الوحدة"
            >
              <Select
                id="unit-building"
                v-model="form.building_id"
                :options="buildings"
                optionLabel="name"
                optionValue="id"
                placeholder="اختر المبنى"
                class="w-full"
                filter
                @change="clearFieldError('building_id')"
              />
            </FormField>

            <FormField
              label="رقم / اسم الوحدة"
              required
              forId="unit-number"
              :errorMessage="errors.unit_number"
              helpText="مثال: 101 أو A2"
            >
              <InputText
                id="unit-number"
                v-model="form.unit_number"
                placeholder="أدخل رقم الوحدة"
                class="w-full"
                @input="clearFieldError('unit_number')"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: Specifications & Rent -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-sliders-h"></i>
            <span>المواصفات والماليات</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="نوع الاستخدام"
              required
              forId="unit-type"
              :errorMessage="errors.unit_type"
            >
              <Select
                id="unit-type"
                v-model="form.unit_type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                filter
              />
            </FormField>

            <FormField
              label="رقم الطابق"
              forId="unit-floor"
              :errorMessage="errors.floor"
            >
              <InputNumber
                id="unit-floor"
                v-model="form.floor"
                class="w-full"
                :min="0"
                :max="100"
              />
            </FormField>

            <FormField
              label="المساحة (م²)"
              forId="unit-area"
              :errorMessage="errors.area"
            >
              <InputNumber
                id="unit-area"
                v-model="form.area"
                class="w-full"
                :min="1"
                placeholder="أدخل المساحة بالمتر المربع"
              />
            </FormField>

            <FormField
              label="قيمة الإيجار الشهري (₪)"
              required
              forId="unit-rent"
              :errorMessage="errors.rent_amount"
              helpText="المبلغ التقديري للإيجار الشهري"
            >
              <InputNumber
                id="unit-rent"
                v-model="form.rent_amount"
                class="w-full"
                :min="0"
                placeholder="أدخل قيمة الإيجار"
                @input="clearFieldError('rent_amount')"
              />
            </FormField>
          </div>

          <FormField
            label="الحالة التشغيلية للوحدة"
            forId="unit-status"
          >
            <Select
              id="unit-status"
              v-model="form.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              filter
            />
          </FormField>
        </div>

        <!-- Section 3: Service Fees (Default Values) -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-bolt"></i>
            <span>رسوم الخدمات الافتراضية (تعبأ تلقائيًا في العقد)</span>
          </div>

          <div class="form-grid-3">
            <FormField label="الكهرباء (₪)" forId="unit-electricity">
              <InputNumber
                id="unit-electricity"
                v-model="form.electricity_amount"
                class="w-full"
                :min="0"
                placeholder="0"
              />
            </FormField>

            <FormField label="المياه (₪)" forId="unit-water">
              <InputNumber
                id="unit-water"
                v-model="form.water_amount"
                class="w-full"
                :min="0"
                placeholder="0"
              />
            </FormField>

            <FormField label="الإنترنت (₪)" forId="unit-internet">
              <InputNumber
                id="unit-internet"
                v-model="form.internet_amount"
                class="w-full"
                :min="0"
                placeholder="0"
              />
            </FormField>

            <FormField label="خدمات أخرى (₪)" forId="unit-services">
              <InputNumber
                id="unit-services"
                v-model="form.services_amount"
                class="w-full"
                :min="0"
                placeholder="0"
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
      :message="`هل أنت متأكد من حذف الوحدة <strong>#${ itemToDelete?.unit_number }</strong>؟`"
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
const buildings = ref([])
const loading = ref(false)
const toast = useToastStore()
const router = useRouter()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const filters = reactive({ status: null, building_id: null })

const form = reactive({
  id: null, building_id: null, unit_number: '', unit_type: 'apartment',
  floor: 0, area: null, rent_amount: null,
  electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0,
  status: 'available'
})

const errors = reactive({
  building_id: '', unit_number: '', unit_type: '', floor: '', area: '', rent_amount: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'unit_number', header: 'رقم / اسم الوحدة' },
  { field: 'building.name', header: 'المبنى' },
  { field: 'unit_type', header: 'نوع الوحدة' },
  { field: 'area', header: 'المساحة (م²)' },
  { field: 'rent_amount', header: 'قيمة الإيجار' },
  { field: 'status', header: 'الحالة التشغيلية' }
]

const typeLabels = { apartment: 'شقة سكنية', shop: 'محل تجاري', warehouse: 'مخزن' }
const statusLabels = { available: 'متاحة (شاغرة)', occupied: 'مشغولة (مؤجرة)', maintenance: 'تحت الصيانة' }

const typeOptions = ref([
  { label: 'شقة سكنية', value: 'apartment' },
  { label: 'محل تجاري', value: 'shop' },
  { label: 'مخزن', value: 'warehouse' }
])

const statusFilter = ref([
  { label: 'متاحة (شاغرة)', value: 'available' },
  { label: 'مشغولة (مؤجرة)', value: 'occupied' },
  { label: 'تحت الصيانة', value: 'maintenance' }
])

const statusOptions = ref([
  { label: 'متاحة (شاغرة)', value: 'available' },
  { label: 'مشغولة (مؤجرة)', value: 'occupied' },
  { label: 'تحت الصيانة', value: 'maintenance' }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.building_id) {
    errors.building_id = 'يرجى اختيار المبنى'
    isValid = false
  }

  if (!form.unit_number || !form.unit_number.toString().trim()) {
    errors.unit_number = 'يرجى إدخال رقم أو اسم الوحدة'
    isValid = false
  }

  if (form.rent_amount === null || form.rent_amount === undefined || form.rent_amount < 0) {
    errors.rent_amount = 'يرجى إدخال قيمة الإيجار الشهرية الصحيحة'
    isValid = false
  }

  return isValid
}

function isFormDirty() {
  return JSON.stringify(form) !== initialFormState
}

function getStatusClass(status) {
  switch (status) {
    case 'available': return 'status-available'
    case 'occupied': return 'status-occupied'
    case 'maintenance': return 'status-maintenance'
    default: return 'status-info'
  }
}

function getRowActions(row) {
  return [
    {
      label: 'عرض الملف',
      icon: 'pi pi-eye',
      command: () => router.push(`/units/${row.id}`)
    },
    {
      label: 'تعديل الوحدة',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'حذف الوحدة',
      icon: 'pi pi-trash',
      danger: true,
      command: () => confirmDelete(row)
    }
  ]
}

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchBuildings(); fetchItems() })

async function fetchBuildings() {
  try {
    const { data } = await api.get('/buildings')
    buildings.value = data.data
  } catch (err) {
    console.error(err)
  }
}

async function fetchItems() {
  loading.value = true
  try {
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.building_id) params.building_id = filters.building_id

    const { data } = await api.get('/units', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل الوحدات: ' + (err.response?.data?.message || err.message))
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
  Object.assign(form, { ...item, building_id: item.building_id || item.building?.id })
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  Object.assign(form, {
    id: null, building_id: null, unit_number: '', unit_type: 'apartment',
    floor: 0, area: null, rent_amount: null,
    electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0,
    status: 'available'
  })
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
      const { data } = await api.put(`/units/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل بيانات الوحدة بنجاح')
    } else {
      const { data } = await api.post('/units', form)
      items.value.unshift(data.data)
      toast.success('تم إضافة الوحدة بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ بيانات الوحدة')
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
    await api.delete(`/units/${itemToDelete.value.id}`)
    toast.success('تم حذف الوحدة بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'خطأ أثناء عملية الحذف')
  }
}
</script>

<style scoped>
.unit-cell {
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

.building-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
}

.type-badge {
  background: var(--bg-subtle, #F1F5F9);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}

.area-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.rent-amount {
  font-weight: 700;
  color: var(--success);
}

.filter-select {
  width: 170px !important;
}

.text-blue {
  color: #2563EB;
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
