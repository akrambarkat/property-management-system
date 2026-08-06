<template>
  <div class="notification-center" ref="dropdownRef">
    <button class="bell-btn" @click="toggleDropdown" :title="store.hasUnread ? `${store.unreadCount} إشعارات غير مقروءة` : 'الإشعارات'">
      <i class="pi pi-bell"></i>
      <span v-if="store.hasUnread" class="badge-dot" :class="animated ? 'badge-animate' : ''">
        {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
      </span>
    </button>

    <transition name="dropdown-fade">
      <div v-if="isOpen" class="dropdown-panel">
        <div class="dropdown-header">
          <div class="header-title">
            <h3>الإشعارات</h3>
            <span v-if="store.hasUnread" class="unread-badge">{{ store.unreadCount }}</span>
          </div>
          <div class="header-actions">
            <button v-if="store.hasUnread" class="btn-link" @click="markAllRead">تحديد الكل كمقروء</button>
            <router-link to="/notifications" class="btn-link" @click="closeDropdown">عرض الكل</router-link>
          </div>
        </div>

        <div class="dropdown-body">
          <div v-if="loading" class="dropdown-loading">
            <div v-for="i in 4" :key="i" class="skeleton-item">
              <div class="skel-icon"></div>
              <div class="skel-content">
                <div class="skel-title"></div>
                <div class="skel-text"></div>
              </div>
            </div>
          </div>

          <div v-else-if="store.latestNotifications.length === 0" class="empty-state">
            <i class="pi pi-bell-slash"></i>
            <p>لا توجد إشعارات</p>
          </div>

          <div v-else class="notification-list">
            <div
              v-for="n in store.latestNotifications"
              :key="n.id"
              class="notification-item"
              :class="{ unread: !n.is_read }"
              @click="handleClick(n)"
            >
              <div class="notif-icon" :style="{ background: getPriorityBg(n.priority) }">
                <i :class="getTypeIcon(n.type)" :style="{ color: getPriorityColor(n.priority) }"></i>
              </div>
              <div class="notif-content">
                <div class="notif-header">
                  <span class="notif-title">{{ n.title }}</span>
                  <span v-if="!n.is_read" class="unread-dot"></span>
                </div>
                <p class="notif-message">{{ n.message }}</p>
                <span class="notif-time">{{ formatRelativeTime(n.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="dropdown-footer">
          <router-link to="/notifications" class="footer-link" @click="closeDropdown">
            <i class="pi pi-list"></i>
            عرض جميع الإشعارات
          </router-link>
        </div>
      </div>
    </transition>

    <transition name="dropdown-fade">
      <div v-if="isOpen" class="dropdown-backdrop" @click="closeDropdown"></div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications'

const store = useNotificationsStore()
const router = useRouter()
const isOpen = ref(false)
const loading = ref(false)
const animated = ref(false)
const dropdownRef = ref(null)

let pollInterval = null

function toggleDropdown() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    loadNotifications()
  }
}

function closeDropdown() {
  isOpen.value = false
}

async function loadNotifications() {
  loading.value = true
  await store.fetchLatest()
  loading.value = false
}

async function markAllRead() {
  await store.markAllAsRead()
}

function handleClick(notification) {
  if (!notification.is_read) {
    store.markAsRead(notification.id)
  }
  if (notification.action_url) {
    router.push(notification.action_url)
  }
  closeDropdown()
}

function getPriorityColor(priority) {
  return { low: '#6b7280', medium: '#f59e0b', high: '#f97316', critical: '#ef4444' }[priority] || '#6b7280'
}

function getPriorityBg(priority) {
  return { low: '#f3f4f6', medium: '#fef3c7', high: '#ffedd5', critical: '#fee2e2' }[priority] || '#f3f4f6'
}

function getTypeIcon(type) {
  return {
    contract: 'pi pi-file',
    invoice: 'pi pi-dollar',
    tenant: 'pi pi-user',
    maintenance: 'pi pi-wrench',
    sms: 'pi pi-comments',
    building: 'pi pi-building',
    system: 'pi pi-cog',
  }[type] || 'pi pi-bell'
}

function formatRelativeTime(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diff = Math.floor((now - date) / 1000)

  if (diff < 60) return 'الآن'
  if (diff < 3600) return `منذ ${Math.floor(diff / 60)} دقيقة`
  if (diff < 86400) return `منذ ${Math.floor(diff / 3600)} ساعة`
  if (diff < 604800) return `منذ ${Math.floor(diff / 86400)} يوم`

  return date.toLocaleDateString('ar-EG', { day: 'numeric', month: 'short' })
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    closeDropdown()
  }
}

function handleEscape(e) {
  if (e.key === 'Escape') closeDropdown()
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
  store.fetchUnreadCount()
  pollInterval = setInterval(() => {
    store.fetchUnreadCount()
  }, 60000)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.notification-center {
  position: relative;
}

.bell-btn {
  position: relative;
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bell-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.bell-btn i {
  font-size: 1.15rem;
}

.badge-dot {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 18px;
  height: 18px;
  background: var(--danger);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  line-height: 1;
}

.badge-animate {
  animation: badgePulse 2s infinite;
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.dropdown-panel {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 400px;
  max-height: 520px;
  background: var(--surface-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
}

.header-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-title h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}

.unread-badge {
  background: var(--danger);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  line-height: 1.3;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-link {
  background: none;
  border: none;
  color: var(--accent);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  padding: 4px 8px;
  border-radius: var(--radius-xs);
  transition: background 0.15s ease;
}

.btn-link:hover {
  background: var(--accent-light);
}

.dropdown-body {
  flex: 1;
  overflow-y: auto;
  max-height: 360px;
}

.dropdown-loading {
  padding: 12px;
}

.skeleton-item {
  display: flex;
  gap: 12px;
  padding: 10px 4px;
}

.skel-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--bg-subtle);
  animation: pulse 1.5s infinite;
}

.skel-content {
  flex: 1;
}

.skel-title {
  height: 13px;
  width: 60%;
  background: var(--bg-subtle);
  border-radius: 4px;
  margin-bottom: 6px;
  animation: pulse 1.5s infinite;
}

.skel-text {
  height: 11px;
  width: 85%;
  background: var(--bg-subtle);
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: var(--text-muted);
  gap: 8px;
}

.empty-state i {
  font-size: 2.2rem;
  opacity: 0.4;
}

.empty-state p {
  margin: 0;
  font-size: 13.5px;
}

.notification-list {
  padding: 4px 0;
}

.notification-item {
  display: flex;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.15s ease;
  border-bottom: 1px solid var(--border-light);
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item:hover {
  background: var(--bg-hover);
}

.notification-item.unread {
  background: var(--accent-light);
}

.notification-item.unread:hover {
  background: var(--accent-light);
  filter: brightness(0.97);
}

.notif-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 0.9rem;
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 2px;
}

.notif-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
}

.notif-message {
  margin: 0;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notif-time {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 3px;
  display: block;
}

.dropdown-footer {
  border-top: 1px solid var(--border);
  padding: 10px 16px;
}

.footer-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--accent);
  text-decoration: none;
  padding: 6px;
  border-radius: var(--radius-xs);
  transition: background 0.15s ease;
}

.footer-link:hover {
  background: var(--accent-light);
}

.dropdown-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 999;
}

@media (min-width: 769px) {
  .dropdown-backdrop {
    display: none;
  }
}

@media (max-width: 768px) {
  .bell-btn {
    width: 44px;
    height: 44px;
  }
  .dropdown-panel {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    top: auto;
    width: 100%;
    max-width: 100%;
    max-height: 70vh;
    border-radius: 18px 18px 0 0;
    z-index: 1000;
    padding-bottom: env(safe-area-inset-bottom, 0px);
  }
  .dropdown-body {
    max-height: none;
  }
  .dropdown-fade-enter-from,
  .dropdown-fade-leave-to {
    opacity: 0;
    transform: translateY(100%);
  }
  .notification-item {
    padding: 14px 16px;
  }
}
</style>
