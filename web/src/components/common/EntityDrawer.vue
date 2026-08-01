<template>
  <Drawer
    v-model:visible="visible"
    position="left"
    class="entity-inspection-drawer"
    :style="{ width: '560px' }"
    :modal="true"
    :dismissable="true"
    @hide="onClose"
  >
    <template #header>
      <div class="drawer-header-content" v-if="entity">
        <div class="entity-badge-avatar" :class="entityType">
          <i :class="entityIcon"></i>
        </div>
        <div class="entity-title-meta">
          <h3 class="entity-title">{{ entityTitle }}</h3>
          <span class="entity-subtitle">{{ entitySubtitle }}</span>
        </div>
      </div>
    </template>

    <div v-if="loading" class="drawer-loading">
      <i class="pi pi-spin pi-spinner text-accent"></i>
      <span>جاري تحميل بيانات السجل...</span>
    </div>

    <div v-else-if="entity" class="drawer-body-container">
      <!-- Tabs Navigation -->
      <div class="drawer-tabs">
        <button
          v-for="tab in availableTabs"
          :key="tab.id"
          class="tab-btn"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >
          <i :class="tab.icon"></i>
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- Tab Content: Overview -->
      <div v-if="activeTab === 'overview'" class="tab-pane">
        <div class="info-section">
          <h4 class="section-title">المعلومات الأساسية</h4>
          <div class="info-grid">
            <div v-for="(val, label) in overviewFields" :key="label" class="info-item">
              <span class="info-label">{{ label }}</span>
              <span class="info-value">{{ val || '—' }}</span>
            </div>
          </div>
        </div>

        <div v-if="entity.notes || entity.description" class="info-section">
          <h4 class="section-title">الملاحظات والبيان</h4>
          <p class="notes-text">{{ entity.notes || entity.description }}</p>
        </div>
      </div>

      <!-- Tab Content: Contract Details -->
      <div v-if="activeTab === 'contract'" class="tab-pane">
        <div v-if="relatedData.contract" class="info-section">
          <div class="status-header">
            <span class="status-badge" :class="'status-' + relatedData.contract.status">
              عقد {{ relatedData.contract.status === 'active' ? 'نشط' : relatedData.contract.status }}
            </span>
            <span class="contract-num">#{{ relatedData.contract.contract_number }}</span>
          </div>
          <div class="info-grid mt-3">
            <div class="info-item"><span class="info-label">قيمة الإيجار</span><span class="info-value font-bold text-success">{{ formatCurrency(relatedData.contract.rent_amount) }}</span></div>
            <div class="info-item"><span class="info-label">تاريخ البداية</span><span class="info-value">{{ relatedData.contract.start_date }}</span></div>
            <div class="info-item"><span class="info-label">تاريخ النهاية</span><span class="info-value">{{ relatedData.contract.end_date }}</span></div>
            <div class="info-item"><span class="info-label">نوع العقد</span><span class="info-value">{{ relatedData.contract.contract_type === 'monthly' ? 'شهري' : 'سنوي' }}</span></div>
          </div>
        </div>
        <div v-else class="empty-tab">
          <i class="pi pi-file"></i>
          <p>لا يوجد عقد نشط مرتبك حالياً</p>
        </div>
      </div>

      <!-- Tab Content: Invoices & History -->
      <div v-if="activeTab === 'invoices'" class="tab-pane">
        <div v-if="relatedData.invoices?.length" class="history-list">
          <div v-for="inv in relatedData.invoices" :key="inv.id" class="history-card">
            <div class="history-main">
              <span class="history-title">فاتورة #{{ inv.invoice_number }}</span>
              <span class="history-amount">{{ formatCurrency(inv.total_amount) }}</span>
            </div>
            <div class="history-sub">
              <span>تاريخ: {{ inv.issue_date }}</span>
              <span :class="'status-badge status-' + inv.status">{{ inv.status === 'paid' ? 'مدفوعة' : 'غير مدفوعة' }}</span>
            </div>
          </div>
        </div>
        <div v-else class="empty-tab">
          <i class="pi pi-receipt"></i>
          <p>لا توجد فواتير مسجلة</p>
        </div>
      </div>
    </div>
  </Drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Drawer from 'primevue/drawer'
import api from '@/services/api'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  entityType: { type: String, required: true }, // 'tenant', 'unit', 'contract'
  entityId: { type: [Number, String], default: null }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const loading = ref(false)
const entity = ref(null)
const relatedData = ref({ contract: null, invoices: [], tickets: [] })
const activeTab = ref('overview')

watch(() => [props.entityId, props.modelValue], async ([id, isVis]) => {
  if (id && isVis) {
    await fetchEntityDetails(id)
  }
})

const entityIcon = computed(() => {
  if (props.entityType === 'tenant') return 'pi pi-user'
  if (props.entityType === 'unit') return 'pi pi-building'
  if (props.entityType === 'contract') return 'pi pi-file'
  return 'pi pi-info-circle'
})

