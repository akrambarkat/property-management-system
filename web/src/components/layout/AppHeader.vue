<template>
  <header class="app-header">
    <!-- Left: sidebar toggle + global search (fixed) -->
    <div class="header-left">
      <button
        class="sidebar-toggle-btn"
        @click="appStore.toggleSidebar"
        :title="appStore.sidebarCollapsed ? 'توسيع القائمة' : 'طي القائمة'"
        :aria-label="appStore.sidebarCollapsed ? 'توسيع القائمة الجانبية' : 'طي القائمة الجانبية'"
        :aria-expanded="!appStore.sidebarCollapsed"
        aria-controls="sidebar"
      >
        <i class="pi pi-bars"></i>
      </button>

      <!-- Mobile: compact search button opens the command palette -->
      <button
        class="icon-btn mobile-search-btn"
        @click="$emit('open-command-palette')"
        aria-label="بحث"
        title="بحث"
      >
        <i class="pi pi-search"></i>
      </button>

      <!-- Global Command Search Input (Stripe / Linear / Vercel style) -->
      <div
        class="global-search-trigger"
        @click="$emit('open-command-palette')"
        role="button"
        tabindex="0"
        @keydown.enter="$emit('open-command-palette')"
      >
        <i class="pi pi-search search-icon"></i>
        <span class="search-placeholder">بحث في العُقود، المستأجرين، الفواتير...</span>
        <div class="search-shortcut">
          <kbd>Ctrl</kbd>
          <kbd>K</kbd>
        </div>
      </div>
    </div>

    <!-- Right: global controls (fixed) -->
    <div class="header-right">
      <!-- Quick Add Dropdown Button -->
      <div class="quick-add-wrapper">
        <button class="btn-primary quick-add-btn" @click="toggleQuickAdd" :aria-expanded="showQuickAddMenu">
          <i class="pi pi-plus"></i>
          <span>إضافة جديدة</span>
          <i class="pi pi-chevron-down text-xs"></i>
        </button>

        <transition name="fade">
          <div v-if="showQuickAddMenu" class="quick-add-dropdown">
            <div class="dropdown-grabber"></div>
            <button class="dropdown-item" @click="handleQuickAdd('/contracts')">
              <i class="pi pi-file text-amber"></i>
              <span>عقد إيجار جديد</span>
            </button>
            <button class="dropdown-item" @click="handleQuickAdd('/tenants')">
              <i class="pi pi-user-plus text-blue"></i>
              <span>مستأجر جديد</span>
            </button>
            <button class="dropdown-item" @click="handleQuickAdd('/payments')">
              <i class="pi pi-wallet text-green"></i>
              <span>تسجيل دفعة جديدة</span>
            </button>
            <button class="dropdown-item" @click="handleQuickAdd('/maintenance')">
              <i class="pi pi-wrench text-purple"></i>
              <span>طلب صيانة جديد</span>
            </button>
            <button class="dropdown-item" @click="handleQuickAdd('/sms/bulk')">
              <i class="pi pi-megaphone text-sky"></i>
              <span>إرسال رسالة SMS</span>
            </button>
            <button class="dropdown-item" @click="handleQuickAdd('/sms/templates')">
              <i class="pi pi-file-edit text-indigo"></i>
              <span>قالب رسالة جديد</span>
            </button>
          </div>
        </transition>
      </div>

      <!-- Currency Switcher Badge -->
      <div class="currency-badge">
        <i class="pi pi-dollar"></i>
        <span>شيكل (₪)</span>
      </div>

      <!-- Dark / Light Mode Toggle -->
      <button
        class="icon-btn theme-toggle-btn"
        @click="appStore.toggleDarkMode"
        :title="appStore.isDarkMode ? 'الوضع النهاري' : 'الوضع الليلي'"
        :aria-label="appStore.isDarkMode ? 'تفعيل الوضع النهاري' : 'تفعيل الوضع الليلي'"
      >
        <i :class="appStore.isDarkMode ? 'pi pi-sun' : 'pi pi-moon'"></i>
      </button>

      <!-- Notifications Bell -->
      <NotificationCenter />

      <!-- User Profile Dropdown (fixed) -->
      <div class="profile-wrapper">
        <button
          class="profile-trigger"
          @click="toggleProfileMenu"
          :aria-expanded="showProfileMenu"
          aria-haspopup="true"
          :aria-label="'قائمة الحساب'"
        >
          <span class="avatar-circle">{{ userInitial }}</span>
          <span class="profile-name">{{ userName }}</span>
          <i class="pi pi-chevron-down profile-chevron"></i>
        </button>

        <transition name="fade">
          <div v-if="showProfileMenu" class="profile-dropdown">
            <div class="dropdown-grabber"></div>
            <div class="profile-dropdown-header">
              <span class="avatar-circle">{{ userInitial }}</span>
              <div class="profile-dropdown-user">
                <span class="name">{{ userName }}</span>
                <span class="role">{{ roleTitle }}</span>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <router-link to="/settings" class="dropdown-item" @click="showProfileMenu = false">
              <i class="pi pi-user"></i>
              <span>الملف الشخصي</span>
            </router-link>
            <router-link to="/settings" class="dropdown-item" @click="showProfileMenu = false">
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
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'
import NotificationCenter from '@/components/notifications/NotificationCenter.vue'

