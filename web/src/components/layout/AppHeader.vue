<template>
  <header class="app-header">
    <div class="header-left">
      <button class="mobile-menu-btn" @click="appStore.toggleSidebar">
        <i class="pi pi-bars"></i>
      </button>

      <!-- Global Command Search Input (Stripe / Linear / Vercel style) -->
      <div class="global-search-trigger" @click="$emit('open-command-palette')">
        <i class="pi pi-search search-icon"></i>
        <span class="search-placeholder">بحث في العُقود، المستأجرين، الفواتير...</span>
        <div class="search-shortcut">
          <kbd>Ctrl</kbd>
          <kbd>K</kbd>
        </div>
      </div>
    </div>

    <div class="header-right">
      <!-- Quick Add Dropdown Button -->
      <div class="quick-add-wrapper">
        <button class="btn-primary quick-add-btn" @click="toggleQuickAdd">
          <i class="pi pi-plus"></i>
          <span>إضافة جديدة</span>
          <i class="pi pi-chevron-down text-xs"></i>
        </button>
        
        <transition name="fade">
          <div v-if="showQuickAddMenu" class="quick-add-dropdown">
            <router-link to="/contracts" class="dropdown-item" @click="showQuickAddMenu = false">
              <i class="pi pi-file text-amber"></i>
              <span>عقد إيجار جديد</span>
            </router-link>
            <router-link to="/tenants" class="dropdown-item" @click="showQuickAddMenu = false">
              <i class="pi pi-user-plus text-blue"></i>
              <span>مستأجر جديد</span>
            </router-link>
            <router-link to="/payments" class="dropdown-item" @click="showQuickAddMenu = false">
              <i class="pi pi-wallet text-green"></i>
              <span>تسجيل دفعة جديدة</span>
            </router-link>
            <router-link to="/maintenance" class="dropdown-item" @click="showQuickAddMenu = false">
              <i class="pi pi-wrench text-purple"></i>
              <span>طلب صيانة جديد</span>
            </router-link>
          </div>
        </transition>
      </div>

      <!-- Currency Switcher Badge -->
      <div class="currency-badge">
        <i class="pi pi-dollar"></i>
        <span>شيكل (₪)</span>
      </div>

      <!-- Notifications Bell Icon with Dropdown Overlay -->
      <div class="notification-wrapper">
        <button
          class="icon-btn notification-btn"
          @click="toggleNotifications"
          title="مركز الإشعارات والتنبيهات"
        >
          <i class="pi pi-bell"></i>
          <span class="badge-dot">3</span>
        </button>

        <transition name="fade">
          <div v-if="showNotificationsDropdown" class="notifications-dropdown">
            <div class="notifications-dropdown-header">
              <span class="dropdown-title">التنبيهات الفورية</span>
              <span class="unread-count">3 غير مقروءة</span>
            </div>
            
            <div class="notifications-dropdown-list">
              <div class="notif-item unread">
                <div class="notif-icon danger">
                  <i class="pi pi-exclamation-circle"></i>
                </div>
                <div class="notif-text">
                  <span class="notif-title">عقد ينتهي قريبًا</span>
                  <span class="notif-desc">شقة 401 للمستأجر خالد العلي تنتهي في 7 أيام</span>
                  <span class="notif-time">منذ ساعتين</span>
                </div>
              </div>

              <div class="notif-item unread">
                <div class="notif-icon warning">
                  <i class="pi pi-clock"></i>
                </div>
                <div class="notif-text">
                  <span class="notif-title">فاتورة متأخرة</span>
                  <span class="notif-desc">فاتورة بمبلغ 2,500 ₪ تجاوزت الاستحقاق</span>
                  <span class="notif-time">منذ 5 ساعات</span>
                </div>
              </div>

              <div class="notif-item">
                <div class="notif-icon info">
                  <i class="pi pi-wrench"></i>
                </div>
                <div class="notif-text">
                  <span class="notif-title">طلب صيانة جديد</span>
                  <span class="notif-desc">صيانة تكييف في برج الأمل شقة 203</span>
                  <span class="notif-time">منذ يوم واحد</span>
                </div>
              </div>
            </div>

            <div class="notifications-dropdown-footer">
              <router-link to="/notifications" class="view-all-link" @click="showNotificationsDropdown = false">
                عرض جميع الإشعارات
              </router-link>
            </div>
          </div>
        </transition>
      </div>

      <!-- User Profile Header & Logout -->
      <div class="user-header-menu">
        <div class="avatar-circle">
          <span>{{ userInitial }}</span>
        </div>
        <div class="user-text">
          <span class="name">{{ authStore.currentUser?.name || 'مدير النظام' }}</span>
          <span class="role">{{ roleTitle }}</span>
        </div>

        <button class="logout-icon-btn" @click="handleLogout" title="تسجيل الخروج">
          <i class="pi pi-sign-out"></i>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'

defineEmits(['open-command-palette'])

const router = useRouter()
const authStore = useAuthStore()
const appStore = useAppStore()

const showQuickAddMenu = ref(false)
const showNotificationsDropdown = ref(false)

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
  showNotificationsDropdown.value = false
}

function toggleNotifications() {
  showNotificationsDropdown.value = !showNotificationsDropdown.value
  showQuickAddMenu.value = false
}

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
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.mobile-menu-btn {
  display: none;
  background: none;
  border: 1px solid var(--border);
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  font-size: 1.1rem;
  color: var(--text-primary);
  cursor: pointer;
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
  width: 340px;
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
  gap: 16px;
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
}
.dropdown-item:hover {
  background: var(--bg-subtle, #F1F5F9);
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
  color: var(--secondary);
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
}
.icon-btn:hover {
  background: var(--bg-subtle, #F1F5F9);
  color: var(--text-primary);
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
  color: var(--accent);
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
  background: var(--bg-subtle, #F1F5F9);
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
  color: var(--accent);
  text-decoration: none;
}

/* User Header Menu */
.user-header-menu {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-right: 12px;
  border-right: 1px solid var(--border);
}
.avatar-circle {
  width: 36px;
  height: 36px;
  background: var(--accent-light);
  color: var(--accent);
  border: 1px solid var(--info-border, #BFDBFE);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
}
.user-text {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}
.name {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-primary);
}
.role {
  font-size: 11.5px;
  color: var(--text-secondary);
}

.logout-icon-btn {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.15s ease;
}
.logout-icon-btn:hover {
  background: var(--danger-bg);
  color: var(--danger);
  border-color: var(--danger-border, #FECACA);
}

@media (max-width: 900px) {
  .global-search-trigger {
    width: 200px;
  }
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .global-search-trigger {
    display: none;
  }
  .user-text {
    display: none;
  }
}
</style>
