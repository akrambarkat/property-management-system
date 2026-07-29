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
          <InputText v-model="searchQuery" placeholder="بحث برقم الوحدة..." class="search-input-field" />
        </div>
        <Select
          v-model="filters.utility_type"
          :options="typeFilter"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الخدمات"
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
          <i class="pi pi-plus"></i> تسجيل قراءة جديدة
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
        <Column field="unit.unit_number" header="الوحدة العقارية" sortable>
          <template #body="slotProps">
            <div class="utility-cell">
              <div class="icon-avatar">
                <i :class="slotProps.data.utility_type === 'electricity' ? 'pi pi-bolt text-amber' : 'pi pi-compass text-blue'"></i>
              </div>
              <div class="cell-text">
                <span class="font-bold">وحدة #{{ slotProps.data.unit?.unit_number || '—' }}</span>
                <span class="sub-text">{{ slotProps.data.unit?.building?.name || 'مبنى غير محدد' }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="utility_type" header="نوع الخدمة" sortable>
          <template #body="slotProps">
            <span class="type-pill">
              {{ slotProps.data.utility_type === 'electricity' ? 'كهرباء' : 'ماء' }}
            </span>
          </template>
        </Column>

        <Column field="reading_date" header="تاريخ القراءة" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ slotProps.data.reading_date || '—' }}</span>
          </template>
        </Column>

        <Column field="previous_reading" header="القراءة السابقة">
          <template #body="slotProps">
            <span>{{ slotProps.data.previous_reading || 0 }}</span>
          </template>
        </Column>

        <Column field="current_reading" header="القراءة الحالية">
          <template #body="slotProps">
            <span class="font-bold">{{ slotProps.data.current_reading || 0 }}</span>
          </template>
        </Column>

        <Column field="consumption" header="معدل الاستهلاك" sortable>
          <template #body="slotProps">
            <span class="consumption-tag">{{ slotProps.data.consumption || 0 }} وحدة</span>
          </template>
        </Column>

        <Column field="total" header="إجمالي التكلفة" sortable>
          <template #body="slotProps">
            <span class="total-amount">{{ formatCurrency(slotProps.data.total) }}</span>
          </template>
        </Column>

        <!-- Empty State -->
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-bolt"></i>
            <p>لا توجد قراءات عدادات مسجلة تطابق البحث</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Dialog -->
    <Dialog
      v-model:visible="showDialog"
      header="تسجيل قراءة عداد جديدة"
      modal
      :style="{ width: '520px' }"
      class="saas-dialog"
    >
      <div class="dialog-body">
        <div class="form-field">
          <label>الوحدة العقارية <span class="required">*</span></label>
          <Select v-model="form.unit_id" :options="units" optionLabel="label" optionValue="id" placeholder="اختر الوحدة المستهدفة" class="w-full" />
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>نوع الخدمة</label>
            <Select v-model="form.utility_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-field flex-1">
            <label>تاريخ القراءة</label>
            <DatePicker v-model="form.reading_date" class="w-full" placeholder="اختر التاريخ" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field flex-1">
            <label>القراءة السابقة</label>
            <InputNumber v-model="form.previous_reading" class="w-full" :min="0" />
          </div>
          <div class="form-field flex-1">
            <label>القراءة الحالية <span class="required">*</span></label>
            <InputNumber v-model="form.current_reading" class="w-full" :min="0" />
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
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/services/api'

const dt = ref(null)
const items = ref([])
const units = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const errorMsg = ref('')
const toastMsg = ref('')
const searchQuery = ref('')

const filters = reactive({ utility_type: null })

const form = reactive({ unit_id: null, utility_type: 'electricity', reading_date: null, previous_reading: 0, current_reading: 0 })

const typeFilter = ref([{ label: 'كهرباء', value: 'electricity' }, { label: 'ماء', value: 'water' }])
const typeOptions = ref([{ label: 'كهرباء', value: 'electricity' }, { label: 'ماء', value: 'water' }])

const filteredItems = computed(() => {
  if (!searchQuery.value.trim()) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.unit?.unit_number?.toString().toLowerCase().includes(q) ||
    item.unit?.building?.name?.toLowerCase().includes(q)
  )
})

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
    errorMsg.value = ''
    const params = filters.utility_type ? { type: filters.utility_type } : {}
    const { data } = await api.get('/utility-readings', { params })
    items.value = data.data
  } catch (err) {
    errorMsg.value = 'خطأ في تحميل القراءات: ' + (err.response?.data?.message || err.message)
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

function closeDialog() {
  showDialog.value = false
  Object.assign(form, { unit_id: null, utility_type: 'electricity', reading_date: null, previous_reading: 0, current_reading: 0 })
}

async function saveItem() {
  if (!form.unit_id || form.current_reading === null) {
    errorMsg.value = 'يرجى اختيار الوحدة وإدخال القراءة الحالية'
    return
  }

  saving.value = true
  try {
    await api.post('/utility-readings', form)
    showToast('تم حفظ القراءة بنجاح')
    closeDialog()
    await fetchItems()
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'تعذر حفظ البيانات'
  } finally {
    saving.value = false
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
  width: 250px !important;
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

.utility-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icon-avatar {
  width: 36px;
  height: 36px;
  background: #FFFBEB;
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

.type-pill {
  background: #F1F5F9;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
}

.consumption-tag {
  background: #EFF6FF;
  color: var(--accent);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}

.total-amount {
  font-weight: 700;
  color: var(--text-primary);
}

.date-text {
  font-size: 13px;
  color: var(--text-secondary);
}

.form-row {
  display: flex;
  gap: 14px;
}

.required {
  color: var(--danger);
}
</style>
