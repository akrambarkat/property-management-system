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
          <button class="btn-secondary" @click="downloadStatement">
            <i class="pi pi-download"></i> تصدير كشف الحساب
          </button>
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
                <button class="btn-primary w-full justify-center" @click="openContractDialog">
                  <i class="pi pi-file"></i> إضافة عقد جديد
                </button>
                <button class="btn-primary w-full justify-center" @click="openInvoiceDialog">
                  <i class="pi pi-file-plus"></i> إضافة فاتورة
                </button>
                <button class="btn-primary w-full justify-center" @click="openPaymentDialog">
                  <i class="pi pi-money-bill"></i> تسجيل دفعة
                </button>
              </div>
            </div>
          </div>

          <div class="dashboard-widget">
            <div class="widget-header">
              <h3>العقود النشطة</h3>
              <span v-if="activeContracts.length" class="badge" :class="activeContracts.length > 1 ? 'badge-warning' : 'badge-success'">{{ activeContracts.length > 1 ? `${activeContracts.length} عقود` : (contractEndSoon(activeContracts[0]) ? 'ينتهي قريباً' : 'نشط') }}</span>
            </div>
            <div v-for="(contract, idx) in activeContracts" :key="contract.id" class="contract-card" :class="{ 'mt-4': idx > 0 }">
              <div class="contract-meta">
                <span class="c-label">رقم العقد</span>
                <span class="c-val">{{ contract.contract_number }}</span>
              </div>
              <div class="contract-meta">
                <span class="c-label">الوحدة</span>
                <span class="c-val">وحدة {{ contract.unit?.unit_number || '—' }} ({{ contract.unit?.building?.name || '—' }})</span>
              </div>
              <div class="contract-meta">
                <span class="c-label">قيمة الإيجار</span>
                <span class="c-val">{{ format(contract.rent_amount) }}/شهرياً</span>
              </div>
              <div class="contract-meta">
                <span class="c-label">تاريخ الانتهاء</span>
                <span class="c-val" :class="contractEndSoon(contract) ? 'text-danger font-bold' : ''">{{ contract.end_date }}</span>
              </div>
            </div>
            <div v-if="activeContracts.length === 0" class="text-muted" style="padding: 16px; text-align: center;">لا يوجد عقد نشط حالياً</div>
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
                  <td data-label="رقم الإيصال">{{ p.receipt_number || '—' }}</td>
                  <td data-label="التاريخ">{{ p.payment_date }}</td>
                  <td data-label="المبلغ" class="font-bold">{{ format(p.amount) }}</td>
                  <td data-label="طريقة الدفع">{{ paymentMethodLabel(p.payment_method) }}</td>
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
                  <td data-label="رقم الفاتورة">{{ inv.invoice_number }}</td>
                  <td data-label="التاريخ">{{ inv.issue_date }}</td>
                  <td data-label="المبلغ">{{ format(inv.total_amount) }}</td>
                  <td data-label="المتبقي" class="font-bold" :class="inv.balance > 0 ? 'text-danger' : 'text-success'">{{ format(inv.balance) }}</td>
                  <td data-label="الحالة"><span class="status-badge" :class="inv.status === 'paid' ? 'status-active' : 'status-expired'">{{ inv.status === 'paid' ? 'مدفوع' : inv.status === 'partial' ? 'جزئي' : 'غير مدفوع' }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- إضافة فاتورة -->
      <Dialog v-model:visible="showInvoiceDialog" header="إضافة فاتورة جديدة" modal :style="{ width: '580px' }" :closable="true">
        <div class="dialog-body">
          <div class="form-section">
            <div class="form-section-title">
              <i class="pi pi-file"></i>
              <span>العقد والتواريخ</span>
            </div>
            <FormField label="العقد" required forId="pf-inv-contract" :errorMessage="invoiceErrors.contract_id">
              <Select
                id="pf-inv-contract"
                v-model="invoiceForm.contract_id"
                :options="contractOptions"
                optionLabel="label"
                optionValue="id"
                placeholder="اختر العقد"
                class="w-full"
                filter
                @change="onInvoiceContractChange"
              />
            </FormField>
            <div class="form-grid-2">
              <FormField label="تاريخ الإصدار" forId="pf-inv-issue">
                <DatePicker id="pf-inv-issue" v-model="invoiceForm.issue_date" class="w-full" placeholder="اختر التاريخ" />
              </FormField>
              <FormField label="تاريخ الاستحقاق" forId="pf-inv-due">
                <DatePicker id="pf-inv-due" v-model="invoiceForm.due_date" class="w-full" placeholder="اختر التاريخ" />
              </FormField>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title">
              <i class="pi pi-calculator"></i>
              <span>المبالغ المستحقة</span>
            </div>
            <div class="form-grid-2">
              <FormField label="قيمة الإيجار (₪)" forId="pf-inv-rent">
                <InputNumber id="pf-inv-rent" v-model="invoiceForm.rent_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="الكهرباء (₪)" forId="pf-inv-elec">
                <InputNumber id="pf-inv-elec" v-model="invoiceForm.electricity_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="المياه (₪)" forId="pf-inv-water">
                <InputNumber id="pf-inv-water" v-model="invoiceForm.water_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="الإنترنت (₪)" forId="pf-inv-net">
                <InputNumber id="pf-inv-net" v-model="invoiceForm.internet_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="خدمات أخرى (₪)" forId="pf-inv-srv">
                <InputNumber id="pf-inv-srv" v-model="invoiceForm.services_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
            </div>
            <FormField label="ملاحظات" forId="pf-inv-notes">
              <Textarea id="pf-inv-notes" v-model="invoiceForm.notes" class="w-full" rows="2" placeholder="اختياري" />
            </FormField>
          </div>

          <div class="form-actions">
            <button class="btn-secondary" @click="showInvoiceDialog = false">إلغاء</button>
            <button class="btn-primary" @click="saveInvoice" :disabled="savingInvoice">
              <i v-if="savingInvoice" class="pi pi-spin pi-spinner"></i>
              <span>{{ savingInvoice ? 'جاري الحفظ...' : 'حفظ الفاتورة' }}</span>
            </button>
          </div>
        </div>
      </Dialog>

      <!-- تسجيل دفعة -->
      <Dialog v-model:visible="showPaymentDialog" header="تسجيل دفعة جديدة" modal :style="{ width: '540px' }" :closable="true">
        <div class="dialog-body">
          <div class="form-section">
            <div class="form-section-title">
              <i class="pi pi-money-bill"></i>
              <span>تفاصيل الدفعة</span>
            </div>
            <FormField label="الفاتورة" required forId="pf-pay-inv" :errorMessage="paymentErrors.invoice_id">
              <Select
                id="pf-pay-inv"
                v-model="paymentForm.invoice_id"
                :options="payableInvoiceOptions"
                optionLabel="label"
                optionValue="id"
                placeholder="اختر الفاتورة"
                class="w-full"
                filter
                @change="onPaymentInvoiceChange"
              />
            </FormField>
            <div class="form-grid-2">
              <FormField label="المبلغ (₪)" required forId="pf-pay-amount" :errorMessage="paymentErrors.amount">
                <InputNumber id="pf-pay-amount" v-model="paymentForm.amount" class="w-full" :min="0.01" placeholder="0" />
              </FormField>
              <FormField label="طريقة الدفع" required forId="pf-pay-method">
                <Select id="pf-pay-method" v-model="paymentForm.payment_method" :options="paymentMethodOptions" optionLabel="label" optionValue="value" placeholder="اختر الطريقة" class="w-full" />
              </FormField>
              <FormField label="تاريخ الدفع" required forId="pf-pay-date" :errorMessage="paymentErrors.payment_date">
                <DatePicker id="pf-pay-date" v-model="paymentForm.payment_date" class="w-full" placeholder="اختر التاريخ" />
              </FormField>
              <FormField label="رقم مرجعي" forId="pf-pay-ref">
                <InputText id="pf-pay-ref" v-model="paymentForm.reference_number" class="w-full" placeholder="اختياري" />
              </FormField>
            </div>
            <FormField label="ملاحظات" forId="pf-pay-notes">
              <Textarea id="pf-pay-notes" v-model="paymentForm.notes" class="w-full" rows="2" placeholder="اختياري" />
            </FormField>
          </div>

          <div class="form-actions">
            <button class="btn-secondary" @click="showPaymentDialog = false">إلغاء</button>
            <button class="btn-primary" @click="savePayment" :disabled="savingPayment">
              <i v-if="savingPayment" class="pi pi-spin pi-spinner"></i>
              <span>{{ savingPayment ? 'جاري الحفظ...' : 'حفظ الدفعة' }}</span>
            </button>
          </div>
        </div>
      </Dialog>

      <!-- إضافة عقد جديد -->
      <Dialog v-model:visible="showContractDialog" header="إضافة عقد جديد" modal :style="{ width: '640px' }" :closable="true">
        <div class="dialog-body">
          <div class="form-section">
            <div class="form-section-title">
              <i class="pi pi-users"></i>
              <span>المستأجر والوحدة</span>
            </div>
            <div class="form-grid-3">
              <FormField label="المستأجر" required forId="pf-cnt-tenant">
                <InputText id="pf-cnt-tenant" :modelValue="fullName" class="w-full" disabled />
              </FormField>
              <FormField label="الموقع" required forId="pf-cnt-location">
                <Select
                  id="pf-cnt-location"
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
              <FormField label="العمارة" required forId="pf-cnt-building">
                <Select
                  id="pf-cnt-building"
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
              <FormField label="الوحدة العقارية" required forId="pf-cnt-unit" :errorMessage="contractErrors.unit_id" helpText="اختر الوحدة المتاحة للتأجير">
                <Select
                  id="pf-cnt-unit"
                  v-model="contractForm.unit_id"
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
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title">
              <i class="pi pi-calendar"></i>
              <span>المدة والبنود المالية</span>
            </div>
            <div class="form-grid-2">
              <FormField label="تاريخ بداية العقد" required forId="pf-cnt-start" :errorMessage="contractErrors.start_date">
                <DatePicker id="pf-cnt-start" v-model="contractForm.start_date" class="w-full" placeholder="اختر التاريخ" @change="clearContractError('start_date')" />
              </FormField>
              <FormField label="تاريخ نهاية العقد" required forId="pf-cnt-end" :errorMessage="contractErrors.end_date">
                <DatePicker id="pf-cnt-end" v-model="contractForm.end_date" class="w-full" placeholder="اختر التاريخ" @change="clearContractError('end_date')" />
              </FormField>
              <FormField label="قيمة الإيجار (₪)" required forId="pf-cnt-rent" :errorMessage="contractErrors.rent_amount">
                <InputNumber id="pf-cnt-rent" v-model="contractForm.rent_amount" class="w-full" :min="0" placeholder="أدخل قيمة الإيجار" @input="clearContractError('rent_amount')" />
              </FormField>
              <FormField label="دورية السداد" forId="pf-cnt-type">
                <Select id="pf-cnt-type" v-model="contractForm.contract_type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
              </FormField>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title">
              <i class="pi pi-bolt"></i>
              <span>رسوم الخدمات (فاتورة خدمات منفصلة)</span>
            </div>
            <div class="form-grid-2">
              <FormField label="الكهرباء (₪)" forId="pf-cnt-elec">
                <InputNumber id="pf-cnt-elec" v-model="contractForm.electricity_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="المياه (₪)" forId="pf-cnt-water">
                <InputNumber id="pf-cnt-water" v-model="contractForm.water_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="الإنترنت (₪)" forId="pf-cnt-net">
                <InputNumber id="pf-cnt-net" v-model="contractForm.internet_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
              <FormField label="خدمات أخرى (₪)" forId="pf-cnt-srv">
                <InputNumber id="pf-cnt-srv" v-model="contractForm.services_amount" class="w-full" :min="0" placeholder="0" />
              </FormField>
            </div>
            <FormField label="ملاحظات" forId="pf-cnt-notes">
              <Textarea id="pf-cnt-notes" v-model="contractForm.notes" class="w-full" rows="2" placeholder="اختياري" />
            </FormField>
          </div>

          <div class="form-actions">
            <button class="btn-secondary" @click="showContractDialog = false">إلغاء</button>
            <button class="btn-primary" @click="saveContract" :disabled="savingContract">
              <i v-if="savingContract" class="pi pi-spin pi-spinner"></i>
              <span>{{ savingContract ? 'جاري الحفظ...' : 'حفظ العقد' }}</span>
            </button>
          </div>
        </div>
      </Dialog>
    </template>
    <div v-else class="text-center py-8 text-danger">فشل تحميل بيانات المستأجر</div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import FormField from '@/components/common/FormField.vue'
import { formatCurrency } from '@/utils/currency'
import { useAppStore } from '@/stores/app'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const route = useRoute()
const appStore = useAppStore()
const toast = useToastStore()

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
const activeContracts = computed(() => allContracts.value.filter(c => c.status === 'active'))

const monthsSinceJoined = computed(() => {
  if (!allContracts.value.length) return '—'
  const dates = allContracts.value.map(c => c.start_date ? new Date(c.start_date) : null).filter(Boolean)
  if (!dates.length) return '—'
  const earliest = new Date(Math.min(...dates))
  const now = new Date()
  return Math.max(1, (now.getFullYear() - earliest.getFullYear()) * 12 + now.getMonth() - earliest.getMonth())
})

function contractEndSoon(contract) {
  if (!contract?.end_date) return false
  const end = new Date(contract.end_date)
  const now = new Date()
  const diff = (end - now) / (1000 * 60 * 60 * 24)
  return diff >= 0 && diff <= 60
}

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

const showInvoiceDialog = ref(false)
const showPaymentDialog = ref(false)
const savingInvoice = ref(false)
const savingPayment = ref(false)

const invoiceForm = reactive({
  contract_id: null, issue_date: null, due_date: null,
  rent_amount: 0, electricity_amount: 0, water_amount: 0,
  internet_amount: 0, services_amount: 0, notes: ''
})
const invoiceErrors = reactive({ contract_id: '' })

const paymentForm = reactive({
  invoice_id: null, amount: null, payment_date: null,
  payment_method: null, reference_number: null, notes: ''
})
const paymentErrors = reactive({ invoice_id: '', amount: '', payment_date: '' })

const contractOptions = computed(() =>
  allContracts.value.map(c => ({
    ...c,
    label: `${c.contract_number} - ${c.unit?.building?.name || ''} (وحدة #${c.unit?.unit_number || ''})`
  }))
)

const payableInvoices = computed(() =>
  allInvoices.value.filter(inv => (inv.total_amount - inv.paid_amount) > 0)
)

const payableInvoiceOptions = computed(() =>
  payableInvoices.value.map(inv => ({
    ...inv,
    label: `${inv.invoice_number} - المتبقي ${format(inv.total_amount - inv.paid_amount)}`
  }))
)

const paymentMethodOptions = [
  { label: 'نقدي', value: 'cash' },
  { label: 'تحويل بنكي', value: 'bank_transfer' },
  { label: 'شيك', value: 'check' },
  { label: 'بطاقة ائتمان', value: 'credit_card' }
]

const showContractDialog = ref(false)
const savingContract = ref(false)
const locations = ref([])
const buildings = ref([])
const filteredUnits = ref([])
const selectedLocation = ref(null)
const selectedBuilding = ref(null)

const contractForm = reactive({
  unit_id: null, start_date: null, end_date: null, rent_amount: null,
  contract_type: 'monthly', electricity_amount: 0, water_amount: 0,
  internet_amount: 0, services_amount: 0, notes: ''
})
const contractErrors = reactive({ unit_id: '', start_date: '', end_date: '', rent_amount: '' })

const typeOptions = [
  { label: 'شهري', value: 'monthly' },
  { label: 'سنوي', value: 'annual' }
]

function clearContractError(field) {
  if (contractErrors[field]) contractErrors[field] = ''
}

async function openContractDialog() {
  Object.assign(contractForm, {
    unit_id: null, start_date: new Date(), end_date: null, rent_amount: null,
    contract_type: 'monthly', electricity_amount: 0, water_amount: 0,
    internet_amount: 0, services_amount: 0, notes: ''
  })
  Object.keys(contractErrors).forEach(k => contractErrors[k] = '')
  selectedLocation.value = null
  selectedBuilding.value = null
  buildings.value = []
  filteredUnits.value = []
  try {
    const { data } = await api.get('/locations')
    locations.value = data.data || []
  } catch (err) { console.error(err) }
  showContractDialog.value = true
}

async function onLocationChange() {
  selectedBuilding.value = null
  contractForm.unit_id = null
  buildings.value = []
  filteredUnits.value = []
  if (!selectedLocation.value) return
  try {
    const { data } = await api.get('/buildings', { params: { location_id: selectedLocation.value } })
    buildings.value = data.data || []
  } catch (err) { console.error(err) }
}

async function onBuildingChange() {
  contractForm.unit_id = null
  filteredUnits.value = []
  if (!selectedBuilding.value) return
  try {
    const { data } = await api.get('/units', { params: { building_id: selectedBuilding.value, status: 'available' } })
    filteredUnits.value = (data.data || []).map(u => ({
      ...u,
      label: `وحدة #${u.unit_number} - ${u.unit_type === 'apartment' ? 'شقة' : u.unit_type === 'shop' ? 'محل' : 'مستودع'}`
    }))
  } catch (err) { console.error(err) }
}

function onUnitChange() {
  clearContractError('unit_id')
  if (!contractForm.unit_id) return
  const unit = filteredUnits.value.find(u => u.id === contractForm.unit_id)
  if (!unit) return
  if (unit.rent_amount) contractForm.rent_amount = Number(unit.rent_amount)
  if (unit.electricity_amount) contractForm.electricity_amount = Number(unit.electricity_amount)
  if (unit.water_amount) contractForm.water_amount = Number(unit.water_amount)
  if (unit.internet_amount) contractForm.internet_amount = Number(unit.internet_amount)
  if (unit.services_amount) contractForm.services_amount = Number(unit.services_amount)
}

async function saveContract() {
  let isValid = true
  Object.keys(contractErrors).forEach(k => contractErrors[k] = '')
  if (!contractForm.unit_id) { contractErrors.unit_id = 'يرجى اختيار الوحدة العقارية'; isValid = false }
  if (!contractForm.start_date) { contractErrors.start_date = 'يرجى اختيار تاريخ البداية'; isValid = false }
  if (!contractForm.end_date) { contractErrors.end_date = 'يرجى اختيار تاريخ النهاية'; isValid = false }
  if (contractForm.rent_amount === null || contractForm.rent_amount === undefined || contractForm.rent_amount < 0) {
    contractErrors.rent_amount = 'يرجى إدخال قيمة إيجار صحيحة'
    isValid = false
  }
  if (!isValid) return

  savingContract.value = true
  try {
    await api.post('/contracts', { ...contractForm, tenant_id: tenant.value.id })
    toast.success('تم إنشاء العقد بنجاح')
    showContractDialog.value = false
    await loadTenant()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ العقد')
  } finally {
    savingContract.value = false
  }
}

function openInvoiceDialog() {
  const active = activeContracts.value[0]
  Object.assign(invoiceForm, {
    contract_id: active?.id || null,
    issue_date: new Date(),
    due_date: null,
    rent_amount: active?.rent_amount || 0,
    electricity_amount: active?.electricity_amount || 0,
    water_amount: active?.water_amount || 0,
    internet_amount: active?.internet_amount || 0,
    services_amount: active?.services_amount || 0,
    notes: ''
  })
  invoiceErrors.contract_id = ''
  showInvoiceDialog.value = true
}

function onInvoiceContractChange() {
  invoiceErrors.contract_id = ''
  const c = allContracts.value.find(x => x.id === invoiceForm.contract_id)
  if (c) {
    invoiceForm.rent_amount = c.rent_amount || 0
    invoiceForm.electricity_amount = c.electricity_amount || 0
    invoiceForm.water_amount = c.water_amount || 0
    invoiceForm.internet_amount = c.internet_amount || 0
    invoiceForm.services_amount = c.services_amount || 0
  }
}

async function saveInvoice() {
  if (!invoiceForm.contract_id) {
    invoiceErrors.contract_id = 'يرجى اختيار العقد'
    return
  }
  savingInvoice.value = true
  try {
    await api.post('/invoices', invoiceForm)
    toast.success('تم إنشاء الفاتورة بنجاح')
    showInvoiceDialog.value = false
    await loadTenant()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ الفاتورة')
  } finally {
    savingInvoice.value = false
  }
}

function openPaymentDialog() {
  const first = payableInvoices.value[0]
  Object.assign(paymentForm, {
    invoice_id: first?.id || null,
    amount: first ? first.total_amount - first.paid_amount : null,
    payment_date: new Date(),
    payment_method: 'cash',
    reference_number: null,
    notes: ''
  })
  Object.keys(paymentErrors).forEach(k => paymentErrors[k] = '')
  showPaymentDialog.value = true
}

function onPaymentInvoiceChange() {
  paymentErrors.invoice_id = ''
  const inv = allInvoices.value.find(x => x.id === paymentForm.invoice_id)
  if (inv) paymentForm.amount = inv.total_amount - inv.paid_amount
}

async function savePayment() {
  if (!paymentForm.invoice_id) {
    paymentErrors.invoice_id = 'يرجى اختيار الفاتورة'
    return
  }
  if (!paymentForm.amount || paymentForm.amount <= 0) {
    paymentErrors.amount = 'يرجى إدخال مبلغ صحيح'
    return
  }
  if (!paymentForm.payment_date) {
    paymentErrors.payment_date = 'يرجى اختيار تاريخ الدفع'
    return
  }
  savingPayment.value = true
  try {
    await api.post('/payments', paymentForm)
    toast.success('تم تسجيل الدفعة بنجاح')
    showPaymentDialog.value = false
    await loadTenant()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر تسجيل الدفعة')
  } finally {
    savingPayment.value = false
  }
}

async function downloadStatement() {
  try {
    const { data } = await api.get(`/reports/tenant-statement/${tenant.value.id}`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `tenant_statement_${tenant.value.id}_${new Date().toISOString().slice(0, 10)}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر تحميل كشف الحساب')
  }
}

async function loadTenant() {
  try {
    const tenantId = route.params.id
    const tenantRes = await api.get(`/tenants/${tenantId}`)
    tenant.value = tenantRes.data?.data || null

    const active = tenant.value?.contracts?.find(c => c.status === 'active')
    if (active?.unit?.id) {
      const maintRes = await api.get('/maintenance', { params: { unit_id: active.unit.id } })
      maintenanceItems.value = maintRes.data?.data || []
    } else {
      maintenanceItems.value = []
    }
  } catch (err) {
    console.error(err)
  }
}

onMounted(async () => {
  try {
    await loadTenant()
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
  background: var(--info-bg);
  color: var(--info);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  border: 2px solid var(--info-border);
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

@media (max-width: 768px) {
  .profile-header {
    flex-wrap: wrap;
    gap: 12px;
  }
  .header-left {
    flex-wrap: wrap;
  }
  .header-actions {
    width: 100%;
    flex-wrap: wrap;
  }
  .header-actions .btn {
    flex: 1;
    justify-content: center;
  }
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
  background: linear-gradient(180deg, var(--bg-surface) 0%, var(--warning-bg) 100%);
  border-color: var(--warning-border);
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
.badge-warning { background: var(--warning-bg); color: var(--warning-contrast); }
.badge-success { background: var(--success-bg); color: var(--success-contrast); }
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
  background: var(--danger-bg);
  border-radius: var(--radius-sm);
  border: 1px solid var(--danger-border);
}
.box-label { font-size: 12.5px; color: var(--danger-contrast); font-weight: 600; }
.box-value { font-size: 28px; font-weight: 900; margin-top: 4px; }
.text-danger { color: var(--danger); }
.text-success { color: var(--success); }
.text-warning { color: var(--warning); }
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
  background: var(--bg-subtle);
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
.status-active { background: var(--success-bg); color: var(--success-contrast); }
.status-expired { background: var(--danger-bg); color: var(--danger-contrast); }

@media (max-width: 640px) {
  .simple-table thead {
    display: none;
  }
  .simple-table,
  .simple-table tbody,
  .simple-table tr,
  .simple-table td {
    display: block;
    width: 100%;
  }
  .simple-table tr {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 4px 10px;
    margin: 8px 0;
  }
  .simple-table td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border-light);
    padding: 10px 4px;
    text-align: right;
  }
  .simple-table td:last-child {
    border-bottom: none;
  }
  .simple-table td::before {
    content: attr(data-label);
    font-size: 11.5px;
    font-weight: 600;
    color: var(--text-muted);
    flex-shrink: 0;
  }
  .simple-table td[colspan]::before {
    display: none;
  }
  .simple-table td[colspan] {
    justify-content: center;
  }
}
.text-center { text-align: center; }
.text-muted { color: var(--text-secondary); }
.text-xs { font-size: 12px; }
.text-accent { color: var(--accent-hover); }
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
.bg-info-light { background: var(--info-bg); }
.text-info { color: var(--info-contrast); }
.bg-warning-light { background: var(--warning-bg); }
.item-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.item-title { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
.item-sub { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
</style>
