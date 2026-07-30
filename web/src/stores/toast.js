import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let nextId = 1

  function show(options) {
    const id = nextId++
    const toast = {
      id,
      title: options.title || '',
      message: options.message || '',
      type: options.type || 'info', // 'success' | 'error' | 'warning' | 'info'
      duration: options.duration !== undefined ? options.duration : 5000,
      undoAction: options.undoAction || null,
      undoText: options.undoText || 'تراجع',
      createdAt: Date.now()
    }

    toasts.value.push(toast)

    if (toast.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, toast.duration)
    }

    return id
  }

  function success(message, options = {}) {
    return show({ message, type: 'success', title: 'تم بنجاح', ...options })
  }

  function error(message, options = {}) {
    return show({ message, type: 'error', title: 'خطأ', ...options })
  }

  function warning(message, options = {}) {
    return show({ message, type: 'warning', title: 'تنبيه', ...options })
  }

  function info(message, options = {}) {
    return show({ message, type: 'info', title: 'إشعار', ...options })
  }

  function remove(id) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  function triggerUndo(toast) {
    if (toast.undoAction && typeof toast.undoAction === 'function') {
      toast.undoAction()
    }
    remove(toast.id)
  }

  return {
    toasts,
    show,
    success,
    error,
    warning,
    info,
    remove,
    triggerUndo
  }
})
