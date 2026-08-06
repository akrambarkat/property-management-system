<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      entity="expenses"
      :exportParams="filters"
      :loading="loading"
      searchPlaceholder="بحث ببيان المصروف، التصنيف، أو المبنى..."
      emptyTitle="لا توجد مصروفات مسجلة"
      emptySubtitle="لم يتم العثور على أي مصروفات تطابق خيارات التصفية والبحث"
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
          v-model="filters.category"
          :options="categoryOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع التصنيفات"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إضافة مصروف جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('building.name')" field="building.name" header="المبنى العقاري" sortable>
          <template #body="slotProps">
            <span class="building-name" v-if="slotProps.data.building">
              <i class="pi pi-building text-muted"></i>
              {{ slotProps.data.building.name }}
            </span>
            <span v-else class="text-muted">مصروف عام</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('unit.unit_number')" field="unit.unit_number" header="الوحدة" sortable>
          <template #body="slotProps">
            <span v-if="slotProps.data.unit" class="unit-pill">
              <i class="pi pi-home text-blue"></i>
              وحدة #{{ slotProps.data.unit.unit_number }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('category')" field="category" header="تصنيف المصروف" sortable>
          <template #body="slotProps">
            <span class="category-badge">
              {{ categoryLabels[slotProps.data.category] || slotProps.data.category }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('amount')" field="amount" header="المبلغ" sortable>
          <template #body="slotProps">
            <span class="expense-amount">{{ formatCurrency(slotProps.data.amount) }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('expense_date')" field="expense_date" header="التاريخ" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.expense_date || '—' }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('description')" field="description" header="البيان / التفاصيل">
          <template #body="slotProps">
            <span class="desc-text">{{ slotProps.data.description || '—' }}</span>
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
      :header="isEditing ? 'تعديل بيانات المصروف' : 'إضافة مصروف جديد'"
      modal
      :style="{ width: '560px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Classification & Building -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-building"></i>
            <span>المبنى والتصنيف</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="الموقع العقاري"
              required
              forId="exp-location"
              :errorMessage="errors.location_id"
              helpText="اختيار الموقع يفلتر البنايات التابعة له"
            >
              <Select
                id="exp-location"
                v-model="selectedLocation"
                :options="locations"
                optionLabel="name"
                optionValue="id"
                placeholder="اختر الموقع"
                class="w-full"
                filter
                @change="onLocationChange"
              />
            </FormField>

            <FormField
              label="المبنى العقاري"
              required
              forId="exp-building"
              :errorMessage="errors.building_id"
              helpText="المبنى الذي صُرف عليه المبلغ"
            >
              <Select
                id="exp-building"
                v-model="form.building_id"
                :options="availableBuildings"
                optionLabel="name"
                optionValue="id"
                placeholder="اختر المبنى"
                class="w-full"
                filter
                :disabled="!selectedLocation"
                @change="onBuildingChange"
              />
            </FormField>

            <FormField
              label="الوحدة العقارية"
              forId="exp-unit"
              helpText="اختياري - ربط المصروف بوحدة محددة"
            >
              <Select
                id="exp-unit"
                v-model="form.unit_id"
                :options="availableUnits"
                optionLabel="unit_number"
                optionValue="id"
                placeholder="اختر الوحدة (اختياري)"
                class="w-full"
                filter
                showClear
                :disabled="!selectedBuilding"
              />
            </FormField>

            <FormField
              label="تصنيف المصروف"
              forId="exp-cat"
            >
              <Select
                id="exp-cat"
                v-model="form.category"
                :options="categoryOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                filter
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: Amount & Description -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-money-bill"></i>
            <span>المبلغ والتاريخ</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="المبلغ (₪)"
              required
              forId="exp-amount"
              :errorMessage="errors.amount"
            >
              <InputNumber
                id="exp-amount"
                v-model="form.amount"
                class="w-full"
                :min="0"
                placeholder="أدخل المبلغ"
                @input="clearFieldError('amount')"
              />
            </FormField>

            <FormField
              label="تاريخ الصرف"
              forId="exp-date"
            >
              <DatePicker
                id="exp-date"
                v-model="form.expense_date"
                class="w-full"
                placeholder="اختر التاريخ"
              />
            </FormField>
          </div>

          <FormField
            label="تفاصيل وبيان المصروف"
            forId="exp-desc"
            helpText="توضيح الأسباب أو الجهة المستلمة للمبلغ"
          >
            <Textarea
              id="exp-desc"
              v-model="form.description"
              class="w-full"
              rows="3"
              placeholder="أدخل تفاصيل وملاحظات المصروف"
            />
          </FormField>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ المصروف' }}</span>
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Delete Modal -->
    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="تأكيد الحذف"
      :message="`هل أنت متأكد من حذف المصروف بقيمة <strong>${ formatCurrency(itemToDelete?.amount) }</strong>؟`"
      variant="danger"
      confirmText="تأكيد الحذف"
      @confirm="deleteItemConfirmed"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const locations = ref([])
const buildings = ref([])
const units = ref([])
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const selectedLocation = ref(null)
const selectedBuilding = ref(null)

const filters = reactive({ building_id: null, category: null })

const form = reactive({
  id: null, building_id: null, unit_id: null, category: 'general', amount: null,
  expense_date: null, description: ''
})

const errors = reactive({
  location_id: '', building_id: '', amount: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'building.name', header: 'المبنى العقاري' },
  { field: 'unit.unit_number', header: 'الوحدة', tabletHidden: true },
  { field: 'category', header: 'تصنيف المصروف' },
  { field: 'amount', header: 'المبلغ' },
  { field: 'expense_date', header: 'التاريخ', tabletHidden: true },
  { field: 'description', header: 'البيان / التفاصيل' }
]

const categoryLabels = {
  maintenance: 'صيانة وتصليحات',
  plumbing: 'سباكة',
  electrical: 'كهرباء',
  cleaning: 'نظافة وتدبير',
  security: 'حراسة وأمن',
  general: 'مصروف عام'
}

const categoryOptions = ref([
  { label: 'صيانة وتصليحات', value: 'maintenance' },
  { label: 'سباكة', value: 'plumbing' },
  { label: 'كهرباء', value: 'electrical' },
  { label: 'نظافة وتدبير', value: 'cleaning' },
  { label: 'حراسة وأمن', value: 'security' },
  { label: 'مصروف عام', value: 'general' }
])

const availableBuildings = computed(() => {
  if (!selectedLocation.value) return []
  return buildings.value.filter(building => building.location_id === selectedLocation.value)
})

const availableUnits = computed(() => {
  if (!selectedBuilding.value) return []
  return units.value.filter(unit => unit.building_id === selectedBuilding.value)
})

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!selectedLocation.value) {
    errors.location_id = 'يرجى اختيار الموقع العقاري'
    isValid = false
  }

  if (!form.building_id) {
    errors.building_id = 'يرجى اختيار المبنى العقاري'
    isValid = false
  }

  if (form.amount === null || form.amount === undefined || form.amount <= 0) {
    errors.amount = 'يرجى إدخال مبلغ مصروف صحيح أكبر من صفر'
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
      label: 'تعديل المصروف',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    },
    {
      label: 'حذف المصروف',
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

onMounted(() => { fetchLocations(); fetchBuildings(); fetchUnits(); fetchItems() })

async function fetchLocations() {
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data || []
  } catch (err) { console.error(err) }
}

async function fetchBuildings() {
  try {
    const { data } = await api.get('/buildings')
    buildings.value = data.data
  } catch (err) { console.error(err) }
}

async function fetchUnits() {
  try {
    const { data } = await api.get('/units')
    units.value = data.data
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    const params = {}
    if (filters.building_id) params.building_id = filters.building_id
    if (filters.category) params.category = filters.category

    const { data } = await api.get('/expenses', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل المصروفات: ' + (err.response?.data?.message || err.message))
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
  selectedLocation.value = item.building?.location_id || item.building?.location?.id || null
  selectedBuilding.value = item.building_id || item.building?.id || null
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  isEditing.value = false
  selectedLocation.value = null
  selectedBuilding.value = null
  Object.assign(form, {
    id: null, building_id: null, unit_id: null, category: 'general', amount: null,
    expense_date: null, description: ''
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

function onLocationChange() {
  selectedBuilding.value = null
  form.building_id = null
  form.unit_id = null
  clearFieldError('location_id')
  clearFieldError('building_id')
}

function onBuildingChange() {
  form.unit_id = null
  clearFieldError('building_id')
}

async function saveItem() {
  if (!validateForm()) return

  saving.value = true
  try {
    if (isEditing.value) {
      const { data } = await api.put(`/expenses/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل المصروف بنجاح')
    } else {
      const { data } = await api.post('/expenses', form)
      items.value.unshift(data.data)
      toast.success('تم إضافة المصروف بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ المصروف')
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
    await api.delete(`/expenses/${itemToDelete.value.id}`)
    toast.success('تم حذف المصروف بنجاح')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'خطأ أثناء عملية الحذف')
  }
}
</script>

<style scoped>
.building-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
}

.unit-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-subtle);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}

.text-blue {
  color: var(--info-contrast);
}

.category-badge {
  background: var(--bg-subtle);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}

.expense-amount {
  font-weight: 700;
  color: var(--danger);
}

.date-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.desc-text {
  font-size: 13px;
  color: var(--text-secondary);
}

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
  background: var(--danger-hover) !important;
}
</style>
