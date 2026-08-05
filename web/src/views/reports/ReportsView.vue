<template>
  <div class="reports-page">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="المبنى" showClear @change="fetchReport" class="filter-select" />
        <DatePicker v-model="filters.from" placeholder="من تاريخ" @change="fetchReport" class="filter-datepicker" />
        <DatePicker v-model="filters.to" placeholder="إلى تاريخ" @change="fetchReport" class="filter-datepicker" />
      </div>
      <div class="toolbar-actions">
        <button class="btn-secondary" @click="exportPDF"><i class="pi pi-file-pdf"></i> تصدير PDF</button>
        <button class="btn-primary" @click="exportExcel"><i class="pi pi-file-excel"></i> تصدير Excel</button>
      </div>
    </div>

    <div class="reports-grid">
      <Card class="saas-report-card">
        <template #title><span class="card-header-title"><i class="pi pi-chart-line text-blue"></i> تقرير الدخل</span></template>
        <template #content>
          <div class="report-summary">
            <div class="summary-item">
              <span>إجمالي الإيجارات</span>
              <strong>{{ formatCurrency(reportData.total_rent) }}</strong>
            </div>
            <div class="summary-item">
              <span>إجمالي المرافق</span>
              <strong>{{ formatCurrency(reportData.total_utilities) }}</strong>
            </div>
            <div class="summary-item highlight">
              <span>إجمالي الدخل</span>
              <strong>{{ formatCurrency(reportData.total_income) }}</strong>
            </div>
          </div>
        </template>
      </Card>

      <Card class="saas-report-card">
        <template #title><span class="card-header-title"><i class="pi pi-receipt text-red"></i> تقرير المصروفات</span></template>
        <template #content>
          <div class="report-summary">
            <div class="summary-item" v-for="cat in expenseCategories" :key="cat.key">
              <span>{{ cat.label }}</span>
              <strong>{{ formatCurrency(reportData.expenses_by_category?.[cat.key] || 0) }}</strong>
            </div>
            <div class="summary-item highlight">
              <span>إجمالي المصروفات</span>
              <strong>{{ formatCurrency(reportData.total_expenses) }}</strong>
            </div>
          </div>
        </template>
      </Card>

      <Card class="saas-report-card">
        <template #title><span class="card-header-title"><i class="pi pi-wallet text-green"></i> صافي الربح / الخسارة</span></template>
        <template #content>
          <div class="profit-loss">
            <div class="pl-item positive" v-if="reportData.net_profit >= 0">
              <div class="pl-icon-bg success-bg">
                <i class="pi pi-arrow-up"></i>
              </div>
              <div>
                <span class="pl-label">صافي الربح</span>
                <strong>{{ formatCurrency(reportData.net_profit) }}</strong>
              </div>
            </div>
            <div class="pl-item negative" v-else>
              <div class="pl-icon-bg danger-bg">
                <i class="pi pi-arrow-down"></i>
              </div>
              <div>
                <span class="pl-label">صافي الخسارة</span>
                <strong>{{ formatCurrency(Math.abs(reportData.net_profit)) }}</strong>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Enterprise SaaS Card Table Layout for Details -->
    <EnterpriseTable
      :value="reportData.details || []"
      :loading="loading"
      searchPlaceholder="بحث في تفاصيل الدخل..."
      emptyTitle="لا توجد تفاصيل متاحة"
      emptySubtitle="لم يتم العثور على أي تفاصيل دخل ضمن الفترة المحددة"
      :columns="tableColumns"
      @refresh="fetchReport"
    >
      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('building')" field="building" header="المبنى" sortable></Column>
        <Column v-if="!hiddenColumns.includes('unit')" field="unit" header="الوحدة" sortable></Column>
        <Column v-if="!hiddenColumns.includes('tenant')" field="tenant" header="المستأجر" sortable></Column>
        <Column v-if="!hiddenColumns.includes('rent')" field="rent" header="الإيجار" sortable>
          <template #body="s"><span class="amount-val">{{ formatCurrency(s.data.rent) }}</span></template>
        </Column>
        <Column v-if="!hiddenColumns.includes('utilities')" field="utilities" header="المرافق" sortable>
          <template #body="s"><span class="amount-val">{{ formatCurrency(s.data.utilities) }}</span></template>
        </Column>
        <Column v-if="!hiddenColumns.includes('total')" field="total" header="المجموع" sortable>
          <template #body="s"><span class="font-bold text-success">{{ formatCurrency(s.data.total) }}</span></template>
        </Column>
      </template>
    </EnterpriseTable>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import { useAppStore } from '@/stores/app'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const appStore = useAppStore()
