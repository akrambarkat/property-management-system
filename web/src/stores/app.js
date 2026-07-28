import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAppStore = defineStore('app', () => {
  const sidebarCollapsed = ref(false)
  const currencies = ref([
    { code: 'ILS', name: 'شيكل', symbol: '₪', rate: 1.0000, is_default: true },
    { code: 'JOD', name: 'دينار أردني', symbol: 'د.أ', rate: 0.2000, is_default: false },
    { code: 'USD', name: 'دولار أمريكي', symbol: '$', rate: 0.2800, is_default: false }
  ])
  const selectedCurrency = ref('ILS')
  const appName = ref('EMAARPlus')

  const defaultCurrency = computed(() => currencies.value.find(c => c.is_default))
  const currentCurrencySymbol = computed(() => {
    const c = currencies.value.find(cur => cur.code === selectedCurrency.value)
    return c ? c.symbol : '₪'
  })

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function setCurrencies(list) {
    currencies.value = list
  }

  function setSelectedCurrency(code) {
    selectedCurrency.value = code
  }

  return {
    sidebarCollapsed,
    currencies,
    selectedCurrency,
    appName,
    defaultCurrency,
    currentCurrencySymbol,
    toggleSidebar,
    setCurrencies,
    setSelectedCurrency
  }
})
