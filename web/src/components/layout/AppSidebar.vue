<template>
  <div class="sidebar" :class="{ collapsed: appStore.sidebarCollapsed }">
    <!-- Header / Brand -->
    <div class="sidebar-header">
      <div class="brand">
        <div class="logo-icon">
          <i class="pi pi-building-columns"></i>
        </div>
        <div v-show="!appStore.sidebarCollapsed" class="brand-text">
          <span class="brand-name">EMAAR<span class="brand-plus">Plus</span></span>
          <span class="brand-sub">إدارة العقارات SaaS</span>
        </div>
      </div>
    </div>

    <!-- Quick Search Input -->
    <div class="sidebar-search" v-show="!appStore.sidebarCollapsed">
      <div class="search-box">
        <i class="pi pi-search search-icon"></i>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="بحث سريع بالصفحات..."
          class="search-input"
        />
        <span v-if="searchQuery" class="clear-btn" @click="searchQuery = ''">×</span>
      </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
      <template v-for="(group, gIdx) in filteredMenuGroups" :key="gIdx">
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
      <div v-if="filteredMenuGroups.every(g => !g.items.length)" class="no-results">
        لا توجد نتائج بحث
      </div>
    </div>

    <!-- User Profile Area Footer -->
    <div class="sidebar-footer">
      <div class="user-profile-card" v-show="!appStore.sidebarCollapsed">
        <div class="user-avatar">
          <span>{{ userInitials }}</span>
        </div>
        <div class="user-info">
          <span class="user-name">{{ authStore.currentUser?.name || 'مدير النظام' }}</span>
          <span class="user-role">{{ roleLabel }}</span>
        </div>
        <button class="user-menu-btn" @click="toggleUserDropdown" title="خيارات الحساب">
          <i class="pi pi-ellipsis-v"></i>
        </button>

        <!-- Context Menu Dropdown -->
        <transition name="fade">
          <div v-if="showUserDropdown" class="user-dropdown-menu">
            <router-link to="/settings" class="dropdown-item" @click="showUserDropdown = false">
              <i class="pi pi-user"></i>
              <span>الملف الشخصي</span>
            </router-link>
            <router-link to="/settings" class="dropdown-item" @click="showUserDropdown = false">
              <i class="pi pi-sliders-h"></i>
              <span>الإعدادات</span>
            </router-link>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item logout" @click="handleLogout">
              <i class="pi pi-sign-out"></i>
              <span>تسجيل الخروج</span>
            </button>
          </div>
        </transition>
      </div>

      <button
        class="collapse-btn"
        @click="appStore.toggleSidebar"
        :title="appStore.sidebarCollapsed ? 'توسيع القائمة' : 'طَي القائمة'"
      >
        <i class="pi pi-chevron-right" :class="{ rotated: appStore.sidebarCollapsed }"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const authStore = useAuthStore()

const searchQuery = ref('')
const showUserDropdown = ref(false)

const userInitials = computed(() => {
  const name = authStore.currentUser?.name || 'م'
  return name.charAt(0).toUpperCase()
})

const roleLabel = computed(() => {
  const labels = { super_admin: 'مدير النظام', employee: 'موظف', guard: 'حارس' }
  return labels[authStore.currentUser?.role] || 'مدير النظام'
})

const rawMenuGroups = computed(() => {
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
        { to: '/notifications', label: 'الإشعارات', icon: 'pi pi-bell', badge: '3', badgeClass: 'badge-warning' }
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
  groups[4].items.push({ to: '/settings', label: 'الإعدادات', icon: 'pi pi-sliders-h' })

  return groups
})

const filteredMenuGroups = computed(() => {
  if (!searchQuery.value.trim()) return rawMenuGroups.value

  const q = searchQuery.value.toLowerCase().trim()
  return rawMenuGroups.value.map(group => {
    const filteredItems = group.items.filter(item => item.label.toLowerCase().includes(q))
    return { ...group, items: filteredItems }
  })
})

function isActive(to) {
  if (to === '/') return route.path === '/'
  return route.path.startsWith(to)
}

function toggleUserDropdown() {
  showUserDropdown.value = !showUserDropdown.value
}

