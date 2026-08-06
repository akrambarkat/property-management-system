<template>
  <div class="layout-wrapper" :class="{ 'sidebar-collapsed': appStore.sidebarCollapsed }">
    <!-- Global Toast Container -->
    <ToastContainer />

    <!-- Mobile Sidebar Backdrop -->
    <div
      v-if="isMobile && !appStore.sidebarCollapsed"
      class="sidebar-backdrop"
      @click="appStore.toggleSidebar"
      aria-hidden="true"
    ></div>

    <!-- Fixed Sidebar -->
    <AppSidebar />

    <div class="layout-main">
      <!-- Fixed Sticky Navbar with Integrated Global Search & Quick Actions -->
      <AppHeader @open-command-palette="showCommandPalette = true" />
      
      <!-- Sub-Navbar Breadcrumb Area (Directly Below Sticky Navbar) -->
      <div class="sub-navbar-breadcrumb">
        <nav class="breadcrumb-container">
          <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
              <router-link to="/" class="breadcrumb-link">
                <i class="pi pi-home"></i>
                <span>الرئيسية</span>
              </router-link>
            </li>
            <li v-for="(crumb, index) in breadcrumbs" :key="index" class="breadcrumb-item">
              <i class="pi pi-chevron-left breadcrumb-separator"></i>
              <router-link v-if="crumb.to" :to="crumb.to" class="breadcrumb-link">
                {{ crumb.label }}
              </router-link>
              <span v-else class="breadcrumb-current">{{ crumb.label }}</span>
            </li>
          </ol>
        </nav>

        <div class="header-breadcrumb-right">
          <span class="system-time"><i class="pi pi-clock"></i> {{ currentTime }}</span>
        </div>
      </div>

      <!-- Main Balanced Content Viewport -->
      <main class="layout-content">
        <div class="content-container">
          <router-view v-slot="{ Component }">
            <transition name="fade-slide" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>

    <!-- Indexed Command Palette (Global Search Ctrl+K) -->
    <Dialog
      v-model:visible="showCommandPalette"
      :modal="true"
      :showHeader="false"
      :dismissableMask="true"
      class="command-palette-dialog"
      :style="{ width: '640px', maxWidth: '92vw' }"
    >
      <div class="command-palette-body">
        <div class="command-search-header">
          <i class="pi pi-search search-icon"></i>
          <input
            type="text"
            v-model="commandQuery"
            placeholder="بحث شامل في النظام (مستأجر، شقة، عقد، فاتورة، صيانة)..."
            class="command-input"
            ref="commandInputRef"
            @keydown.esc="showCommandPalette = false"
          />
          <kbd class="esc-kbd">ESC</kbd>
        </div>

        <div class="command-results" v-if="commandQuery.trim()">
          <div class="results-group-title">نتائج البحث الفوري</div>
          <div v-if="searchLoading" class="loading-box">
            <i class="pi pi-spin pi-spinner"></i>
            <p>جاري البحث...</p>
          </div>
          <template v-else>
            <div
              v-for="res in filteredCommands"
              :key="res.id"
              class="command-item"
              @click="navigateToCommand(res.to)"
            >
              <div class="item-icon-box" :class="res.category">
                <i :class="res.icon"></i>
              </div>
              <div class="item-details">
                <span class="item-title">{{ res.title }}</span>
                <span class="item-subtitle">{{ res.subtitle }}</span>
              </div>
              <span class="category-badge">{{ res.categoryLabel }}</span>
            </div>
            <div v-if="!filteredCommands.length" class="no-results-box">
              <i class="pi pi-search-minus"></i>
              <p>لم يتم العثور على أي نتائج تطابق "{{ commandQuery }}"</p>
            </div>
          </template>
        </div>

        <div class="command-shortcuts-footer">
          <span><kbd>↑</kbd> <kbd>↓</kbd> للتنقل</span>
          <span><kbd>↵</kbd> للاختيار</span>
          <span><kbd>Ctrl</kbd> + <kbd>K</kbd> لفتح البحث في أي وقت</span>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { useRoute, useRouter } from 'vue-router'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import ToastContainer from '@/components/common/ToastContainer.vue'
