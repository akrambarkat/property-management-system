<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      entity="contracts"
      :exportParams="filters"
      :loading="loading"
      searchPlaceholder="بحث برقم العقد، المستأجر، أو الوحدة..."
      emptyTitle="لا توجد عقود إيجار مسجلة"
      emptySubtitle="لم يتم العثور على أي عقود تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.status"
          :options="statusFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع حالات العقود"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> إنشاء عقد جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('id')" field="id" header="رقم العقد" sortable>
          <template #body="slotProps">
            <span class="contract-code">CNT-{{ String(slotProps.data.id).padStart(4, '0') }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('unit.unit_number')" field="unit.unit_number" header="الوحدة والمبنى" sortable>
          <template #body="slotProps">
            <div class="unit-cell">
              <div class="icon-avatar">
                <i class="pi pi-building text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">وحدة #{{ slotProps.data.unit?.unit_number || '—' }}</span>
                <span class="sub-text">{{ slotProps.data.unit?.building?.name || 'مبنى غير محدد' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('tenant.first_name')" field="tenant.first_name" header="المستأجر" sortable>
          <template #body="slotProps">
            <span class="tenant-name" v-if="slotProps.data.tenant">
              <i class="pi pi-user text-muted"></i>
              {{ slotProps.data.tenant.first_name }} {{ slotProps.data.tenant.last_name }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('start_date')" field="start_date" header="مدة العقد" sortable>
          <template #body="slotProps">
            <div class="date-range-cell">
              <span>{{ slotProps.data.start_date || '—' }}</span>
              <i class="pi pi-arrow-left text-muted"></i>
              <span>{{ slotProps.data.end_date || '—' }}</span>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('rent_amount')" field="rent_amount" header="قيمة الإيجار" sortable>
          <template #body="slotProps">
            <span class="rent-amount">{{ formatCurrency(slotProps.data.rent_amount) }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('status')" field="status" header="حالة العقد" sortable>
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
      :header="isEditing ? 'تعديل بيانات العقد' : 'إنشاء عقد إيجار جديد'"
      modal
      :style="{ width: '640px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Contract Parties -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-users"></i>
            <span>أطراف العقد والوحدة</span>
          </div>

          <div class="form-grid-3">
            <FormField
              label="الموقع"
              required
              forId="contract-location"
            >
              <Select
                id="contract-location"
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
              label="العمارة"
              required
              forId="contract-building"
            >
              <Select
                id="contract-building"
                v-model="selectedBuilding"
                :options="buildings"
                optionLabel="name"
                optionValue="id"
                placeholder="اختر العمارة"
                class="w-full"
                :disabled="!selectedLocation"
                filter
                @change="onBuildingChange"
              />
            </FormField>

            <FormField
              label="الوحدة العقارية"
              required
              forId="contract-unit"
              :errorMessage="errors.unit_id"
              helpText="اختر الوحدة المتاحة للتأجير"
            >
              <Select
                id="contract-unit"
                v-model="form.unit_id"
                :options="filteredUnits"
                optionLabel="label"
                optionValue="id"
                placeholder="اختر الوحدة"
                class="w-full"
                :disabled="!selectedBuilding"
                filter
                @change="onUnitChange"
              />
            </FormField>

            <FormField
              label="المستأجر"
              required
              forId="contract-tenant"
              :errorMessage="errors.tenant_id"
              helpText="المستأجر المستهدف بالعقد"
            >
              <Select
                id="contract-tenant"
                v-model="form.tenant_id"
                :options="tenants"
                optionLabel="label"
                optionValue="id"
                placeholder="اختر المستأجر"
                class="w-full"
                filter
                @change="clearFieldError('tenant_id')"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: Dates & Financial Terms -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-calendar"></i>
            <span>المدة والبنود المالية</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="تاريخ بداية العقد"
              required
              forId="contract-start"
              :errorMessage="errors.start_date"
            >
              <DatePicker
                id="contract-start"
                v-model="form.start_date"
                class="w-full"
                placeholder="اختر التاريخ"
                @change="clearFieldError('start_date')"
              />
            </FormField>

            <FormField
              label="تاريخ نهاية العقد"
              required
              forId="contract-end"
              :errorMessage="errors.end_date"
            >
              <DatePicker
                id="contract-end"
                v-model="form.end_date"
                class="w-full"
                placeholder="اختر التاريخ"
                @change="clearFieldError('end_date')"
              />
            </FormField>
          </div>

          <div class="form-grid-2">
            <FormField
              label="قيمة الإيجار (₪)"
              required
              forId="contract-rent"
              :errorMessage="errors.rent_amount"
            >
              <InputNumber
                id="contract-rent"
                v-model="form.rent_amount"
                class="w-full"
                :min="0"
                placeholder="أدخل قيمة الإيجار"
                @input="clearFieldError('rent_amount')"
              />
            </FormField>

            <FormField
              label="دورية السداد"
              forId="contract-type"
            >
              <Select
                id="contract-type"
                v-model="form.contract_type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                filter
              />
            </FormField>
          </div>
        </div>

        <!-- Section 3: Service Fees (Second Invoice) -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-bolt"></i>
            <span>رسوم الخدمات (فاتورة خدمات منفصلة)</span>
          </div>

          <div class="form-grid-3">
            <FormField label="الكهرباء (₪)" forId="contract-electricity">
              <InputNumber
                id="contract-electricity"
                v-model="form.electricity_amount"
                class="w-full"
                :min="0"
                placeholder="0"
              />
            </FormField>

            <FormField label="المياه (₪)" forId="contract-water">
              <InputNumber
                id="contract-water"
                v-model="form.water_amount"
                class="w-full"
                :min="0"
                placeholder="0"
              />
            </FormField>

            <FormField label="الإنترنت (₪)" forId="contract-internet">
              <InputNumber
                id="contract-internet"
                v-model="form.internet_amount"
                class="w-full"
                :min="0"
                placeholder="0"
              />
            </FormField>

            <FormField label="خدمات أخرى (₪)" forId="contract-services">
              <InputNumber
                id="contract-services"
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
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ بيانات العقد' }}</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const tenants = ref([])
const locations = ref([])
const buildings = ref([])
const filteredUnits = ref([])
const selectedLocation = ref(null)
const selectedBuilding = ref(null)
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const filters = reactive({ status: null })

const form = reactive({
  id: null, unit_id: null, tenant_id: null,
  start_date: null, end_date: null, rent_amount: null, contract_type: 'monthly',
  electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0
})

const errors = reactive({
  unit_id: '', tenant_id: '', start_date: '', end_date: '', rent_amount: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'id', header: 'رقم العقد' },
  { field: 'unit.unit_number', header: 'الوحدة والمبنى' },
  { field: 'tenant.first_name', header: 'المستأجر' },
  { field: 'start_date', header: 'مدة العقد' },
  { field: 'rent_amount', header: 'قيمة الإيجار' },
  { field: 'status', header: 'حالة العقد' }
]

const statusLabels = { active: 'نشط (ساري)', expired: 'منتهي', terminated: 'مفسوخ' }

const typeOptions = ref([
  { label: 'شهري', value: 'monthly' },
  { label: 'سنوي', value: 'annual' }
])

const statusFilterOptions = ref([
  { label: 'نشط (ساري)', value: 'active' },
  { label: 'منتهي', value: 'expired' },
  { label: 'مفسوخ', value: 'terminated' }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.unit_id) {
    errors.unit_id = 'يرجى اختيار الوحدة العقارية'
    isValid = false
  }

  if (!form.tenant_id) {
    errors.tenant_id = 'يرجى اختيار المستأجر'
    isValid = false
  }

  if (!form.start_date) {
    errors.start_date = 'يرجى اختيار تاريخ بداية العقد'
    isValid = false
  }

  if (!form.end_date) {
    errors.end_date = 'يرجى اختيار تاريخ نهاية العقد'
    isValid = false
  }

  if (form.rent_amount === null || form.rent_amount === undefined || form.rent_amount < 0) {
    errors.rent_amount = 'يرجى إدخال قيمة إيجار صحيحة'
    isValid = false
  }

  return isValid
}

function isFormDirty() {
  return JSON.stringify(form) !== initialFormState
}

function getStatusClass(status) {
  switch (status) {
    case 'active': return 'status-active'
    case 'expired': return 'status-expired'
    case 'terminated': return 'status-danger'
    default: return 'status-info'
  }
}

function getRowActions(row) {
  const actions = [
    {
      label: 'تعديل العقد',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    }
  ]

  if (row.status === 'active') {
    actions.push({
      label: 'إنهاء / فسخ العقد',
      icon: 'pi pi-times-circle',
      danger: true,
      command: () => terminateContract(row)
    })
  }

  return actions
}

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

const route = useRoute()
const router = useRouter()

onMounted(async () => {
  fetchLocations(); fetchTenants(); fetchItems()
  if (route.query.new === '1') {
    await new Promise(resolve => setTimeout(resolve, 300))
    openCreateDialog()
    router.replace({ path: route.path, query: {} })
  }
})

async function fetchLocations() {
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data || []
  } catch (err) { console.error(err) }
}

async function onLocationChange() {
  selectedBuilding.value = null
  form.unit_id = null
  buildings.value = []
  filteredUnits.value = []
  if (!selectedLocation.value) return
  try {
    const { data } = await api.get('/buildings', { params: { location_id: selectedLocation.value } })
    buildings.value = data.data || []
  } catch (err) { console.error(err) }
}

async function onBuildingChange() {
  form.unit_id = null
  filteredUnits.value = []
  if (!selectedBuilding.value) return
  try {
    const params = { building_id: selectedBuilding.value }
    if (!isEditing.value) params.status = 'available'
    const { data } = await api.get('/units', { params })
    filteredUnits.value = (data.data || []).map(u => ({
      ...u,
      label: `وحدة #${u.unit_number} - ${u.unit_type === 'apartment' ? 'شقة' : u.unit_type === 'shop' ? 'محل' : 'مستودع'}`
    }))
  } catch (err) { console.error(err) }
}

function onUnitChange() {
  clearFieldError('unit_id')
  if (!isEditing.value && form.unit_id) {
    const unit = filteredUnits.value.find(u => u.id === form.unit_id)
    if (!unit) return
    if (unit.rent_amount) form.rent_amount = Number(unit.rent_amount)
    if (unit.electricity_amount) form.electricity_amount = Number(unit.electricity_amount)
    if (unit.water_amount) form.water_amount = Number(unit.water_amount)
    if (unit.internet_amount) form.internet_amount = Number(unit.internet_amount)
    if (unit.services_amount) form.services_amount = Number(unit.services_amount)
  }
}

async function fetchTenants() {
  try {
    const { data } = await api.get('/tenants')
    tenants.value = data.data.map(t => ({ ...t, label: `${t.first_name} ${t.last_name} (${t.phone || t.id_number})` }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    const params = filters.status ? { status: filters.status } : {}
    const { data } = await api.get('/contracts', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل قائمة العقود: ' + (err.response?.data?.message || err.message))
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

  if (item.unit?.building) {
    selectedLocation.value = item.unit.building.location_id
    fetchBuildingsForEdit(item.unit.building_id, item.unit_id)
  }
}

async function fetchBuildingsForEdit(buildingId, unitId) {
  try {
    const { data } = await api.get('/buildings', { params: { location_id: selectedLocation.value } })
    buildings.value = data.data || []
    selectedBuilding.value = buildingId
    const params = { building_id: buildingId }
    const res = await api.get('/units', { params })
    filteredUnits.value = (res.data.data || []).map(u => ({
      ...u,
      label: `وحدة #${u.unit_number} - ${u.unit_type === 'apartment' ? 'شقة' : u.unit_type === 'shop' ? 'محل' : 'مستودع'}`
    }))
    form.unit_id = unitId
  } catch (err) { console.error(err) }
}

function resetForm() {
  isEditing.value = false
  Object.assign(form, {
    id: null, unit_id: null, tenant_id: null,
    start_date: null, end_date: null, rent_amount: null, contract_type: 'monthly',
    electricity_amount: 0, water_amount: 0, internet_amount: 0, services_amount: 0
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
      const { data } = await api.put(`/contracts/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل بيانات العقد بنجاح')
    } else {
      const { data } = await api.post('/contracts', form)
      items.value.unshift(data.data)
      toast.success('تم إنشاء العقد بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ بيانات العقد')
  } finally {
    saving.value = false
  }
}

async function terminateContract(contract) {
  if (!confirm(`هل أنت متأكد من فسخ العقد رقم CNT-${String(contract.id).padStart(4, '0')}؟`)) return
  try {
    await api.patch(`/contracts/${contract.id}/terminate`)
    toast.success('تم فسخ العقد بنجاح')
    await fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر فسخ العقد')
  }
}
</script>

<style scoped>
.contract-code {
  font-family: monospace;
  font-weight: 700;
  color: var(--accent-hover);
  background: var(--accent-light);
  padding: 3px 8px;
  border-radius: var(--radius-xs);
}

.unit-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: var(--accent-light);
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

.tenant-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
}

.date-range-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: var(--text-secondary);
}

.rent-amount {
  font-weight: 700;
  color: var(--success);
}

.filter-select {
  width: 180px !important;
}

.text-blue {
  color: var(--info-contrast);
}
</style>
