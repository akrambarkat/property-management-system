<template>
  <div class="notifications-page page-view">
    <div class="page-header">
      <div>
        <h2 class="page-title">مركز الإشعارات والتنبيهات</h2>
        <p class="page-subtitle">استعرض التنبيهات المتعلقة بالعقود، الفواتير المتأخرة، وطلبات الصيانة</p>
      </div>
      <button class="btn-secondary" @click="markAllAsRead">
        <i class="pi pi-check-square"></i> تحديد الكل كتم قراءته
      </button>
    </div>

    <div class="notifications-list card">
      <div
        v-for="item in notifications"
        :key="item.id"
        class="notification-item"
        :class="{ unread: !item.is_read }"
      >
        <div class="icon-box" :class="item.type">
          <i :class="item.icon"></i>
        </div>

        <div class="content">
          <div class="title-row">
            <h4 class="item-title">{{ item.title }}</h4>
            <span class="item-time">{{ item.time }}</span>
          </div>
          <p class="item-desc">{{ item.description }}</p>
        </div>

        <div class="actions">
          <router-link :to="item.link" class="btn-primary-sm">عرض التفاصيل</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const notifications = ref([
  {
    id: 1,
    type: 'danger',
    icon: 'pi pi-exclamation-circle',
    title: 'عقد ينتهي قريبًا',
    description: 'العقد رقم #CNT-2024-089 للمستأجر محمد أحمد ينتهي خلال 7 أيام.',
    time: 'منذ ساعتين',
    is_read: false,
    link: '/contracts'
  },
  {
    id: 2,
    type: 'warning',
    icon: 'pi pi-clock',
    title: 'فاتورة متأخرة الدفع',
    description: 'الفاتورة رقم #INV-1094 بمبلغ 2,500 شيكل تجاوزت تاريخ الاستحقاق.',
    time: 'منذ 5 ساعات',
    is_read: false,
    link: '/invoices'
  },
  {
    id: 3,
    type: 'info',
    icon: 'pi pi-wrench',
    title: 'طلب صيانة جديد',
    description: 'تم تسجيل طلب صيانة جديد (إصلاح تكييف) في المبنى أ - شقة 104.',
    time: 'منذ يوم واحد',
    is_read: true,
    link: '/maintenance'
  }
])

function markAllAsRead() {
  notifications.value.forEach(item => (item.is_read = true))
}
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.page-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
}
.page-subtitle {
  font-size: 13.5px;
  color: var(--text-secondary);
}

.notifications-list {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.notification-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 24px;
  border-bottom: 1px solid var(--border-light);
  transition: background 0.15s ease;
}
.notification-item:last-child {
  border-bottom: none;
}
.notification-item.unread {
  background: var(--bg-subtle);
}
.notification-item:hover {
  background: var(--bg-hover);
}

.icon-box {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}
.icon-box.danger {
  background: var(--danger-bg);
  color: var(--danger);
  border: 1px solid var(--danger-border);
}
.icon-box.warning {
  background: var(--warning-bg);
  color: var(--warning);
  border: 1px solid var(--warning-border);
}
.icon-box.info {
  background: var(--info-bg);
  color: var(--info);
  border: 1px solid var(--info-border);
}

.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.item-title {
  font-size: 14.5px;
  font-weight: 600;
  color: var(--text-primary);
}
.item-time {
  font-size: 12px;
  color: var(--text-muted);
}

.item-desc {
  font-size: 13.5px;
  color: var(--text-secondary);
}

.btn-primary-sm {
  font-size: 12.5px;
  padding: 6px 14px;
  background: var(--accent);
  color: var(--text-on-accent);
  border-radius: var(--radius-sm);
  font-weight: 500;
  text-decoration: none;
}
.btn-primary-sm:hover {
  background: var(--accent-hover);
}
</style>