function handleLogout() {
  showUserDropdown.value = false
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
  background: var(--bg-sidebar, #FFFFFF);
  color: var(--text-primary, #0F172A);
  display: flex;
  flex-direction: column;
  z-index: 1000;
  transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 1px solid var(--border, #E2E8F0);
  box-shadow: 2px 0 12px rgba(15, 23, 42, 0.04);
}

.sidebar.collapsed {
  width: 80px;
}

/* Header & Logo */
.sidebar-header {
  padding: 20px 18px;
  border-bottom: 1px solid var(--border, #E2E8F0);
}
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
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
}
.brand-name {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.5px;
  color: var(--text-primary, #0F172A);
}
.brand-plus {
  color: var(--accent, #4F46E5);
}
.brand-sub {
  font-size: 11px;
  color: var(--text-secondary, #64748B);
  font-weight: 500;
}

/* Search Box */
.sidebar-search {
  padding: 14px 16px 6px 16px;
}
.search-box {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  right: 12px;
  color: #94A3B8;
  font-size: 0.85rem;
}
.search-input {
  width: 100%;
  padding: 8px 34px 8px 24px;
  background: #F8FAFC;
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-sm, 8px);
  color: var(--text-primary, #0F172A);
  font-family: var(--font-family);
  font-size: 13px;
  outline: none;
  transition: all 0.2s ease;
}
.search-input:focus {
  background: #FFFFFF;
  border-color: var(--accent, #4F46E5);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.clear-btn {
  position: absolute;
  left: 10px;
  color: #94A3B8;
  cursor: pointer;
  font-size: 14px;
}

/* Menu */
.sidebar-menu {
  flex: 1;
  padding: 12px 14px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.menu-group-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #94A3B8;
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
}

.menu-item:hover {
  background: #F1F5F9;
  color: var(--text-primary, #0F172A);
}

.menu-item.active {
  background: #EEF2FF;
  color: var(--accent, #4F46E5);
  font-weight: 700;
  border-right: 3px solid var(--accent, #4F46E5);
}

.item-icon-wrapper {
  width: 22px;
  display: flex;
  justify-content: center;
  font-size: 1.1rem;
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
  background: #F59E0B;
  color: #FFFFFF;
}

.no-results {
  font-size: 13px;
  color: #94A3B8;
  text-align: center;
  padding: 20px 0;
}

/* Footer & User Profile */
.sidebar-footer {
  padding: 12px 14px;
  border-top: 1px solid var(--border, #E2E8F0);
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
  background: #F8FAFC;
}

.user-profile-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  background: #FFFFFF;
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-sm, 8px);
  position: relative;
}

.user-avatar {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, var(--accent, #4F46E5) 0%, #3730A3 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  color: #FFFFFF;
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary, #0F172A);
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

.user-role {
  font-size: 11px;
  color: var(--text-secondary, #64748B);
}

.user-menu-btn {
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}
.user-menu-btn:hover {
  color: var(--text-primary, #0F172A);
  background: #F1F5F9;
}

.user-dropdown-menu {
  position: absolute;
  bottom: 100%;
  right: 14px;
  left: 14px;
  margin-bottom: 8px;
  background: #FFFFFF;
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-sm, 8px);
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
  padding: 6px;
  z-index: 1010;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  color: var(--text-primary, #0F172A);
  font-size: 13px;
  border-radius: 6px;
  background: none;
  border: none;
  width: 100%;
  text-align: right;
  cursor: pointer;
  font-weight: 500;
}
.dropdown-item:hover {
  background: #F1F5F9;
  color: var(--accent, #4F46E5);
}
.dropdown-item.logout {
  color: #EF4444;
}
.dropdown-item.logout:hover {
  background: #FEF2F2;
  color: #DC2626;
}

.dropdown-divider {
  height: 1px;
  background: var(--border, #E2E8F0);
  margin: 4px 0;
}

.collapse-btn {
  width: 100%;
  padding: 8px;
  background: #FFFFFF;
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-sm, 8px);
  color: var(--text-secondary, #64748B);
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}
.collapse-btn:hover {
  background: #F1F5F9;
  color: var(--text-primary, #0F172A);
}
.collapse-btn i {
  transition: transform 0.25s ease;
}
.collapse-btn i.rotated {
  transform: rotate(180deg);
}
</style>
