<template>
  <div class="page-view profile-dashboard">
    <div v-if="loading" class="text-center py-8 text-muted">جاري التحميل...</div>
    <template v-else-if="tenant">
      <div class="profile-header">
        <div class="header-left">
          <button class="btn-icon" @click="router.back()">
            <i class="pi pi-arrow-right"></i>
          </button>
          <div class="profile-title-block">
            <div class="flex-align gap-3">
              <div class="tenant-avatar">
                <img v-if="tenant.id_photo_url" :src="tenant.id_photo_url" class="avatar-img" />
                <i v-else class="pi pi-user"></i>
              </div>
              <div>
                <h2 class="profile-title">{{ fullName }}</h2>
                <span class="profile-subtitle">
                  <i class="pi pi-id-card text-muted"></i>
                  الرقم المدني/سجل: {{ tenant.id_number || '—' }} | مستأجر منذ {{ monthsSinceJoined }} شهر
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <button class="btn-secondary" @click="router.push(`/tenants`)">
            <i class="pi pi-arrow-left"></i> العودة للقائمة
          </button>
        </div>
      </div>

      <div class="profile-body-grid">
        <div class="profile-col-left">
          <div class="dashboard-widget highlight-widget">
            <div class="widget-header">
              <h3>مركز التحصيل السريع</h3>
              <i class="pi pi-bolt text-warning text-xl"></i>
            </div>
            <div class="collection-center">
              <div class="outstanding-box">
                <span class="box-label">إجمالي المستحقات</span>
                <span class="box-value" :class="outstandingTotal > 0 ? 'text-danger' : 'text-success'">{{ format(outstandingTotal) }}</span>
              </div>
              <div class="collection-actions">
                <button class="btn-primary w-full justify-center" @click="router.push('/payments')">
                  <i class="pi pi-money-bill"></i> تسجيل دفعة جديدة
                </button>
              </div>
            </div>
          </div>

          <div class="dashboard-widget">
            <div class="widget-header">
              <h3>العقد الحالي</h3>
              <span v-if="activeContract" class="badge" :class="contractEndSoon ? 'badge-warning' : 'badge-success'">{{ contractEndSoon ? 'ينتهي قريباً' : 'نشط' }}</span>
            </div>
            <div v-if="activeContract" class="contract-card">
              <div class="contract-meta">
                <span class="c-label">رقم العقد</span>
                <span class="c-val">{{ activeContract.contract_number }}</span>
              </div>
              <div class="contract-meta">
                <span class="c-label">الوحدة</span>
                <span class="c-val">وحدة {{ activeContract.unit?.unit_number || '—' }} ({{ activeContract.unit?.building?.name || '—' }})</span>
              </div>
              <div class="contract-meta">
                <span class="c-label">قيمة الإيجار</span>
                <span class="c-val">{{ format(activeContract.rent_amount) }}/شهرياً</span>
              </div>
              <div class="contract-meta">
                <span class="c-label">تاريخ الانتهاء</span>
                <span class="c-val" :class="contractEndSoon ? 'text-danger font-bold' : ''">{{ activeContract.end_date }}</span>
              </div>
            </div>
            <div v-else class="text-muted" style="padding: 16px; text-align: center;">لا يوجد عقد نشط حالياً</div>
          </div>

          <div class="dashboard-widget">
            <div class="widget-header">
              <h3>معلومات التواصل</h3>
            </div>
            <div class="contact-list">
              <div v-if="tenant.phone" class="contact-item">
                <i class="pi pi-phone text-muted"></i>
                <span dir="ltr" style="text-align: right;">{{ tenant.phone }}</span>
              </div>
              <div v-if="tenant.email" class="contact-item">
                <i class="pi pi-envelope text-muted"></i>
                <span>{{ tenant.email }}</span>
              </div>
              <div v-if="!tenant.phone && !tenant.email" class="contact-item text-muted">
                <span>لا توجد معلومات تواصل</span>
              </div>
            </div>
          </div>
        </div>

        <div class="profile-col-right">
          <div class="dashboard-widget">
            <div class="widget-header">
              <h3>طلبات الصيانة</h3>
              <span v-if="maintenanceItems.length" class="text-xs text-muted">{{ maintenanceItems.length }} طلب</span>
            </div>
            <div class="widget-list">
              <div v-if="maintenanceItems.length === 0" class="text-muted" style="padding: 16px; text-align: center;">لا توجد طلبات صيانة</div>
              <div v-for="m in maintenanceItems" :key="m.id" class="widget-list-item">
                <div class="item-icon" :class="m.status === 'completed' ? 'bg-info-light' : 'bg-warning-light'">
                  <i :class="m.status === 'completed' ? 'pi pi-check-circle text-info' : 'pi pi-cog text-warning'"></i>
                </div>
                <div class="item-info">
                  <span class="item-title">{{ m.description }}</span>
                  <span class="item-sub">{{ maintStatusText(m.status) }}{{ m.cost ? ` | التكلفة: ${format(m.cost)}` : '' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-widget mt-4">
            <div class="widget-header">
              <h3>سجل الدفعات الأخير</h3>
              <router-link v-if="recentPayments.length" to="/payments" class="text-xs text-accent font-bold">عرض الكل</router-link>
            </div>
            <table class="simple-table">
              <thead>
                <tr>
                  <th>رقم الإيصال</th>
                  <th>التاريخ</th>
                  <th>المبلغ</th>
                  <th>طريقة الدفع</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="recentPayments.length === 0">
                  <td colspan="4" class="text-muted text-center">لا توجد دفعات مسجلة</td>
                </tr>
                <tr v-for="p in recentPayments" :key="p.id">
                  <td>{{ p.receipt_number || '—' }}</td>
                  <td>{{ p.payment_date }}</td>
                  <td class="font-bold">{{ format(p.amount) }}</td>
                  <td>{{ paymentMethodLabel(p.payment_method) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="dashboard-widget mt-4">
            <div class="widget-header">
              <h3>آخر الفواتير</h3>
            </div>
            <table class="simple-table">
              <thead>
                <tr>
                  <th>رقم الفاتورة</th>
                  <th>التاريخ</th>
                  <th>المبلغ</th>
                  <th>المتبقي</th>
                  <th>الحالة</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="recentInvoices.length === 0">
                  <td colspan="5" class="text-muted text-center">لا توجد فواتير</td>
                </tr>
                <tr v-for="inv in recentInvoices" :key="inv.id">
                  <td>{{ inv.invoice_number }}</td>
                  <td>{{ inv.issue_date }}</td>
                  <td>{{ format(inv.total_amount) }}</td>
                  <td class="font-bold" :class="inv.balance > 0 ? 'text-danger' : 'text-success'">{{ format(inv.balance) }}</td>
                  <td><span class="status-badge" :class="inv.status === 'paid' ? 'status-active' : 'status-expired'">{{ inv.status === 'paid' ? 'مدفوع' : inv.status === 'partial' ? 'جزئي' : 'غير مدفوع' }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
    <div v-else class="text-center py-8 text-danger">فشل تحميل بيانات المستأجر</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { formatCurrency } from '@/utils/currency'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const route = useRoute()
const appStore = useAppStore()

const tenant = ref(null)
const maintenanceItems = ref([])
const loading = ref(true)

function format(val) {
  return formatCurrency(val || 0, appStore.selectedCurrency)
}

const fullName = computed(() => {
  if (!tenant.value) return '—'
  return `${tenant.value.first_name} ${tenant.value.last_name}`
})

const allContracts = computed(() => tenant.value?.contracts || [])
const activeContract = computed(() => allContracts.value.find(c => c.status === 'active'))

const monthsSinceJoined = computed(() => {
  if (!allContracts.value.length) return '—'
  const dates = allContracts.value.map(c => c.start_date ? new Date(c.start_date) : null).filter(Boolean)
  if (!dates.length) return '—'
  const earliest = new Date(Math.min(...dates))
  const now = new Date()
  return Math.max(1, (now.getFullYear() - earliest.getFullYear()) * 12 + now.getMonth() - earliest.getMonth())
})

const contractEndSoon = computed(() => {
  if (!activeContract.value?.end_date) return false
  const end = new Date(activeContract.value.end_date)
  const now = new Date()
  const diff = (end - now) / (1000 * 60 * 60 * 24)
  return diff >= 0 && diff <= 60
})

const allInvoices = computed(() => {
  if (!allContracts.value.length) return []
  return allContracts.value.flatMap(c => c.invoices || []).sort((a, b) => new Date(b.issue_date) - new Date(a.issue_date))
})

const outstandingTotal = computed(() => {
  return allInvoices.value.reduce((sum, inv) => sum + (inv.total_amount - inv.paid_amount), 0)
})

const recentPayments = computed(() => {
  const payments = allInvoices.value.flatMap(inv => (inv.payments || []).map(p => ({ ...p, invoice_number: inv.invoice_number })))
  payments.sort((a, b) => new Date(b.payment_date || b.created_at) - new Date(a.payment_date || a.created_at))
  return payments.slice(0, 10)
})

const recentInvoices = computed(() => {
  return allInvoices.value.slice(0, 10)
})

const paymentMethodLabel = (method) => {
  const labels = { bank_transfer: 'تحويل بنكي', cash: 'نقدي', check: 'شيك', credit_card: 'بطاقة ائتمان' }
  return labels[method] || method || '—'
}

const maintStatusText = (status) => {
  const labels = { pending: 'معلق', in_progress: 'قيد التنفيذ', completed: 'مكتمل', cancelled: 'ملغي' }
  return labels[status] || status || '—'
}

onMounted(async () => {
  try {
    const tenantId = route.params.id
    const [tenantRes] = await Promise.all([
      api.get(`/tenants/${tenantId}`),
    ])
    tenant.value = tenantRes.data?.data || null

    const active = tenant.value?.contracts?.find(c => c.status === 'active')
    if (active?.unit?.id) {
      const maintRes = await api.get('/maintenance', { params: { unit_id: active.unit.id } })
      maintenanceItems.value = maintRes.data?.data || []
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.profile-dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.profile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}
.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.tenant-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #EFF6FF;
  color: #3B82F6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  border: 2px solid #BFDBFE;
  overflow: hidden;
}
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.flex-align { display: flex; align-items: center; }
.gap-3 { gap: 12px; }
.profile-title {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}
.profile-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
}
.header-actions {
  display: flex;
  gap: 12px;
}
.profile-body-grid {
  display: grid;
  grid-template-columns: 1fr 2.2fr;
  gap: 20px;
}
@media (max-width: 1024px) {
  .profile-body-grid {
    grid-template-columns: 1fr;
  }
}
.dashboard-widget {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  margin-bottom: 16px;
}
.highlight-widget {
  background: linear-gradient(180deg, #FFFFFF 0%, #FEF9C3 100%);
  border-color: #FDE047;
}
.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.widget-header h3 {
  font-size: 14.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}
.badge-warning { background: #FEF3C7; color: #D97706; }
.badge-success { background: #DCFCE7; color: #16A34A; }
.collection-center {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.outstanding-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px;
  background: #FEF2F2;
  border-radius: var(--radius-sm);
  border: 1px solid #FECACA;
}
.box-label { font-size: 12.5px; color: #991B1B; font-weight: 600; }
.box-value { font-size: 28px; font-weight: 900; margin-top: 4px; }
.text-danger { color: #DC2626; }
.text-success { color: #16A34A; }
.text-warning { color: #D97706; }
.collection-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.w-full { width: 100%; }
.justify-center { justify-content: center; }
.contract-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #F8FAFC;
  padding: 14px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-light);
}
.contract-meta {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}
.c-label { color: var(--text-secondary); }
.c-val { font-weight: 600; color: var(--text-primary); }
.font-bold { font-weight: 700; }
.mt-4 { margin-top: 16px; }
.contact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.contact-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: var(--text-primary);
}
.simple-table {
  width: 100%;
  border-collapse: collapse;
}
.simple-table th {
  text-align: right;
  padding: 10px 12px;
  border-bottom: 1px solid var(--border);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
}
.simple-table td {
  padding: 12px;
  border-bottom: 1px solid var(--border-light);
  font-size: 13.5px;
}
.status-badge {
  padding: 3px 8px;
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 600;
}
.status-active { background: #ECFDF5; color: #059669; }
.status-expired { background: #FEF2F2; color: #DC2626; }
.text-center { text-align: center; }
.text-muted { color: var(--text-secondary); }
.text-xs { font-size: 12px; }
.text-accent { color: var(--accent); }
.py-8 { padding-top: 32px; padding-bottom: 32px; }
.widget-list {
  display: flex;
  flex-direction: column;
}
.widget-list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border-light);
}
.widget-list-item:last-child { border-bottom: none; }
.item-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.bg-info-light { background: #E0F2FE; }
.text-info { color: #0284C7; }
.bg-warning-light { background: #FEF3C7; }
.item-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.item-title { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
.item-sub { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
</style>
