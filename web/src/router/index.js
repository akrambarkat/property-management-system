import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/components/layout/AppLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { layout: 'none' }
  },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue')
      },
      {
        path: 'locations',
        name: 'Locations',
        component: () => import('@/views/locations/LocationsView.vue')
      },
      {
        path: 'buildings',
        name: 'Buildings',
        component: () => import('@/views/buildings/BuildingsView.vue')
      },
      {
        path: 'units',
        name: 'Units',
        component: () => import('@/views/units/UnitsView.vue')
      },
      {
        path: 'tenants',
        name: 'Tenants',
        component: () => import('@/views/tenants/TenantsView.vue')
      },
      {
        path: 'contracts',
        name: 'Contracts',
        component: () => import('@/views/contracts/ContractsView.vue')
      },
      {
        path: 'invoices',
        name: 'Invoices',
        component: () => import('@/views/invoices/InvoicesView.vue')
      },
      {
        path: 'payments',
        name: 'Payments',
        component: () => import('@/views/payments/PaymentsView.vue')
      },
      {
        path: 'utilities',
        name: 'Utilities',
        component: () => import('@/views/utilities/UtilitiesView.vue')
      },
      {
        path: 'expenses',
        name: 'Expenses',
        component: () => import('@/views/expenses/ExpensesView.vue')
      },
      {
        path: 'maintenance',
        name: 'Maintenance',
        component: () => import('@/views/maintenance/MaintenanceView.vue')
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('@/views/reports/ReportsView.vue')
      },
      {
        path: 'notifications',
        name: 'Notifications',
        component: () => import('@/views/notifications/NotificationsView.vue')
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/views/users/UsersView.vue')
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/views/settings/SettingsView.vue')
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: () => {
      const authStore = useAuthStore()
      return authStore.isAuthenticated ? { name: 'Dashboard' } : { name: 'Login' }
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.matched.some(record => record.meta.requiresAuth) && !authStore.isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.name === 'Login' && authStore.isAuthenticated) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