const buildings = ref([])
const loading = ref(false)
const toast = useToastStore()

const filters = reactive({ building_id: null, from: null, to: null })

const tableColumns = [
  { field: 'building', header: 'المبنى' },
  { field: 'unit', header: 'الوحدة' },
  { field: 'tenant', header: 'المستأجر' },
  { field: 'rent', header: 'الإيجار' },
  { field: 'utilities', header: 'المرافق' },
  { field: 'total', header: 'المجموع' }
]

const reportData = ref({
  total_rent: 0, total_utilities: 0, total_income: 0,
  expenses_by_category: {}, total_expenses: 0, net_profit: 0,
  details: []
})

const expenseCategories = ref([
  { key: 'maintenance', label: 'صيانة' }, { key: 'plumbing', label: 'سباكة' },
  { key: 'electrical', label: 'كهرباء' }, { key: 'cleaning', label: 'نظافة' },
  { key: 'security', label: 'أمن' }, { key: 'general', label: 'عام' }
])

function formatCurrency(amount) { return `${Number(amount || 0).toLocaleString('ar-SA')} ${appStore.currentCurrencySymbol}` }

onMounted(() => { fetchBuildings(); fetchReport() })

async function fetchBuildings() {
  try { const { data } = await api.get('/buildings'); buildings.value = data.data } catch {}
}

async function fetchReport() {
  loading.value = true
  try {
    const params = {}
    if (filters.building_id) params.building_id = filters.building_id
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    const { data } = await api.get('/reports/profit-loss', { params })
    reportData.value = data.data
  } catch { /* */ } finally {
    loading.value = false
  }
}

async function buildParams() {
  const params = {}
  if (filters.building_id) params.building_id = filters.building_id
  if (filters.from) params.from = filters.from
  if (filters.to) params.to = filters.to
  return params
}

async function download(exportType) {
  const params = await buildParams()
  params.export = exportType
  const { data } = await api.get('/reports/profit-loss', { params, responseType: 'blob' })
  const ext = exportType === 'pdf' ? 'pdf' : 'csv'
  const blob = new Blob([data])
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `profit_loss_${new Date().toISOString().slice(0, 10)}.${ext}`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

function exportPDF() {
  try { download('pdf') } catch {}
}
function exportExcel() {
  try { download('excel') } catch {}
}
</script>

<style scoped>
.reports-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.saas-report-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}

.card-header-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  font-weight: 700;
}

.report-summary { display: flex; flex-direction: column; gap: 12px; }

.summary-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 0; border-bottom: 1px solid var(--border);
  font-size: 13.5px;
}

.summary-item.highlight {
  border-bottom: none; padding-top: 12px; margin-top: 4px;
  border-top: 2px solid var(--accent);
}

.summary-item strong { font-size: 16px; color: var(--text-primary); }

.profit-loss { padding: 10px; }

.pl-item { display: flex; align-items: center; gap: 16px; }
.pl-icon-bg {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}
.success-bg { background: var(--success-bg); color: var(--success-contrast); }
.danger-bg { background: var(--danger-bg); color: var(--danger-contrast); }

.pl-label { display: block; font-size: 13px; color: var(--text-secondary); }
.pl-item strong { font-size: 24px; font-weight: 700; }
.positive strong { color: var(--success); }
.negative strong { color: var(--danger); }

.amount-val { font-size: 13.5px; color: var(--text-secondary); }
.font-bold { font-weight: 700; }
.text-success { color: var(--success); }

.filter-select { width: 180px !important; }
.filter-datepicker { width: 150px !important; }

.text-blue { color: var(--info-contrast); }
.text-red { color: var(--danger); }
.text-green { color: var(--success); }

@media (max-width: 1024px) { .reports-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px) { .reports-grid { grid-template-columns: 1fr; } }
</style>
