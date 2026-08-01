<template>
  <div class="page-view settings-center">
    <!-- Loading skeletons -->
    <div v-if="store.loading" class="settings-center-layout">
      <div class="settings-center-skeleton">
        <div class="settings-center-layout">
          <aside class="settings-nav skeleton-side">
            <div v-for="i in 8" :key="i" class="skeleton-cell" style="height: 40px; margin-bottom: 8px;"></div>
          </aside>
          <div class="settings-panel" style="flex: 1;">
            <div v-for="i in 5" :key="i" class="skeleton-cell" style="height: 90px; margin-bottom: 14px;"></div>
          </div>
        </div>
      </div>
    </div>

    <template v-else>
      <div class="settings-center-layout">
        <!-- Category navigation -->
        <aside class="settings-nav">
          <div class="settings-nav-header">
            <h2>مركز الإعدادات</h2>
            <p>إدارة إعدادات النظام والشركة</p>
          </div>
          <nav>
            <button
              v-for="cat in categories"
              :key="cat.key"
              class="settings-nav-item"
              :class="{ active: activeCategory === cat.key, 'has-dirty': dirtyGroups.includes(cat.key) }"
              @click="activeCategory = cat.key"
            >
              <i :class="cat.icon"></i>
              <span>{{ cat.label }}</span>
              <span v-if="dirtyGroups.includes(cat.key)" class="dirty-dot" title="تغييرات غير محفوظة"></span>
            </button>
          </nav>
        </aside>

        <!-- Active panel -->
        <section class="settings-panel">
          <transition name="fade-slide" mode="out-in">
            <component
              :is="activeComponent"
              :key="activeCategory"
              :settings="store.groups[activeCategory]"
              :dirty="store.isDirty(activeCategory)"
              :saving="store.savingGroup === activeCategory"
              @save="store.saveGroup(activeCategory)"
            />
          </transition>
        </section>
      </div>
    </template>

    <!-- Unsaved changes guard on leave -->
    <ConfirmModal
      v-model:visible="showLeaveConfirm"
      title="تغييرات غير محفوظة"
      message="لديك تغييرات غير محفوظة في الإعدادات. هل تريد مغادرتها؟"
      variant="warning"
      confirmText="مغادرة"
      cancelText="البقاء"
      @confirm="proceedLeave"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useSettingsStore } from '@/stores/settings'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

import GeneralPanel from './panels/GeneralPanel.vue'
import CompanyPanel from './panels/CompanyPanel.vue'
import SmsPanel from './panels/SmsPanel.vue'
import NotificationsPanel from './panels/NotificationsPanel.vue'
import InvoicesPanel from './panels/InvoicesPanel.vue'
import ContractsPanel from './panels/ContractsPanel.vue'
import AppearancePanel from './panels/AppearancePanel.vue'
import SecurityPanel from './panels/SecurityPanel.vue'
import BackupPanel from './panels/BackupPanel.vue'
import SystemPanel from './panels/SystemPanel.vue'
import UsersPanel from './panels/UsersPanel.vue'

const store = useSettingsStore()

const activeCategory = ref('general')
const showLeaveConfirm = ref(false)
let pendingLeave = null

const categories = [
  { key: 'general', label: 'عام', icon: 'pi pi-sliders-h', component: GeneralPanel },
  { key: 'company', label: 'بيانات الشركة', icon: 'pi pi-building', component: CompanyPanel },
  { key: 'sms', label: 'بوابة الرسائل SMS', icon: 'pi pi-send', component: SmsPanel },
  { key: 'notifications', label: 'الإشعارات', icon: 'pi pi-bell', component: NotificationsPanel },
  { key: 'users', label: 'المستخدمون والصلاحيات', icon: 'pi pi-users', component: UsersPanel },
  { key: 'invoices', label: 'الفواتير', icon: 'pi pi-file-invoice', component: InvoicesPanel },
  { key: 'contracts', label: 'العقود', icon: 'pi pi-file-edit', component: ContractsPanel },
  { key: 'appearance', label: 'المظهر', icon: 'pi pi-palette', component: AppearancePanel },
  { key: 'security', label: 'الأمان', icon: 'pi pi-shield', component: SecurityPanel },
  { key: 'backup', label: 'النسخ الاحتياطي', icon: 'pi pi-database', component: BackupPanel },
  { key: 'system', label: 'النظام', icon: 'pi pi-cog', component: SystemPanel }
]

const activeComponent = computed(() => {
  return categories.find(c => c.key === activeCategory.value)?.component
})

const dirtyGroups = computed(() => store.dirtyGroups)

function switchCategory(key) {
  if (key !== activeCategory.value && store.dirtyGroups.length > 0) {
    showLeaveConfirm.value = true
    pendingLeave = key
    return
  }
  activeCategory.value = key
}

function proceedLeave() {
  showLeaveConfirm.value = false
  if (pendingLeave) {
    activeCategory.value = pendingLeave
    pendingLeave = null
  }
}

onBeforeRouteLeave(() => {
  if (store.dirtyGroups.length > 0) {
    showLeaveConfirm.value = true
    return false
  }
  return true
})

watch(showLeaveConfirm, (val) => {
  if (!val) pendingLeave = null
})

onMounted(() => {
  if (!store.loaded) store.fetchAll()
})
</script>

<style scoped>
.settings-center {
  min-height: calc(100vh - 160px);
}
.settings-center-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 24px;
  align-items: start;
}
.settings-nav {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 16px;
  position: sticky;
  top: calc(var(--header-height) + 20px);
  max-height: calc(100vh - var(--header-height) - 40px);
  overflow-y: auto;
}
.settings-nav-header h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}
.settings-nav-header p {
  margin: 4px 0 14px;
  font-size: 12px;
  color: var(--text-secondary);
}
.settings-nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: none;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
  margin-bottom: 2px;
  text-align: right;
  position: relative;
}
.settings-nav-item i {
  font-size: 1rem;
  width: 20px;
  text-align: center;
}
.settings-nav-item:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}
.settings-nav-item.active {
  background: var(--accent-light);
  color: var(--accent);
}
.settings-nav-item.active::before {
  content: '';
  position: absolute;
  right: 0;
  top: 25%;
  bottom: 25%;
  width: 3px;
  border-radius: 3px;
  background: var(--accent);
}
.dirty-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--warning);
  margin-right: auto;
}
.settings-panel {
  min-width: 0;
  min-height: 400px;
}
.skeleton-side {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 16px;
}

@media (max-width: 900px) {
  .settings-center-layout {
    grid-template-columns: 1fr;
  }
  .settings-nav {
    position: static;
    max-height: none;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 12px;
  }
  .settings-nav-header {
    width: 100%;
  }
  .settings-nav-item {
    width: auto;
    flex: 1 1 45%;
  }
  .settings-nav-item.active::before {
    display: none;
  }
}
</style>