const entityTitle = computed(() => {
  if (!entity.value) return ''
  if (props.entityType === 'tenant') return `${entity.value.first_name || ''} ${entity.value.last_name || ''}`
  if (props.entityType === 'unit') return `وحدة رقم #${entity.value.unit_number}`
  if (props.entityType === 'contract') return `عقد رقم #${entity.value.contract_number}`
  return 'تفاصيل السجل'
})

const entitySubtitle = computed(() => {
  if (!entity.value) return ''
  if (props.entityType === 'tenant') return `هاتف: ${entity.value.phone || '—'} | بريد: ${entity.value.email || '—'}`
  if (props.entityType === 'unit') return `المبنى: ${entity.value.building?.name || '—'} | الطابق: ${entity.value.floor || 0}`
  if (props.entityType === 'contract') return `المستأجر: ${entity.value.tenant?.first_name || ''}`
  return ''
})

const availableTabs = computed(() => {
  const tabs = [{ id: 'overview', label: 'المعلومات العامة', icon: 'pi pi-info-circle' }]
  if (props.entityType === 'tenant' || props.entityType === 'unit') {
    tabs.push({ id: 'contract', label: 'العقد الحالي', icon: 'pi pi-file' })
    tabs.push({ id: 'invoices', label: 'سجل الفواتير', icon: 'pi pi-receipt' })
  }
  return tabs
})

const overviewFields = computed(() => {
  if (!entity.value) return {}
  if (props.entityType === 'tenant') {
    return {
      'رقم الهوية / الجواز': entity.value.national_id,
      'رقم الهاتف': entity.value.phone,
      'البريد الإلكتروني': entity.value.email,
      'جهة العمل': entity.value.employer,
      'تاريخ التسجيل': entity.value.created_at?.split('T')[0]
    }
  }
  if (props.entityType === 'unit') {
    return {
      'رقم الوحدة': entity.value.unit_number,
      'المبنى': entity.value.building?.name,
      'الطابق': entity.value.floor,
      'عدد الغرف': entity.value.rooms,
      'المساحة (م²)': entity.value.area,
      'سعر الإيجار السنوي': formatCurrency(entity.value.rent_price)
    }
  }
  return {}
})

async function fetchEntityDetails(id) {
  loading.value = true
  activeTab.value = 'overview'
  try {
    const endpoint = props.entityType === 'tenant' ? '/tenants' : props.entityType === 'unit' ? '/units' : '/contracts'
    const { data } = await api.get(`${endpoint}/${id}`)
    entity.value = data.data || data

    // Fetch relational data if tenant
    if (props.entityType === 'tenant') {
      try {
        const { data: cData } = await api.get(`/contracts?tenant_id=${id}&status=active`)
        relatedData.value.contract = cData.data?.[0] || null
      } catch {}
      try {
        const { data: iData } = await api.get(`/invoices?tenant_id=${id}`)
        relatedData.value.invoices = iData.data || []
      } catch {}
    }
  } catch (err) {
    console.error('Error loading entity drawer details:', err)
  } finally {
    loading.value = false
  }
}

function formatCurrency(val) {
  if (!val) return '0 ₪'
  return `${Number(val).toLocaleString('ar-EG')} ₪`
}

function onClose() {
  entity.value = null
  relatedData.value = { contract: null, invoices: [], tickets: [] }
}
</script>

<style scoped>
.drawer-header-content {
  display: flex;
  align-items: center;
  gap: 14px;
}
.entity-badge-avatar {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  background: var(--info-bg);
  color: var(--info-contrast);
}
.entity-badge-avatar.unit {
  background: var(--warning-bg);
  color: var(--warning-contrast);
}
.entity-title-meta {
  display: flex;
  flex-direction: column;
}
.entity-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.entity-subtitle {
  font-size: 12.5px;
  color: var(--text-secondary);
}

.drawer-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 12px;
  color: var(--text-secondary);
}

.drawer-body-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-top: 10px;
}

.drawer-tabs {
  display: flex;
  border-bottom: 1px solid var(--border);
  gap: 8px;
}
.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border: none;
  background: transparent;
  font-family: var(--font-family);
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s ease;
}
.tab-btn.active {
  color: var(--accent-hover);
  border-bottom-color: var(--accent);
  font-weight: 600;
}

.tab-pane {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.info-section {
  background: var(--bg-subtle, #F8FAFC);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px;
}
.section-title {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px 16px;
}
.info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.info-label {
  font-size: 12px;
  color: var(--text-muted);
}
.info-value {
  font-size: 13.5px;
  font-weight: 500;
  color: var(--text-primary);
}

.notes-text {
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.6;
}

.empty-tab {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-muted);
}
.empty-tab i {
  font-size: 2.2rem;
  margin-bottom: 8px;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.history-card {
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border);
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.history-main {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
  font-weight: 600;
}
.history-sub {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-secondary);
}

.status-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.contract-num {
  font-weight: 700;
  font-size: 14px;
}
</style>
