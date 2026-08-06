<template>
  <aside
    id="sidebar"
    class="sidebar"
    :class="{ collapsed: appStore.sidebarCollapsed }"
    :style="dragStyle"
    aria-label="القائمة الجانبية"
    @touchstart="onTouchStart"
    @touchmove="onTouchMove"
    @touchend="onTouchEnd"
    @touchcancel="onTouchEnd"
  >
    <!-- Section 1: Fixed Logo Header (never scrolls) -->
    <div class="sidebar-header">
      <router-link to="/" class="brand" :title="appStore.sidebarCollapsed ? 'AqarMaster' : ''">
        <div class="logo-icon">
          <i class="pi pi-building-columns"></i>
        </div>
        <div v-show="!appStore.sidebarCollapsed" class="brand-text">
          <span class="brand-name">Aqar<span class="brand-plus">Master</span></span>
          <span class="brand-sub">إدارة العقارات SaaS</span>
        </div>
      </router-link>
      <button
        v-if="isMobile"
        type="button"
        class="sidebar-close-btn"
        @click="appStore.toggleSidebar"
        aria-label="إغلاق القائمة"
      >
        <i class="pi pi-times"></i>
      </button>
    </div>

    <!-- Section 2: Scrollable Navigation Menu (only this section scrolls) -->
    <nav class="sidebar-menu" aria-label="التنقل الرئيسي">
      <template v-for="(group, gIdx) in menuGroups" :key="gIdx">
        <div
          v-if="group.title && !appStore.sidebarCollapsed && group.items.length"
          class="menu-group-title"
        >
          {{ group.title }}
        </div>
        <router-link
          v-for="item in group.items"
          :key="item.to"
          :to="item.to"
          class="menu-item"
          :class="{ active: isActive(item.to) }"
          :title="appStore.sidebarCollapsed ? item.label : ''"
          :aria-current="isActive(item.to) ? 'page' : null"
        >
          <div class="item-icon-wrapper">
            <i :class="item.icon"></i>
          </div>
          <span v-show="!appStore.sidebarCollapsed" class="menu-label">{{ item.label }}</span>
          <span
            v-if="item.badge && !appStore.sidebarCollapsed"
            class="menu-badge"
            :class="item.badgeClass"
          >
            {{ item.badge }}
          </span>
        </router-link>
      </template>
    </nav>

    <!-- Section 3: Fixed User Section (never scrolls) -->
    <footer class="sidebar-user" v-if="authStore.currentUser">
      <router-link to="/settings" class="sidebar-user-inner" @click="handleUserClick">
        <span class="user-avatar">{{ userInitial }}</span>
        <div v-show="!appStore.sidebarCollapsed" class="user-meta">
          <span class="user-name">{{ userName }}</span>
          <span class="user-role">{{ roleTitle }}</span>
        </div>
        <button
          v-show="!appStore.sidebarCollapsed"
          type="button"
          class="user-logout-btn"
          @click.prevent="handleLogout"
          :title="'تسجيل الخروج'"
        >
          <i class="pi pi-sign-out"></i>
        </button>
      </router-link>
    </footer>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()

const isMobile = ref(false)
const touchStartX = ref(0)
const touchDelta = ref(0)
const dragging = ref(false)
let mq = null

onMounted(() => {
  notificationsStore.fetchUnreadCount()
  mq = window.matchMedia('(max-width: 768px)')
  isMobile.value = mq.matches
  if (mq.addEventListener) {
    mq.addEventListener('change', onChange)
  } else if (mq.addListener) {
    mq.addListener(onChange)
  }
})

onBeforeUnmount(() => {
  if (mq?.removeEventListener) mq.removeEventListener('change', onChange)
  else if (mq?.removeListener) mq.removeListener(onChange)
})

function onChange(e) {
  isMobile.value = e.matches
}

const dragStyle = computed(() => {
  if (!dragging.value || touchDelta.value <= 0) return null
  return { transform: `translateX(${touchDelta.value}px)` }
})

