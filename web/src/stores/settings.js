import { defineStore } from 'pinia'
import { reactive, ref, computed } from 'vue'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'

const GROUP_DEFAULTS = {
  general: {},
  company: {},
  sms: {},
  notifications: {},
  invoices: {},
  contracts: {},
  appearance: {},
  security: {},
  backup: {},
  system: {},
  users: {}
}

export const useSettingsStore = defineStore('settings', () => {
  const toast = useToastStore()

  const groups = reactive({})
  const pristine = reactive({})
  const loading = ref(false)
  const loaded = ref(false)
  const savingGroup = ref(null)
  const lastSavedAt = ref(null)
  const currencies = ref([])

  const dirtyGroups = computed(() =>
    Object.keys(groups).filter(g => isDirty(g))
  )

  function isDirty(group) {
    if (!pristine[group] || !groups[group]) return false
    const snapshot = Object.fromEntries(
      Object.entries(pristine[group]).map(([k, v]) => [k, normalize(v)])
    )
    const current = Object.fromEntries(
      Object.entries(groups[group]).map(([k, v]) => [k, normalize(v)])
    )
    return JSON.stringify(snapshot) !== JSON.stringify(current)
  }

  function normalize(v) {
    if (v === null || v === undefined) return ''
    if (typeof v === 'boolean') return v
    if (typeof v === 'number') return v
    return String(v)
  }

  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await api.get('/settings')
      const payload = data.data || {}
      Object.keys(GROUP_DEFAULTS).forEach(g => {
        const value = payload[g] ?? {}
        groups[g] = reactive({ ...value })
        pristine[g] = { ...value }
      })
      if (Array.isArray(payload.currencies)) currencies.value = payload.currencies
      loaded.value = true
    } catch (err) {
      toast.error('تعذر تحميل الإعدادات: ' + (err.response?.data?.message || err.message))
    } finally {
      loading.value = false
    }
  }

  function setValue(group, key, value) {
    groups[group][key] = value
  }

  async function saveGroup(group) {
    savingGroup.value = group
    try {
      const { data } = await api.put('/settings', { group, values: groups[group] })
      pristine[group] = { ...groups[group] }
      lastSavedAt.value = new Date()
      if (group === 'general' && groups.general.app_name) {
        localStorage.setItem('app_name', groups.general.app_name)
      }
      toast.success(data.message || 'تم حفظ الإعدادات بنجاح')
      return data
    } catch (err) {
      const errors = err.response?.data?.errors
      if (errors) {
        Object.keys(errors).forEach(k => toast.error(errors[k][0]))
      } else {
        toast.error(err.response?.data?.message || 'تعذر حفظ الإعدادات')
      }
      throw err
    } finally {
      savingGroup.value = null
    }
  }

  async function fetchCurrencies() {
    try {
      const { data } = await api.get('/currencies')
      currencies.value = data.data
    } catch (err) {
      toast.error('تعذر تحميل العملات')
    }
  }

  function resetGroup(group) {
    groups[group] = reactive({ ...pristine[group] })
  }

  return {
    groups,
    pristine,
    loading,
    loaded,
    savingGroup,
    lastSavedAt,
    currencies,
    dirtyGroups,
    isDirty,
    fetchAll,
    fetchCurrencies,
    setValue,
    saveGroup,
    resetGroup
  }
})