import { useAppStore } from '@/stores/app'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()

const showCommandPalette = ref(false)
const commandQuery = ref('')
const commandInputRef = ref(null)
const searchResults = ref([])
const searchLoading = ref(false)
const currentTime = ref('')
const isMobile = ref(false)

watch(showCommandPalette, (val) => {
  if (val) {
    commandQuery.value = ''
    searchResults.value = []
    setTimeout(() => commandInputRef.value?.focus(), 100)
  }
})

const routeNamesMap = {
  Dashboard: 'لوحة التحكم القيادية',
  Locations: 'المواقع العقارية',
  Buildings: 'المباني والأبراج',
  Units: 'الوحدات والتفاصيل',
  Tenants: 'سجل المستأجرين',
  Contracts: 'العقود والاتفاقيات',
  Invoices: 'الفواتير والتحصيلات',
  Payments: 'سجل المدفوعات',
  Utilities: 'حساب المرافق',
  Expenses: 'المصروفات التشغيلية',
  Maintenance: 'طلبات الصيانة',
  Reports: 'التقارير التحليلية',
  Notifications: 'مركز الإشعارات',
  Users: 'إدارة المستخدمين',
  Settings: 'إعدادات النظام',
  SmsOverview: 'مركز الرسائل SMS',
  SmsLogs: 'سجل الرسائل',
  SmsTemplates: 'قوالب الرسائل',
  SmsBulk: 'الإرسال الجماعي',
  SmsScheduler: 'الإرسال التلقائي'
}

const breadcrumbs = computed(() => {
  const currentName = route.name
  if (!currentName || currentName === 'Dashboard') return []

  if (['Buildings', 'Units'].includes(currentName)) {
    return [
      { label: 'إدارة العقارات', to: '/locations' },
      { label: routeNamesMap[currentName] || currentName }
    ]
  }

  if (['Invoices', 'Payments', 'Utilities', 'Expenses'].includes(currentName)) {
    return [
      { label: 'المالية والحسابات', to: '/invoices' },
      { label: routeNamesMap[currentName] || currentName }
    ]
  }

  if (['SmsLogs', 'SmsTemplates', 'SmsBulk', 'SmsScheduler'].includes(currentName)) {
    return [
      { label: 'الرسائل SMS', to: '/sms' },
      { label: routeNamesMap[currentName] || currentName }
    ]
  }

  return [{ label: routeNamesMap[currentName] || currentName }]
})

let searchTimer

watch(commandQuery, (val) => {
  clearTimeout(searchTimer)
  if (!val.trim()) {
    searchResults.value = []
    return
  }
  searchLoading.value = true
  searchTimer = setTimeout(() => performSearch(val.trim()), 300)
})