function onTouchStart(e) {
  if (!isMobile.value || appStore.sidebarCollapsed || !e.touches?.[0]) return
  dragging.value = true
  touchStartX.value = e.touches[0].clientX
  touchDelta.value = 0
}

function onTouchMove(e) {
  if (!dragging.value || !e.touches?.[0]) return
  const delta = Math.max(0, e.touches[0].clientX - touchStartX.value)
  touchDelta.value = delta
  if (delta > 0) e.preventDefault()
}

function onTouchEnd() {
  if (!dragging.value) return
  dragging.value = false
  if (touchDelta.value > 80) {
    appStore.toggleSidebar()
  }
  touchDelta.value = 0
}

const menuGroups = computed(() => {
  const groups = [
    {
      title: 'الرئيسية',
      items: [
        { to: '/', label: 'لوحة التحكم', icon: 'pi pi-chart-pie' }
      ]
    },
    {
      title: 'إدارة العقارات',
      items: [
        { to: '/locations', label: 'المواقع', icon: 'pi pi-map-marker' },
        { to: '/buildings', label: 'المباني', icon: 'pi pi-building' },
        { to: '/units', label: 'الوحدات', icon: 'pi pi-th-large' },
        { to: '/tenants', label: 'المستأجرين', icon: 'pi pi-users' },
        { to: '/contracts', label: 'العقود', icon: 'pi pi-file' }
      ]
    },
    {
      title: 'المالية والحسابات',
      items: [
        { to: '/invoices', label: 'الفواتير', icon: 'pi pi-receipt' },
        { to: '/payments', label: 'الدفعات', icon: 'pi pi-wallet' },
        { to: '/utilities', label: 'المرافق', icon: 'pi pi-bolt' },
        { to: '/expenses', label: 'المصروفات', icon: 'pi pi-money-bill' }
      ]
    },
    {
      title: 'الخدمات والتشغيل',
      items: [
        { to: '/maintenance', label: 'الصيانة', icon: 'pi pi-wrench' },
        { to: '/analytics', label: 'التحليلات المالية', icon: 'pi pi-chart-line' },
        { to: '/reports', label: 'التقارير', icon: 'pi pi-chart-bar' },
        { to: '/notifications', label: 'الإشعارات', icon: 'pi pi-bell', badge: notificationsStore.unreadCount > 0 ? String(notificationsStore.unreadCount) : null, badgeClass: 'badge-warning' }
      ]
    },
    {
      title: 'الرسائل SMS',
      items: [
        { to: '/sms', label: 'نظرة عامة', icon: 'pi pi-send' },
        { to: '/sms/bulk', label: 'إرسال جماعي', icon: 'pi pi-megaphone' },
        { to: '/sms/templates', label: 'القوالب', icon: 'pi pi-file-edit' },
        { to: '/sms/scheduler', label: 'الإرسال التلقائي', icon: 'pi pi-calendar-clock' },
        { to: '/sms/logs', label: 'سجل الرسائل', icon: 'pi pi-list' }
      ]
    },
    {
      title: 'النظام والإعدادات',
      items: []
    }
  ]

    if (authStore.isSuperAdmin) {
      groups[4].items.push({ to: '/users', label: 'المستخدمين', icon: 'pi pi-user-edit' })
    }
    groups[4].items.push({ to: '/notifications/settings', label: 'إعدادات الإشعارات', icon: 'pi pi-bell' })
    groups[4].items.push({ to: '/settings', label: 'الإعدادات', icon: 'pi pi-sliders-h' })

  return groups
})

function isActive(to) {
  if (to === '/') return route.path === '/'
  return route.path.startsWith(to)
}

const userName = computed(() => authStore.currentUser?.name || 'مدير النظام')

const userInitial = computed(() => {
  const name = authStore.currentUser?.name || 'م'
  return name.charAt(0).toUpperCase()
})

const roleTitle = computed(() => {
  const labels = { super_admin: 'مدير النظام', employee: 'موظف', guard: 'حارس' }
  return labels[authStore.currentUser?.role] || 'مدير النظام'
})

function handleUserClick() {
  if (isMobile.value) appStore.sidebarCollapsed = true
}