defineEmits(['open-command-palette'])

const router = useRouter()
const authStore = useAuthStore()
const appStore = useAppStore()

const showQuickAddMenu = ref(false)
const showProfileMenu = ref(false)

const userName = computed(() => authStore.currentUser?.name || 'مدير النظام')

const userInitial = computed(() => {
  const name = authStore.currentUser?.name || 'م'
  return name.charAt(0).toUpperCase()
})

const roleTitle = computed(() => {
  const labels = { super_admin: 'مدير النظام', employee: 'موظف', guard: 'حارس' }
  return labels[authStore.currentUser?.role] || 'مدير النظام'
})

function toggleQuickAdd() {
  showQuickAddMenu.value = !showQuickAddMenu.value
  showProfileMenu.value = false
}

function handleQuickAdd(path) {
  closeAllMenus()
  router.push({ path, query: { new: '1' } })
}

function toggleProfileMenu() {
  showProfileMenu.value = !showProfileMenu.value
  showQuickAddMenu.value = false
}

function closeAllMenus() {
  showQuickAddMenu.value = false
  showProfileMenu.value = false
}

function handleClickOutside(e) {
  const header = document.querySelector('.app-header')
  if (header && !header.contains(e.target)) {
    closeAllMenus()
  }
}

function handleEscape(e) {
  if (e.key === 'Escape') closeAllMenus()
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
})

function handleLogout() {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>

<style scoped>
.app-header {
  height: var(--header-height);
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-sm);
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
  min-width: 0;
  flex: 1;
}

/* Sidebar collapse / expand toggle (hamburger) - always visible */
.sidebar-toggle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  background: var(--bg-subtle, #F8FAFC);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 1.05rem;
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.sidebar-toggle-btn:hover {
  background: var(--bg-hover, #F1F5F9);
  color: var(--accent);
  border-color: var(--border-hover);
}

/* Command Search Input Bar */
.global-search-trigger {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--bg-subtle, #F8FAFC);
  border: 1px solid var(--border);
  padding: 8px 14px;
  border-radius: var(--radius-full);
  width: 360px;
  max-width: 50vw;
  cursor: pointer;
  transition: all 0.2s ease;
}
.global-search-trigger:hover {
  background: var(--bg-surface, #FFFFFF);
  border-color: var(--accent);
  box-shadow: var(--shadow-sm);
}
.search-icon {
  color: var(--text-muted);
  font-size: 0.95rem;
}
.search-placeholder {
  font-size: 13px;
  color: var(--text-muted);
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.search-shortcut {
  display: flex;
  gap: 3px;
}
.search-shortcut kbd {
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border);
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 10.5px;
  color: var(--text-secondary);
  font-weight: 600;
}

/* Header Right */
.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.quick-add-wrapper {
  position: relative;
}
.quick-add-btn {
  padding: 8px 14px !important;
  font-size: 13px !important;
  border-radius: var(--radius-full) !important;
}
.text-xs {
  font-size: 10px;
}

.quick-add-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-lg);
  padding: 6px;
  width: 200px;
  z-index: 1020;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  color: var(--text-primary);
  font-size: 13px;
  border-radius: var(--radius-sm);
  text-decoration: none;
  transition: background 0.15s ease;
  background: none;
  border: none;
  width: 100%;
  text-align: right;
  cursor: pointer;
  font-weight: 500;
}
.dropdown-item:hover {
  background: var(--bg-subtle, #F1F5F9);
}
.dropdown-item.logout {
  color: var(--danger);
}
.dropdown-item.logout:hover {
  background: var(--danger-bg);
  color: var(--danger);
}
.dropdown-divider {
  height: 1px;
  background: var(--border);
  margin: 4px 0;
}

.currency-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-subtle, #F1F5F9);
  border: 1px solid var(--border);
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text-secondary);
}
.currency-badge i {
  color: var(--text-muted);
}

