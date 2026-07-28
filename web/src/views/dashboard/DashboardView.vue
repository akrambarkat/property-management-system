<template>
  <div class="dashboard">
    <div class="stats-grid">
      <div class="stat-card" v-for="stat in stats" :key="stat.label">
        <div class="stat-icon" :style="{ background: stat.bgColor }">
          <i :class="stat.icon" :style="{ color: stat.color }"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
        <div class="stat-trend" :class="stat.trend > 0 ? 'up' : 'down'" v-if="stat.trend">
          <i :class="stat.trend > 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"></i>
          {{ Math.abs(stat.trend) }}%
        </div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="dashboard-card">
        <div class="card-header">
          <h3>آخر المدفوعات</h3>
          <router-link to="/payments" class="view-all">عرض الكل</router-link>
        </div>
        <DataTable :value="recentPayments" size="small" stripedRows>
          <Column field="receipt_number" header="رقم الإيصال"></Column>
          <Column field="tenant" header="المستأجر"></Column>
          <Column field="amount" header="المبلغ">
            <template #body="slotProps">
              {{ format(slotProps.data.amount) }}
            </template>
          </Column>
          <Column field="payment_date" header="التاريخ"></Column>
        </DataTable>
        <div v-if="!recentPayments.length" class="empty-state">
          <i class="pi pi-inbox"></i>
          <p>لا توجد مدفوعات حديثة</p>
        </div>
      </div>

      <div class="dashboard-card">
        <div class="card-header">
          <h3>الفواتير المتأخرة</h3>
          <router-link to="/invoices" class="view-all">عرض الكل</router-link>
        </div>
        <DataTable :value="overdueInvoices" size="small" stripedRows>
          <Column field="invoice_number" header="رقم الفاتورة"></Column>
          <Column field="tenant" header="المستأجر"></Column>
          <Column field="total_amount" header="المبلغ">
            <template #body="slotProps">
              {{ format(slotProps.data.total_amount) }}
            </template>
          </Column>
          <Column field="due_date" header="تاريخ الاستحقاق"></Column>
          <Column header="الحالة">
            <template #body="slotProps">
              <span class="status-badge status-overdue">متأخرة</span>
            </template>
          </Column>
        </DataTable>
        <div v-if="!overdueInvoices.length" class="empty-state">
          <i class="pi pi-check-circle" style="color: var(--success);"></i>
          <p>لا توجد فواتير متأخرة</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { formatCurrency } from '@/utils/currency'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

function format(amount) {
  return formatCurrency(amount, appStore.selectedCurrency)
}

const stats = ref([
  { label: 'إجمالي الوحدات', value: '200', icon: 'pi pi-th-large', bgColor: '#DBEAFE', color: '#1B2A4A', trend: null },
  { label: 'الوحدات المشغولة', value: '156', icon: 'pi pi-check-circle', bgColor: '#D1FAE5', color: '#065F46', trend: 8 },
  { label: 'الإيراد الشهري', value: '45,000 ₪', icon: 'pi pi-money-bill', bgColor: '#FEF3C7', color: '#92400E', trend: 12 },
  { label: 'المتأخرات', value: '8,500 ₪', icon: 'pi pi-exclamation-triangle', bgColor: '#FEE2E2', color: '#991B1B', trend: -5 }
])

const recentPayments = ref([
  { receipt_number: 'REC-001', tenant: 'أحمد محمود', amount: 1500, payment_date: '2026-07-28' },
  { receipt_number: 'REC-002', tenant: 'محمود علي', amount: 2000, payment_date: '2026-07-27' },
  { receipt_number: 'REC-003', tenant: 'سامر حسن', amount: 1800, payment_date: '2026-07-26' }
])

const overdueInvoices = ref([
  { invoice_number: 'INV-045', tenant: 'خالد عمر', total_amount: 2500, due_date: '2026-07-01' },
  { invoice_number: 'INV-032', tenant: 'نادر سليم', total_amount: 3000, due_date: '2026-06-15' }
])


</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
}

.stat-card {
  background: white;
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon i {
  font-size: 1.4rem;
}

.stat-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.stat-trend {
  position: absolute;
  top: 12px;
  left: 12px;
  font-size: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}

.stat-trend.up { color: var(--success); }
.stat-trend.down { color: var(--danger); }

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.dashboard-card {
  background: white;
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.view-all {
  font-size: 14px;
  color: var(--primary);
  font-weight: 500;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-secondary);
}

.empty-state i {
  font-size: 2.5rem;
  margin-bottom: 12px;
}

@media (max-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