async function performSearch(q) {
  try {
    const [tenantsRes, contractsRes, invoicesRes, maintRes] = await Promise.all([
      api.get('/tenants', { params: { search: q } }),
      api.get('/contracts', { params: { status: 'active' } }),
      api.get('/invoices'),
      api.get('/maintenance'),
    ])
    const results = []
    ;(tenantsRes.data?.data || []).slice(0, 5).forEach(t => {
      results.push({
        id: `t-${t.id}`, title: `${t.first_name} ${t.last_name}`,
        subtitle: `مستأجر - ${t.id_number || t.phone || ''}`,
        category: 'tenant', categoryLabel: 'مستأجر', icon: 'pi pi-user', to: `/tenants/${t.id}`
      })
    })
    ;(contractsRes.data?.data || []).slice(0, 5).forEach(c => {
      if (!`${c.contract_number} ${c.tenant?.first_name || ''} ${c.unit?.unit_number || ''}`.toLowerCase().includes(q.toLowerCase())) return
      results.push({
        id: `c-${c.id}`, title: `عقد رقم ${c.contract_number}`,
        subtitle: `${c.tenant?.first_name || ''} ${c.tenant?.last_name || ''} - وحدة #${c.unit?.unit_number || ''}`,
        category: 'contract', categoryLabel: 'عقد', icon: 'pi pi-file', to: '/contracts'
      })
    })
    ;(invoicesRes.data?.data || []).slice(0, 5).forEach(inv => {
      if (!`${inv.invoice_number} ${inv.contract?.tenant?.first_name || ''}`.toLowerCase().includes(q.toLowerCase())) return
      results.push({
        id: `i-${inv.id}`, title: `فاتورة ${inv.invoice_number}`,
        subtitle: `${Number(inv.total_amount).toLocaleString('ar-EG')} ₪ - ${inv.status === 'paid' ? 'مدفوعة' : 'غير مدفوعة'}`,
        category: 'invoice', categoryLabel: 'فاتورة', icon: 'pi pi-receipt', to: '/invoices'
      })
    })
    ;(maintRes.data?.data || []).slice(0, 5).forEach(m => {
      if (!m.description?.toLowerCase().includes(q.toLowerCase())) return
      results.push({
        id: `m-${m.id}`, title: m.description,
        subtitle: `صيانة - وحدة #${m.unit?.unit_number || ''}`,
        category: 'maintenance', categoryLabel: 'صيانة', icon: 'pi pi-wrench', to: '/maintenance'
      })
    })
    searchResults.value = results
  } catch (err) {
    console.error(err)
    searchResults.value = []
  } finally {
    searchLoading.value = false
  }
}

const filteredCommands = computed(() => {
  if (!commandQuery.value.trim()) return []
  return searchResults.value
})

function navigateToCommand(path) {
  showCommandPalette.value = false
  commandQuery.value = ''
  router.push(path)
}

function updateTime() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })
}

function handleGlobalKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    showCommandPalette.value = !showCommandPalette.value
  }
}

let timer
function updateIsMobile() {
  const wasMobile = isMobile.value
  isMobile.value = window.innerWidth <= 768
  if (isMobile.value && !wasMobile) {
    appStore.sidebarCollapsed = true
  }
}
onMounted(() => {
  document.body.style.direction = 'rtl'
  updateIsMobile()
  window.addEventListener('resize', updateIsMobile)
  updateTime()
  timer = setInterval(updateTime, 30000)
  window.addEventListener('keydown', handleGlobalKeydown)
})

/* Close the mobile sidebar whenever the user navigates */
watch(() => route.fullPath, () => {
  if (isMobile.value) {
    appStore.sidebarCollapsed = true
  }
})

onUnmounted(() => {
  clearInterval(timer)
  window.removeEventListener('resize', updateIsMobile)
  window.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<style scoped>
.layout-wrapper {
  display: flex;
  min-height: 100vh;
  background-color: var(--bg-main);
  direction: rtl;
  overflow: hidden;
}

.layout-main {
  flex: 1;
  margin-right: var(--sidebar-width);
  transition: margin-right 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
}

.sidebar-collapsed .layout-main {
  margin-right: 80px;
}

/* Mobile Sidebar Backdrop */
.sidebar-backdrop {
  position: fixed;
  inset: 0;
  background: var(--overlay-backdrop);
  z-index: 999;
}

/* Sub-Navbar Breadcrumb Area - Positioned Directly Below Sticky Header */
.sub-navbar-breadcrumb {
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border);
  padding: 10px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: var(--shadow-sm);
  flex-shrink: 0;
}

.breadcrumb-list {
  display: flex;
  align-items: center;
  gap: 8px;
  list-style: none;
  margin: 0;
  padding: 0;
}
.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}
.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  font-weight: 500;
  text-decoration: none;
  transition: color 0.15s ease;
}
.breadcrumb-link:hover {
  color: var(--text-primary);
}
.breadcrumb-separator {
  font-size: 9px;
  color: var(--text-muted);
}
.breadcrumb-current {
  color: var(--text-primary);
  font-weight: 600;
}

