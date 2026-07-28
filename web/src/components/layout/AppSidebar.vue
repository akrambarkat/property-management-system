<template>
  <div class="sidebar" :class="{ collapsed: appStore.sidebarCollapsed }">
    <div class="sidebar-header">
      <div class="logo">
        <i class="pi pi-building" style="font-size: 1.5rem; color: var(--secondary);"></i>
        <span v-show="!appStore.sidebarCollapsed" class="logo-text">EMAARPlus</span>
      </div>
    </div>

    <div class="sidebar-menu">
      <router-link
        v-for="item in menuItems"
        :key="item.to"
        :to="item.to"
        class="menu-item"
        :class="{ active: isActive(item.to) }"
      >
        <i :class="item.icon"></i>
        <span v-show="!appStore.sidebarCollapsed" class="menu-label">{{ item.label }}</span>
      </router-link>
    </div>

    <div class="sidebar-footer">
      <button class="collapse-btn" @click="appStore.toggleSidebar">
        <i class="pi pi-chevron-right" :class="{ rotated: appStore.sidebarCollapsed }"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const appStore = useAppStore()
const authStore = useAuthStore()

const menuItems = [
  { to: '/', label: 'لوحة التحكم', icon: 'pi pi-chart-pie' },
  { to: '/locations', label: 'المواقع', icon: 'pi pi-map-marker' },
  { to: '/buildings', label: 'المباني', icon: 'pi pi-building' },
  { to: '/units', label: 'الوحدات', icon: 'pi pi-th-large' },
  { to: '/tenants', label: 'المستأجرين', icon: 'pi pi-users' },
  { to: '/contracts', label: 'العقود', icon: 'pi pi-file' },
  { to: '/invoices', label: 'الفواتير', icon: 'pi pi-receipt' },
  { to: '/payments', label: 'المدفوعات', icon: 'pi pi-wallet' },
  { to: '/utilities', label: 'المرافق', icon: 'pi pi-bolt' },
  { to: '/expenses', label: 'المصروفات', icon: 'pi pi-money-bill' },
  { to: '/maintenance', label: 'الصيانة', icon: 'pi pi-wrench' },
  { to: '/reports', label: 'التقارير', icon: 'pi pi-chart-bar' },
]

if (authStore.isSuperAdmin) {
  menuItems.push({ to: '/users', label: 'المستخدمين', icon: 'pi pi-cog' })
}

menuItems.push({ to: '/settings', label: 'الإعدادات', icon: 'pi pi-sliders-h' })

function isActive(to) {
  if (to === '/') return route.path === '/'
  return route.path.startsWith(to)
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  right: 0;
  top: 0;
  width: var(--sidebar-width);
  height: 100vh;
  background: var(--primary);
  color: white;
  display: flex;
  flex-direction: column;
  z-index: 1000;
  transition: width 0.3s ease;
  overflow: hidden;
}

.sidebar.collapsed {
  width: 80px;
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-text {
  font-size: 20px;
  font-weight: 600;
  white-space: nowrap;
}

.sidebar-menu {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
  cursor: pointer;
  white-space: nowrap;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.menu-item.active {
  background: rgba(255, 255, 255, 0.15);
  color: var(--secondary);
}

.menu-item i {
  font-size: 1.2rem;
  min-width: 24px;
  text-align: center;
}

.menu-label {
  font-size: 14px;
  font-weight: 500;
}

.sidebar-footer {
  padding: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.collapse-btn {
  width: 100%;
  padding: 10px;
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  cursor: pointer;
  transition: all 0.2s;
}

.collapse-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.collapse-btn i {
  transition: transform 0.3s ease;
}

.collapse-btn i.rotated {
  transform: rotate(180deg);
}
</style>