function handleLogout() {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  right: 0;
  top: 0;
  width: var(--sidebar-width);
  height: 100vh;
  height: 100dvh;
  background: var(--bg-sidebar, #FFFFFF);
  color: var(--text-primary, #0F172A);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  z-index: 1000;
  transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 1px solid var(--border, #E2E8F0);
  box-shadow: var(--shadow-sm);
  touch-action: pan-y;
}

.sidebar.collapsed {
  width: 80px;
}

/* Section 1: Fixed Logo Header */
.sidebar-header {
  flex-shrink: 0;
  padding: 18px;
  border-bottom: 1px solid var(--border, #E2E8F0);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  min-width: 0;
}
.sidebar.collapsed .brand {
  justify-content: center;
  width: 100%;
}
.logo-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--accent, #4F46E5) 0%, #3730A3 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #FFFFFF;
  box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
  flex-shrink: 0;
}
.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
  min-width: 0;
}
.brand-name {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.5px;
  color: var(--text-primary, #0F172A);
}
.brand-plus {
  color: var(--accent-hover);
}
.brand-sub {
  font-size: 11px;
  color: var(--text-secondary, #64748B);
  font-weight: 500;
}

.sidebar-close-btn {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--bg-subtle);
  color: var(--text-secondary);
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.sidebar-close-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

/* Section 2: Scrollable Navigation Menu (flex:1 + overflow-y only here) */
.sidebar-menu {
  flex: 1;
  min-height: 0;
  padding: 12px 14px;
  overflow-y: auto;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.menu-group-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
  margin: 14px 8px 6px 8px;
  letter-spacing: 0.5px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 12px;
  border-radius: var(--radius-sm, 8px);
  color: var(--text-secondary, #475569);
  transition: all 0.15s ease;
  cursor: pointer;
  white-space: nowrap;
  position: relative;
  font-weight: 500;
  text-decoration: none;
}

.sidebar.collapsed .menu-item {
  justify-content: center;
  padding: 9px 0;
}

.menu-item:hover {
  background: var(--bg-subtle, #F1F5F9);
  color: var(--text-primary, #0F172A);
}

.menu-item.active {
  background: var(--accent-light);
  color: var(--accent-hover);
  font-weight: 700;
  border-right: 3px solid var(--accent);
}

.item-icon-wrapper {
  width: 22px;
  display: flex;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.menu-label {
  font-size: 13.5px;
  flex: 1;
}

.menu-badge {
  padding: 2px 7px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
}
.badge-warning {
  background: var(--warning);
  color: var(--text-on-fill);
}

/* Section 3: Fixed User Section */
.sidebar-user {
  flex-shrink: 0;
  border-top: 1px solid var(--border, #E2E8F0);
  padding: 12px;
  background: var(--bg-sidebar, #FFFFFF);
}
.sidebar-user-inner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: var(--radius-sm, 8px);
  text-decoration: none;
  cursor: pointer;
  transition: background 0.15s ease;
}
.sidebar-user-inner:hover {
  background: var(--bg-subtle, #F1F5F9);
}
.sidebar.collapsed .sidebar-user-inner {
  justify-content: center;
  padding: 8px 4px;
}
.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent) 0%, #3730A3 100%);
  color: #FFFFFF;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
}
.user-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
  min-width: 0;
  flex: 1;
}
.user-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary, #0F172A);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.user-role {
  font-size: 11.5px;
  color: var(--text-secondary, #64748B);
}
.user-logout-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  border: none;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.user-logout-btn:hover {
  background: var(--danger-bg);
  color: var(--danger);
}

/* Mobile overlay behavior */
@media (max-width: 768px) {
  .sidebar,
  .sidebar.collapsed {
    width: min(var(--sidebar-width), 88vw);
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-lg);
  }
  .sidebar:not(.collapsed) {
    transform: translateX(0);
  }
  .sidebar-header {
    padding: 14px;
  }
  .sidebar-menu {
    padding: 10px 12px;
  }
  .menu-item {
    min-height: 46px;
  }
  .sidebar-user {
    padding: 10px 12px;
  }
}
</style>
