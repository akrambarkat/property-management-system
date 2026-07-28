<template>
  <header class="app-header">
    <div class="header-left">
      <button class="mobile-menu-btn" @click="appStore.toggleSidebar">
        <i class="pi pi-bars"></i>
      </button>
      <h2 class="page-title">{{ pageTitle }}</h2>
    </div>

    <div class="header-right">
      <div class="currency-selector">
        <i class="pi pi-money-bill"></i>
        <select v-model="appStore.selectedCurrency" @change="onCurrencyChange">
          <option v-for="c in appStore.currencies" :key="c.code" :value="c.code">
            {{ c.symbol }} - {{ c.name }}
          </option>
        </select>
      </div>

      <div class="user-info">
        <div class="user-avatar">
          <i class="pi pi-user"></i>
        </div>
        <div class="user-details" v-if="authStore.currentUser">
          <span class="user-name">{{ authStore.currentUser.name }}</span>
          <span class="user-role">{{ roleLabel }}</span>
        </div>
      </div>

      <button class="logout-btn" @click="handleLogout" title="تسجيل الخروج">
        <i class="pi pi-sign-out"></i>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const authStore = useAuthStore()

const pageTitles = {
  Dashboard: 'لوحة التحكم',
  Locations: 'المواقع',
  Buildings: 'المباني',
  Units: 'الوحدات',
  Tenants: 'المستأجرين',
  Contracts: 'العقود',
  Invoices: 'الفواتير',
  Payments: 'المدفوعات',
  Utilities: 'المرافق',
  Expenses: 'المصروفات',
  Maintenance: 'الصيانة',
  Reports: 'التقارير',
  Users: 'المستخدمين',
  Settings: 'الإعدادات',
  Login: 'تسجيل الدخول'
}

const pageTitle = computed(() => pageTitles[route.name] || 'EMAARPlus')

const roleLabel = computed(() => {
  const labels = { super_admin: 'مدير النظام', employee: 'موظف', guard: 'حارس' }
  return labels[authStore.currentUser?.role] || ''
})

function onCurrencyChange() {
  localStorage.setItem('preferred_currency', appStore.selectedCurrency)
}

function handleLogout() {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>

<style scoped>
.app-header {
  height: var(--header-height);
  background: white;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  font-size: 1.3rem;
  color: var(--text-primary);
  cursor: pointer;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.currency-selector {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-secondary);
  padding: 8px 12px;
  border-radius: 8px;
}

.currency-selector i {
  color: var(--secondary);
}

.currency-selector select {
  background: none;
  border: none;
  font-family: var(--font-family);
  font-size: 14px;
  color: var(--text-primary);
  cursor: pointer;
  outline: none;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: var(--bg-secondary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary);
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

.user-role {
  font-size: 12px;
  color: var(--text-secondary);
}

.logout-btn {
  background: none;
  border: 1px solid var(--border);
  border-radius: 8px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: #FEE2E2;
  color: var(--danger);
  border-color: var(--danger);
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    display: block;
  }
  .user-details {
    display: none;
  }
  .currency-selector select {
    max-width: 80px;
  }
}
</style>