.system-time {
  font-size: 12.5px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Layout Content - Clean Single Scroll Container */
.layout-content {
  flex: 1;
  padding: 24px 28px;
  overflow-y: auto;
  min-height: 0;
}

.content-container {
  max-width: 1600px;
  margin: 0 auto;
  width: 100%;
}

/* Command Palette Styling */
.command-palette-body {
  display: flex;
  flex-direction: column;
  background: var(--bg-surface);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.command-search-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  position: relative;
}
.search-icon {
  font-size: 1.2rem;
  color: var(--accent);
}
.command-input {
  flex: 1;
  border: none;
  outline: none;
  font-family: var(--font-family);
  font-size: 15px;
  background: transparent;
  color: var(--text-primary);
}
.esc-kbd {
  background: var(--bg-subtle, #F1F5F9);
  border: 1px solid var(--border);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  color: var(--text-muted);
}

.command-results {
  max-height: 360px;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.results-group-title {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text-muted);
  margin: 4px 8px;
}
.command-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background 0.15s ease;
}
.command-item:hover {
  background: var(--bg-subtle, #F1F5F9);
}

.item-icon-box {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}
.item-icon-box.tenant { background: var(--info-bg); color: var(--info-contrast); }
.item-icon-box.contract { background: var(--warning-bg); color: var(--warning-contrast); }
.item-icon-box.invoice { background: var(--danger-bg); color: var(--danger-contrast); }
.item-icon-box.maintenance { background: var(--bg-subtle); color: var(--text-secondary); }

.item-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.item-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}
.item-subtitle {
  font-size: 12px;
  color: var(--text-secondary);
}

.category-badge {
  font-size: 11px;
  background: var(--bg-subtle, #F1F5F9);
  color: var(--text-secondary);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}

.no-results-box {
  text-align: center;
  padding: 32px 16px;
  color: var(--text-muted);
}
.loading-box {
  text-align: center;
  padding: 32px 16px;
  color: var(--text-muted);
}

.command-shortcuts-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: var(--bg-subtle);
  border-top: 1px solid var(--border);
  font-size: 12px;
  color: var(--text-secondary);
}
.command-shortcuts-footer kbd {
  background: var(--bg-surface-elevated);
  border: 1px solid var(--border);
  padding: 1px 5px;
  border-radius: 4px;
  font-weight: 600;
}

/* Page Transition Animation */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.page-title-group h1 {
  font-size: 1.15rem;
}

@media (max-width: 768px) {
  .layout-main,
  .sidebar-collapsed .layout-main {
    margin-right: 0;
  }
  .layout-content {
    padding: 14px;
  }
}

/* ================= Responsive layout layer ================= */
@media (max-width: 1200px) {
  .sub-navbar-breadcrumb {
    padding: 8px 20px;
  }
  .layout-content {
    padding: 20px;
  }
}

@media (max-width: 768px) {
  .sub-navbar-breadcrumb {
    padding: 8px 14px;
    gap: 8px;
  }
  .system-time { display: none; }
  .breadcrumb-item:nth-of-type(2) .breadcrumb-separator { display: none; }
  .layout-content {
    padding: 14px;
  }
  .content-container {
    max-width: 100%;
  }
}

@media (max-width: 480px) {
  .sub-navbar-breadcrumb {
    padding: 8px 12px;
  }
  .breadcrumb-list {
    gap: 6px;
  }
  .breadcrumb-link span,
  .breadcrumb-current {
    font-size: 12.5px;
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .breadcrumb-link {
    max-width: 140px;
  }
  .breadcrumb-item:first-child .breadcrumb-link span {
    max-width: none;
  }
}
</style>
