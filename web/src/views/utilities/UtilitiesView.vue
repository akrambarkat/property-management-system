<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="بحث بنوع المرافق، رقم الوحدة، أو العداد..."
      emptyTitle="لا توجد قراءات عدادات مسجلة"
      emptySubtitle="لم يتم العثور على أي قراءات تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.utility_type"
          :options="typeOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع أنواع الخدمات"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> تسجيل قراءة عداد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('utility_type')" field="utility_type" header="نوع الخدمة" sortable>
          <template #body="slotProps">
            <span class="utility-badge">
              <i :class="slotProps.data.utility_type === 'electricity' ? 'pi pi-bolt text-warning' : 'pi pi-compass text-info'"></i>
              {{ typeLabels[slotProps.data.utility_type] || slotProps.data.utility_type }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('unit.unit_number')" field="unit.unit_number" header="الوحدة والمبنى" sortable>
          <template #body="slotProps">
            <div class="unit-cell">
              <div class="cell-text">
                <span class="font-bold">وحدة #{{ slotProps.data.unit?.unit_number || '—' }}</span>
                <span class="sub-text">{{ slotProps.data.unit?.building?.name || 'مبنى غير محدد' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('reading_date')" field="reading_date" header="تاريخ القراءة" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.reading_date || '—' }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('previous_reading')" field="previous_reading" header="القراءة السابقة">
          <template #body="slotProps">
            <span class="text-muted">{{ slotProps.data.previous_reading || 0 }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('current_reading')" field="current_reading" header="القراءة الحالية">
          <template #body="slotProps">
            <span class="font-bold">{{ slotProps.data.current_reading || 0 }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('consumption')" field="consumption" header="معدل الاستهلاك" sortable>
          <template #body="slotProps">
            <span class="consumption-tag">{{ slotProps.data.consumption || 0 }} وحدة</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('total')" field="total" header="إجمالي التكلفة" sortable>
          <template #body="slotProps">
            <span class="total-amount">{{ formatCurrency(slotProps.data.total) }}</span>
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <!-- Dialog -->
    <Dialog
      v-model:visible="showDialog"
      header="تسجيل قراءة عداد جديدة"
      modal
      :style="{ width: '540px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Unit & Type -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-bolt"></i>
            <span>الوحدة ونوع الخدمة</span>
          </div>

          <FormField
            label="الوحدة العقارية"
            required
            forId="util-unit"
            :errorMessage="errors.unit_id"
            helpText="اختر الوحدة المزودة بالعداد"
          >
            <Select
              id="util-unit"
              v-model="form.unit_id"
              :options="units"
              optionLabel="label"
              optionValue="id"
              placeholder="اختر الوحدة المستهدفة"
              class="w-full"
              @change="clearFieldError('unit_id')"
            />
          </FormField>

          <div class="form-grid-2">
            <FormField
              label="نوع الخدمة"
              forId="util-type"
            >
              <Select
                id="util-type"
                v-model="form.utility_type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
              />
            </FormField>

            <FormField
              label="تاريخ القراءة"
              forId="util-date"
            >
              <DatePicker
                id="util-date"
                v-model="form.reading_date"
                class="w-full"
                placeholder="اختر التاريخ"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: Meter Readings -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-chart-line"></i>
            <span>قراءات العداد</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="القراءة السابقة"
              forId="util-prev"
            >
              <InputNumber
                id="util-prev"
                v-model="form.previous_reading"
                class="w-full"
                :min="0"
              />
            </FormField>

            <FormField
              label="القراءة الحالية"
              required
              forId="util-curr"
              :errorMessage="errors.current_reading"
            >
              <InputNumber
                id="util-curr"
                v-model="form.current_reading"
                class="w-full"
                :min="0"
                placeholder="أدخل القراءة الحالية"
                @input="clearFieldError('current_reading')"
              />
            </FormField>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ القراءة' }}</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const units = ref([])
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)

const filters = reactive({ utility_type: null })

const form = reactive({
  unit_id: null, utility_type: 'electricity', reading_date: null,
  previous_reading: 0, current_reading: null
})

const errors = reactive({
  unit_id: '', current_reading: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'utility_type', header: 'نوع الخدمة' },
  { field: 'unit.unit_number', header: 'الوحدة والمبنى' },
  { field: 'reading_date', header: 'تاريخ القراءة' },
  { field: 'previous_reading', header: 'القراءة السابقة' },
  { field: 'current_reading', header: 'القراءة الحالية' },
  { field: 'consumption', header: 'معدل الاستهلاك' },
  { field: 'total', header: 'إجمالي التكلفة' }
]

const typeLabels = { electricity: 'كهرباء', water: 'مياه', gas: 'غاز' }
const typeOptions = ref([
  { label: 'كهرباء', value: 'electricity' },
  { label: 'مياه', value: 'water' },
  { label: 'غاز', value: 'gas' }
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

  if (form.current_reading === null || form.current_reading === undefined || form.current_reading < 0) {
    errors.current_reading = 'يرجى إدخال القراءة الحالية للعداد'
    isValid = false
  }

  return isValid
}

function isFormDirty() {
  return JSON.stringify(form) !== initialFormState
}

function formatCurrency(amount) {
  if (!amount) return '0 ₪'
  return `${Number(amount).toLocaleString('ar-EG')} ₪`
}

onMounted(() => { fetchUnits(); fetchItems() })

async function fetchUnits() {
  try {
    const { data } = await api.get('/units')
    units.value = data.data.map(u => ({ ...u, label: `وحدة #${u.unit_number} - ${u.building?.name || ''}` }))
  } catch (err) { console.error(err) }
}

async function fetchItems() {
  loading.value = true
  try {
    const params = filters.utility_type ? { utility_type: filters.utility_type } : {}
    const { data } = await api.get('/utility-readings', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل قراءات الخدمات: ' + (err.response?.data?.message || err.message))
    items.value = []
  } finally {
    loading.value = false
  }
}


function openCreateDialog() {
  resetForm()
  showDialog.value = true
}

function resetForm() {
  Object.assign(form, {
    unit_id: null, utility_type: 'electricity', reading_date: null,
    previous_reading: 0, current_reading: null
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
    const { data } = await api.post('/utility-readings', form)
    items.value.unshift(data.data)
    toast.success('تم تسجيل قراءة العداد بنجاح')
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر تسجيل قراءة العداد')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.utility-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12.5px;
  font-weight: 600;
}

.unit-cell {
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

.date-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.consumption-tag {
  background: #EFF6FF;
  color: #2563EB;
  padding: 3px 8px;
  border-radius: var(--radius-xs);
  font-weight: 600;
  font-size: 12px;
}

.total-amount {
  font-weight: 700;
  color: var(--text-primary);
}

.filter-select {
  width: 170px !important;
}

.text-warning { color: #F59E0B; }
.text-info { color: #0EA5E9; }
</style>