.notification-wrapper {
  position: relative;
}
.icon-btn {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--bg-subtle, #F8FAFC);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  position: relative;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.icon-btn:hover {
  background: var(--bg-hover, #F1F5F9);
  color: var(--text-primary);
}
.theme-toggle-btn {
  border-radius: var(--radius-sm);
}
.theme-toggle-btn i {
  font-size: 1rem;
}

.badge-dot {
  position: absolute;
  top: 2px;
  left: 2px;
  background: var(--danger);
  color: #FFFFFF;
  font-size: 10px;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--bg-surface, #FFFFFF);
}

/* Notifications Overlay Dropdown */
.notifications-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 8px;
  width: 340px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  z-index: 1030;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.notifications-dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--bg-subtle, #F8FAFC);
  border-bottom: 1px solid var(--border);
}
.dropdown-title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
}
.unread-count {
  font-size: 11.5px;
  color: var(--accent-hover);
  font-weight: 600;
}

.notifications-dropdown-list {
  display: flex;
  flex-direction: column;
  max-height: 280px;
  overflow-y: auto;
}
.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-light);
  transition: background 0.15s ease;
}
.notif-item:last-child {
  border-bottom: none;
}
.notif-item.unread {
  background: var(--bg-subtle, #F8FAFC);
}
.notif-item:hover {
  background: var(--bg-hover, #F1F5F9);
}

.notif-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}
.notif-icon.danger { background: var(--danger-bg); color: var(--danger); }
.notif-icon.warning { background: var(--warning-bg); color: var(--warning); }
.notif-icon.info { background: var(--info-bg); color: var(--info); }

.notif-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.notif-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}
.notif-desc {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.3;
}
.notif-time {
  font-size: 10.5px;
  color: var(--text-muted);
  margin-top: 2px;
}

.notifications-dropdown-footer {
  padding: 10px;
  background: var(--bg-subtle, #F8FAFC);
  border-top: 1px solid var(--border);
  text-align: center;
}
.view-all-link {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--accent-hover);
  text-decoration: none;
}

/* User Profile Dropdown */
.profile-wrapper {
  position: relative;
}
.profile-trigger {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 10px 4px 6px;
  background: var(--bg-subtle, #F8FAFC);
  border: 1px solid var(--border);
  border-radius: var(--radius-full);
  cursor: pointer;
  transition: all 0.15s ease;
}
.profile-trigger:hover {
  background: var(--bg-surface, #FFFFFF);
  border-color: var(--border-hover);
  box-shadow: var(--shadow-xs);
}
.avatar-circle {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, var(--accent) 0%, #3730A3 100%);
  color: #FFFFFF;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
}
.profile-name {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
}
.profile-chevron {
  font-size: 10px;
  color: var(--text-muted);
}

.profile-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 8px;
  width: 230px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  z-index: 1030;
  padding: 6px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.profile-dropdown-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-bottom: 1px solid var(--border-light);
  margin-bottom: 4px;
}
.profile-dropdown-user {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}
.profile-dropdown-user .name {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
}
.profile-dropdown-user .role {
  font-size: 11.5px;
  color: var(--text-secondary);
}

@media (max-width: 900px) {
  .global-search-trigger {
    width: 200px;
  }
}

.mobile-search-btn {
  display: none;
}

.dropdown-grabber {
  display: none;
}

@media (max-width: 768px) {
  .app-header {
    padding: 0 12px;
    gap: 8px;
  }
  .mobile-search-btn {
    display: flex;
  }
  .global-search-trigger {
    display: none;
  }
  .currency-badge {
    display: none;
  }
  .quick-add-btn span,
  .quick-add-btn .text-xs {
    display: none;
  }
  .quick-add-btn {
    width: 40px !important;
    padding: 0 !important;
    justify-content: center;
  }
  .profile-name {
    display: none;
  }
  .profile-trigger {
    padding: 4px;
  }
  .profile-trigger .avatar-circle {
    width: 36px;
    height: 36px;
  }

  /* 44px touch targets */
  .sidebar-toggle-btn,
  .icon-btn,
  .quick-add-btn {
    width: 44px !important;
    height: 44px;
  }

  /* Convert header dropdowns into bottom sheets */
  .quick-add-dropdown,
  .profile-dropdown {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    top: auto;
    width: 100%;
    max-width: 100%;
    margin: 0;
    border-radius: 18px 18px 0 0;
    padding: 8px 14px calc(12px + env(safe-area-inset-bottom, 0px));
    max-height: 66vh;
    overflow-y: auto;
    z-index: 1060;
    box-shadow: var(--shadow-lg);
  }
  .dropdown-grabber {
    display: block;
    width: 40px;
    height: 4px;
    border-radius: 4px;
    background: var(--border, #E2E8F0);
    margin: 4px auto 10px;
    flex-shrink: 0;
  }
  .quick-add-dropdown .dropdown-item,
  .profile-dropdown .dropdown-item {
    min-height: 46px;
    font-size: 14px;
  }
}
</style>
