import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const latestNotifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const filters = ref({ is_read: null, type: null, priority: null, search: '', from: null, to: null })
  const selectedIds = ref([])
  const settings = ref([])
  const settingsLoading = ref(false)

  const hasUnread = computed(() => unreadCount.value > 0)

  async function fetchLatest() {
    try {
      const { data } = await api.get('/notifications/latest')
      latestNotifications.value = data.data || []
    } catch (err) {
      console.error('Failed to fetch latest notifications', err)
    }
  }

  async function fetchUnreadCount() {
    try {
      const { data } = await api.get('/notifications/unread-count')
      unreadCount.value = data.data?.count || 0
    } catch (err) {
      console.error('Failed to fetch unread count', err)
    }
  }

  async function fetchNotifications(page = 1) {
    loading.value = true
    try {
      const params = { page, per_page: pagination.value.per_page }
      if (filters.value.is_read !== null) params.is_read = filters.value.is_read
      if (filters.value.type) params.type = filters.value.type
      if (filters.value.priority) params.priority = filters.value.priority
      if (filters.value.search) params.search = filters.value.search
      if (filters.value.from) params.from = filters.value.from
      if (filters.value.to) params.to = filters.value.to

      const { data } = await api.get('/notifications', { params })
      notifications.value = data.data?.data || []
      pagination.value = {
        current_page: data.data?.current_page || 1,
        last_page: data.data?.last_page || 1,
        per_page: data.data?.per_page || 15,
        total: data.data?.total || 0,
      }
    } catch (err) {
      console.error('Failed to fetch notifications', err)
    } finally {
      loading.value = false
    }
  }

  async function markAsRead(id) {
    try {
      await api.patch(`/notifications/${id}/read`)
      const n = latestNotifications.value.find(x => x.id === id)
      if (n) n.is_read = true
      const n2 = notifications.value.find(x => x.id === id)
      if (n2) n2.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (err) {
      console.error('Failed to mark as read', err)
    }
  }

  async function markAllAsRead() {
    try {
      await api.post('/notifications/mark-all-read')
      latestNotifications.value.forEach(n => { n.is_read = true })
      notifications.value.forEach(n => { n.is_read = true })
      unreadCount.value = 0
    } catch (err) {
      console.error('Failed to mark all as read', err)
    }
  }

  async function archiveNotification(id) {
    try {
      await api.patch(`/notifications/${id}/archive`)
      latestNotifications.value = latestNotifications.value.filter(n => n.id !== id)
      notifications.value = notifications.value.filter(n => n.id !== id)
    } catch (err) {
      console.error('Failed to archive', err)
    }
  }

  async function deleteNotification(id) {
    try {
      await api.delete(`/notifications/${id}`)
      latestNotifications.value = latestNotifications.value.filter(n => n.id !== id)
      notifications.value = notifications.value.filter(n => n.id !== id)
    } catch (err) {
      console.error('Failed to delete', err)
    }
  }

  async function bulkAction(action) {
    if (selectedIds.value.length === 0) return
    try {
      await api.post('/notifications/bulk', { ids: selectedIds.value, action })
      selectedIds.value = []
      await fetchNotifications()
      await fetchUnreadCount()
    } catch (err) {
      console.error('Bulk action failed', err)
    }
  }

  async function fetchSettings() {
    settingsLoading.value = true
    try {
      const { data } = await api.get('/notifications/settings')
      settings.value = data.data || []
    } catch (err) {
      console.error('Failed to fetch settings', err)
    } finally {
      settingsLoading.value = false
    }
  }

  async function updateSetting(type, values) {
    try {
      await api.put(`/notifications/settings/${type}`, values)
      const idx = settings.value.findIndex(s => s.type === type)
      if (idx > -1) {
        settings.value[idx] = { ...settings.value[idx], ...values }
      }
    } catch (err) {
      console.error('Failed to update setting', err)
    }
  }

  async function checkNotifications() {
    try {
      await api.post('/notifications/check')
      await fetchUnreadCount()
      await fetchLatest()
    } catch (err) {
      console.error('Failed to check notifications', err)
    }
  }

  function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id)
    if (idx > -1) {
      selectedIds.value.splice(idx, 1)
    } else {
      selectedIds.value.push(id)
    }
  }

  function selectAll() {
    selectedIds.value = notifications.value.map(n => n.id)
  }

  function clearSelection() {
    selectedIds.value = []
  }

  return {
    notifications, latestNotifications, unreadCount, loading,
    pagination, filters, selectedIds, settings, settingsLoading,
    hasUnread,
    fetchLatest, fetchUnreadCount, fetchNotifications,
    markAsRead, markAllAsRead, archiveNotification, deleteNotification,
    bulkAction, fetchSettings, updateSetting, checkNotifications,
    toggleSelect, selectAll, clearSelection,
  }
})
