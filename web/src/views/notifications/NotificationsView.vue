<template>
  <div class="page-view">
    <EnterpriseTable
      :value="store.notifications"
      entity="notifications"
      :loading="store.loading"
      searchPlaceholder="بحث في الإشعارات..."
      emptyTitle="لا توجد إشعارات"
      emptySubtitle="لم يتم العثور على إشعارات تطابق خيارات التصفية والبحث"
      :columns="tableColumns"
      :selectable="true"
      @refresh="store.fetchNotifications()"
    >
      <template #filters>
        <Select
          v-model="store.filters.is_read"
          :options="readFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="حالة القراءة"
          showClear
          @change="store.fetchNotifications()"
          class="filter-select"
        />
        <Select
          v-model="store.filters.type"
          :options="typeFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الأنواع"
          showClear
          @change="store.fetchNotifications()"
          class="filter-select"
        />
        <Select
          v-model="store.filters.priority"
          :options="priorityFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="جميع الأولويات"
          showClear
          @change="store.fetchNotifications()"
          class="filter-select"
        />
      </template>

      <template #actions>
        <button class="btn-secondary" @click="store.markAllAsRead()" :disabled="!store.hasUnread">
          <i class="pi pi-check-double"></i> تحديد الكل كمقروء
        </button>
      </template>

      <template #bulk-actions>
        <button class="btn-sm btn-secondary" @click="store.bulkAction('read')">
          <i class="pi pi-check"></i> تحديد كمقروء
        </button>
        <button class="btn-sm btn-secondary" @click="store.bulkAction('archive')">
          <i class="pi pi-archive"></i> أرشفة
        </button>
        <button class="btn-sm btn-danger" @click="confirmBulkDelete">
          <i class="pi pi-trash"></i> حذف
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column selectionMode="multiple" style="width: 40px" />

        <Column v-if="!hiddenColumns.includes('title')" field="title" header="الإشعار" sortable>
          <template #body="slotProps">
            <div class="notif-cell" :class="{ unread: !slotProps.data.is_read }">
              <div class="notif-icon" :style="{ background: getPriorityBg(slotProps.data.priority) }">
                <i :class="getTypeIcon(slotProps.data.type)" :style="{ color: getPriorityColor(slotProps.data.priority) }"></i>
              </div>
              <div class="notif-info">
                <span class="notif-title">{{ slotProps.data.title }}</span>
                <span class="notif-message">{{ slotProps.data.message }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('type')" field="type" header="النوع" sortable>
          <template #body="slotProps">
            <span class="type-badge" :class="'type-' + slotProps.data.type">
              {{ typeLabels[slotProps.data.type] || slotProps.data.type }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('priority')" field="priority" header="الأولوية" sortable>
          <template #body="slotProps">
            <span class="priority-badge" :class="'priority-' + slotProps.data.priority">
              {{ priorityLabels[slotProps.data.priority] || slotProps.data.priority }}
            </span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('created_at')" field="created_at" header="التاريخ" sortable>
          <template #body="slotProps">
            <span class="date-text">{{ formatRelativeTime(slotProps.data.created_at) }}</span>
          </template>
        </Column>

        <Column v-if="!hiddenColumns.includes('is_read')" field="is_read" header="الحالة">
          <template #body="slotProps">
            <span class="status-pill" :class="slotProps.data.is_read ? 'read' : 'unread'">
              {{ slotProps.data.is_read ? 'مقروء' : 'غير مقروء' }}
            </span>
          </template>
        </Column>

        <Column header="الإجراءات" style="width: 80px; text-align: center;" frozen alignFrozen="right">
          <template #body="slotProps">
            <TableActionMenu :items="getRowActions(slotProps.data)" />
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <!-- Pagination -->
    <div v-if="store.pagination.last_page > 1" class="pagination-wrapper">
      <Button
        icon="pi pi-angle-right"
        :disabled="store.pagination.current_page <= 1"
        @click="store.fetchNotifications(store.pagination.current_page - 1)"
        text
        rounded
      />
      <span class="page-info">
        صفحة {{ store.pagination.current_page }} من {{ store.pagination.last_page }}
      </span>
      <Button
        icon="pi pi-angle-left"
        :disabled="store.pagination.current_page >= store.pagination.last_page"
        @click="store.fetchNotifications(store.pagination.current_page + 1)"
        text
        rounded
      />
    </div>

    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="تأكيد الحذف"
      message="هل أنت متأكد من حذف الإشعارات المحددة؟"
      variant="danger"
      confirmText="تأكيد الحذف"
      @confirm="executeBulkDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications'
import { useToastStore } from '@/stores/toast'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Button from 'primevue/button'

const store = useNotificationsStore()
const toast = useToastStore()
const router = useRouter()
const showDeleteModal = ref(false)

const tableColumns = [
  { field: 'title', header: 'الإشعار' },
  { field: 'type', header: 'النوع', tabletHidden: true },
  { field: 'priority', header: 'الأولوية', tabletHidden: true },
  { field: 'created_at', header: 'التاريخ' },
  { field: 'is_read', header: 'الحالة', tabletHidden: true },
]

const typeLabels = {
  contract: 'عقود', invoice: 'فواتير', tenant: 'مستأجرين',
  maintenance: 'صيانة', sms: 'رسائل', building: 'مباني', system: 'النظام',
}

const priorityLabels = { low: 'منخفضة', medium: 'متوسطة', high: 'عالية', critical: 'حرجة' }

const readFilterOptions = [
  { label: 'غير مقروءة', value: false },
  { label: 'مقروءة', value: true },
]

const typeFilterOptions = [
  { label: 'عقود', value: 'contract' },
  { label: 'فواتير', value: 'invoice' },
  { label: 'مستأجرين', value: 'tenant' },
  { label: 'صيانة', value: 'maintenance' },
  { label: 'رسائل', value: 'sms' },
  { label: 'مباني', value: 'building' },
  { label: 'النظام', value: 'system' },
]

const priorityFilterOptions = [
  { label: 'منخفضة', value: 'low' },
  { label: 'متوسطة', value: 'medium' },
  { label: 'عالية', value: 'high' },
  { label: 'حرجة', value: 'critical' },
]

function getPriorityColor(p) {
  return { low: '#6b7280', medium: '#f59e0b', high: '#f97316', critical: '#ef4444' }[p] || '#6b7280'
}

function getPriorityBg(p) {
  return { low: '#f3f4f6', medium: '#fef3c7', high: '#ffedd5', critical: '#fee2e2' }[p] || '#f3f4f6'
}

function getTypeIcon(t) {
  return { contract: 'pi pi-file', invoice: 'pi pi-dollar', tenant: 'pi pi-user', maintenance: 'pi pi-wrench', sms: 'pi pi-comments', building: 'pi pi-building', system: 'pi pi-cog' }[t] || 'pi pi-bell'
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
  return date.toLocaleDateString('ar-EG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function getRowActions(row) {
  const actions = []
  if (!row.is_read) {
    actions.push({ label: 'تحديد كمقروء', icon: 'pi pi-check', command: () => store.markAsRead(row.id) })
  } else {
    actions.push({ label: 'تحديد كغير مقروء', icon: 'pi pi-eye-slash', command: () => markAsUnread(row.id) })
  }
  actions.push({ label: 'أرشفة', icon: 'pi pi-archive', command: () => store.archiveNotification(row.id) })
  actions.push({ label: 'حذف', icon: 'pi pi-trash', danger: true, command: () => confirmDelete(row) })
  if (row.action_url) {
    actions.push({ label: 'عرض التفاصيل', icon: 'pi pi-arrow-left', command: () => router.push(row.action_url) })
  }
  return actions
}

async function markAsUnread(id) {
  try {
    const { default: api } = await import('@/services/api')
    await api.patch(`/notifications/${id}/unread`)
    store.fetchNotifications()
    store.fetchUnreadCount()
  } catch (err) {
    toast.error('خطأ في التحديث')
  }
}

function confirmDelete(item) {
  if (confirm(`هل أنت متأكد من حذف الإشعار "${item.title}"؟`)) {
    store.deleteNotification(item.id)
    toast.success('تم حذف الإشعار')
  }
}

function confirmBulkDelete() {
  showDeleteModal.value = true
}

async function executeBulkDelete() {
  await store.bulkAction('delete')
  showDeleteModal.value = false
  toast.success('تم حذف الإشعارات المحددة')
}

onMounted(() => {
  store.fetchNotifications()
  store.fetchUnreadCount()
})
</script>

<style scoped>
.filter-select {
  width: 160px !important;
}

.notif-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.notif-cell.unread .notif-title {
  font-weight: 700;
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

.notif-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.notif-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}

.notif-message {
  font-size: 12px;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 350px;
}

.type-badge {
  padding: 3px 10px;
  border-radius: var(--radius-full);
  font-size: 11.5px;
  font-weight: 600;
}

.type-contract { background: var(--info-bg); color: var(--info-contrast); }
.type-invoice { background: var(--success-bg, #d1fae5); color: #065f46; }
.type-tenant { background: var(--accent-light); color: var(--accent-hover); }
.type-maintenance { background: var(--warning-bg); color: var(--warning-contrast); }
.type-sms { background: #ede9fe; color: #5b21b6; }
.type-building { background: var(--bg-subtle); color: var(--text-secondary); }
.type-system { background: #f3f4f6; color: #374151; }

.priority-badge {
  padding: 3px 10px;
  border-radius: var(--radius-full);
  font-size: 11.5px;
  font-weight: 600;
}

.priority-low { background: #f3f4f6; color: #6b7280; }
.priority-medium { background: #fef3c7; color: #92400e; }
.priority-high { background: #ffedd5; color: #9a3412; }
.priority-critical { background: #fee2e2; color: #991b1b; }

.status-pill {
  padding: 3px 10px;
  border-radius: var(--radius-full);
  font-size: 11.5px;
  font-weight: 600;
}

.status-pill.read { background: #f3f4f6; color: #6b7280; }
.status-pill.unread { background: var(--accent-light); color: var(--accent-hover); }

.date-text {
  font-size: 12.5px;
  color: var(--text-secondary);
}

.pagination-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
}

.page-info {
  font-size: 13px;
  color: var(--text-secondary);
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.btn-danger {
  background: var(--danger);
  color: #fff;
  border: none;
  padding: 6px 12px;
  border-radius: var(--radius-xs);
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
}

.btn-danger:hover {
  background: var(--danger-hover);
}
</style>
