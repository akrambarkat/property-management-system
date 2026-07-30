<template>
  <div class="page-view">
    <!-- Header Banner / Feedback -->
            <!-- Enterprise SaaS Card Table Layout -->
    <EnterpriseTable
      :value="items"
      :loading="loading"
      searchPlaceholder="بحث بوصف الصيانة، رقم الوحدة، أو الأولوية..."
      emptyTitle="لا توجد بلاغات صيانة مسجلة"
      emptySubtitle="لم يتم العثور على أي طلبات صيانة تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      @refresh="fetchItems"
    >
      <template #filters>
        <Select
          v-model="filters.status"
          :options="statusFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الحالات"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
        <Select
          v-model="filters.priority"
          :options="priorityFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الأولويات"
          showClear
          @change="fetchItems"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreateDialog">
          <i class="pi pi-plus"></i> بلاغ صيانة جديد
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('unit.unit_number')" field="unit.unit_number" header="الوحدة والمبنى" sortable>
          <template #body="slotProps">
            <div class="unit-cell">
              <div class="icon-avatar">
                <i class="pi pi-wrench text-blue"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">وحدة #{{ slotProps.data.unit?.unit_number || '—' }}</span>
                <span class="sub-text">{{ slotProps.data.unit?.building?.name || 'مبنى غير محدد' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('description')" field="description" header="وصف المشكلة">
          <template #body="slotProps">
            <span class="desc-text">{{ slotProps.data.description || '—' }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('priority')" field="priority" header="الأولوية">
          <template #body="slotProps">
            <span :class="'priority-pill priority-' + slotProps.data.priority">
              {{ priorityLabels[slotProps.data.priority] || slotProps.data.priority }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('status')" field="status" header="الحالة التشغيلية">
          <template #body="slotProps">
            <span :class="'status-badge status-' + slotProps.data.status">
              {{ maintStatusLabels[slotProps.data.status] || slotProps.data.status }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('created_at')" field="created_at" header="تاريخ الطلب" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.created_at?.split('T')[0] || '—' }}</span>
          </template>
        </Column>

        <!-- Actions -->
        <Column header="الإجراءات" style="width: 80px; text-align: center;">
          <template #body="slotProps">
            <TableActionMenu :items="getRowActions(slotProps.data)" />
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل طلب الصيانة' : 'إضافة طلب صيانة جديد'"
      modal
      :style="{ width: '560px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <!-- Section 1: Location & Problem Description -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-home"></i>
            <span>موقع الصيانة والمشكلة</span>
          </div>

          <FormField
            label="الوحدة العقارية"
            required
            forId="maint-unit"
            :errorMessage="errors.unit_id"
            helpText="اختر الوحدة المتأثرة بالمشكلة"
          >
            <Select
              id="maint-unit"
              v-model="form.unit_id"
              :options="units"
              optionLabel="label"
              optionValue="id"
              placeholder="اختر الوحدة المستهدفة"
              class="w-full"
              @change="clearFieldError('unit_id')"
            />
          </FormField>

          <FormField
            label="وصف المشكلة / العطل"
            required
            forId="maint-desc"
            :errorMessage="errors.description"
            helpText="اشرح المشكلة بالتفصيل لتوجيه الفني المناسب"
          >
            <Textarea
              id="maint-desc"
              v-model="form.description"
              class="w-full"
              rows="3"
              placeholder="أدخل تفاصيل العطل أو أعمال الصيانة المطلوبة..."
              @input="clearFieldError('description')"
            />
          </FormField>
        </div>

        <!-- Section 2: Status & Priority -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="pi pi-sliders-h"></i>
            <span>درجة الأولوية وحالة المتابعة</span>
          </div>

          <div class="form-grid-2">
            <FormField
              label="درجة الأولوية"
              forId="maint-priority"
            >
              <Select
                id="maint-priority"
                v-model="form.priority"
                :options="priorityOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
              />
            </FormField>

            <FormField
              label="حالة الطلب"
              forId="maint-status"
            >
              <Select
                id="maint-status"
                v-model="form.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
              />
            </FormField>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ الطلب' }}</span>
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
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import FormField from '@/components/common/FormField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const items = ref([])
const units = ref([])
const loading = ref(false)
const toast = useToastStore()
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)

const filters = reactive({ status: null, priority: null })

const form = reactive({ id: null, unit_id: null, description: '', priority: 'medium', status: 'pending' })

const errors = reactive({
  unit_id: '', description: ''
})

const initialFormState = JSON.stringify(form)

const tableColumns = [
  { field: 'unit.unit_number', header: 'الوحدة والمبنى' },
  { field: 'description', header: 'وصف المشكلة' },
  { field: 'priority', header: 'الأولوية' },
  { field: 'status', header: 'الحالة التشغيلية' },
  { field: 'created_at', header: 'تاريخ الطلب' }
]

const priorityLabels = { low: 'منخفضة', medium: 'متوسطة', high: 'عالية (طوارئ)' }
const maintStatusLabels = { pending: 'قيد الانتظار', in_progress: 'قيد التنفيذ', completed: 'مكتملة', cancelled: 'ملغاة' }

const priorityOptions = ref([
  { label: 'منخفضة', value: 'low' },
  { label: 'متوسطة', value: 'medium' },
  { label: 'عالية (طوارئ)', value: 'high' }
])

const priorityFilterOptions = ref([
  { label: 'منخفضة', value: 'low' },
  { label: 'متوسطة', value: 'medium' },
  { label: 'عالية (طوارئ)', value: 'high' }
])

const statusOptions = ref([
  { label: 'قيد الانتظار', value: 'pending' },
  { label: 'قيد التنفيذ', value: 'in_progress' },
  { label: 'مكتملة', value: 'completed' },
  { label: 'ملغاة', value: 'cancelled' }
])

const statusFilterOptions = ref([
  { label: 'قيد الانتظار', value: 'pending' },
  { label: 'قيد التنفيذ', value: 'in_progress' },
  { label: 'مكتملة', value: 'completed' },
  { label: 'ملغاة', value: 'cancelled' }
])

function clearFieldError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.unit_id) {
    errors.unit_id = 'يرجى اختيار الوحدة العقارية المتأثرة'
    isValid = false
  }

  if (!form.description || !form.description.trim()) {
    errors.description = 'يرجى كتابة وصف توضيحي للمشكلة'
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
      label: 'تعديل الطلب',
      icon: 'pi pi-pencil',
      command: () => editItem(row)
    }
  ]
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
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.priority) params.priority = filters.priority

    const { data } = await api.get('/maintenance-requests', { params })
    items.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل طلبات الصيانة: ' + (err.response?.data?.message || err.message))
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
  Object.assign(form, { id: null, unit_id: null, description: '', priority: 'medium', status: 'pending' })
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
      const { data } = await api.put(`/maintenance-requests/${form.id}`, form)
      const idx = items.value.findIndex(i => i.id === form.id)
      if (idx > -1) items.value[idx] = data.data
      toast.success('تم تعديل طلب الصيانة بنجاح')
    } else {
      const { data } = await api.post('/maintenance-requests', form)
      items.value.unshift(data.data)
      toast.success('تم إضافة طلب الصيانة بنجاح')
    }
    showDialog.value = false
    resetForm()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ طلب الصيانة')
  } finally {
    saving.value = false
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

.desc-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.date-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.priority-pill {
  padding: 3px 9px;
  border-radius: var(--radius-full);
  font-size: 11.5px;
  font-weight: 600;
}
.priority-low { background: #F1F5F9; color: #475569; }
.priority-medium { background: #FEF3C7; color: #D97706; }
.priority-high { background: #FEE2E2; color: #DC2626; }

.filter-select {
  width: 170px !important;
}

.text-blue {
  color: #2563EB;
}
</style>
