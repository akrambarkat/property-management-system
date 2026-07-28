<template>
  <div class="reports-page">
    <div class="page-toolbar">
      <div class="toolbar-filters">
        <Select v-model="filters.building_id" :options="buildings" optionLabel="name" optionValue="id" placeholder="المبنى" showClear @change="fetchReport" />
        <DatePicker v-model="filters.from" placeholder="من تاريخ" @change="fetchReport" />
        <DatePicker v-model="filters.to" placeholder="إلى تاريخ" @change="fetchReport" />
      </div>
      <div class="toolbar-actions">
        <button class="btn-primary" @click="exportPDF"><i class="pi pi-file-pdf"></i> PDF</button>
        <button class="btn-primary" @click="exportExcel"><i class="pi pi-file-excel"></i> Excel</button>
      </div>
    </div>

    <div class="reports-grid">
      <Card>
        <template #title>تقرير الدخل</template>
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

      <Card>
        <template #title>تقرير المصروفات</template>
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

      <Card>
        <template #title>صافي الربح / الخسارة</template>
        <template #content>
          <div class="profit-loss">
            <div class="pl-item positive" v-if="reportData.net_profit >= 0">
              <i class="pi pi-arrow-up"></i>
              <div>
                <span>صافي الربح</span>
                <strong>{{ formatCurrency(reportData.net_profit) }}</strong>
              </div>
            </div>
            <div class="pl-item negative" v-else>
              <i class="pi pi-arrow-down"></i>
              <div>
                <span>صافي الخسارة</span>
                <strong>{{ formatCurrency(Math.abs(reportData.net_profit)) }}</strong>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Card>
      <template #title>تفاصيل الدخل</template>
      <template #content>
        <DataTable :value="reportData.details || []" stripedRows paginator :rows="15">
          <Column field="building" header="المبنى"></Column>
          <Column field="unit" header="الوحدة"></Column>
          <Column field="tenant" header="المستأجر"></Column>
          <Column field="rent" header="الإيجار"><template #body="s">{{ formatCurrency(s.data.rent) }}</template></Column>
          <Column field="utilities" header="المرافق"><template #body="s">{{ formatCurrency(s.data.utilities) }}</template></Column>
          <Column field="total" header="المجموع"><template #body="s">{{ formatCurrency(s.data.total) }}</template></Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const items = ref([])
const buildings = ref([])

const filters = reactive({ building_id: null, from: null, to: null })

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
  try {
    const params = {}
    if (filters.building_id) params.building_id = filters.building_id
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    const { data } = await api.get('/reports/profit-loss', { params })
    reportData.value = data.data
  } catch { /* */ }
}

function exportPDF() { window.open(api.defaults.baseURL + '/reports/profit-loss?export=pdf', '_blank') }
function exportExcel() { window.open(api.defaults.baseURL + '/reports/profit-loss?export=excel', '_blank') }
</script>

<style scoped>
.reports-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.report-summary { display: flex; flex-direction: column; gap: 12px; }

.summary-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 0; border-bottom: 1px solid var(--border);
}

.summary-item.highlight {
  border-bottom: none; padding-top: 12px; margin-top: 4px;
  border-top: 2px solid var(--primary);
}

.summary-item strong { font-size: 18px; color: var(--text-primary); }

.profit-loss { text-align: center; padding: 20px; }

.pl-item { display: flex; align-items: center; justify-content: center; gap: 16px; }
.pl-item.positive { color: var(--success); }
.pl-item.negative { color: var(--danger); }
.pl-item i { font-size: 2rem; }
.pl-item span { display: block; font-size: 14px; }
.pl-item strong { font-size: 28px; font-weight: 700; }

@media (max-width: 1024px) { .reports-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px) { .reports-grid { grid-template-columns: 1fr; } }
</style>
