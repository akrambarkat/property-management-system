<template>
  <div class="layout-wrapper" :class="{ 'sidebar-collapsed': appStore.sidebarCollapsed }">
    <AppSidebar />
    <div class="layout-main">
      <AppHeader />
      <div class="layout-content">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

onMounted(() => {
  document.body.style.direction = 'rtl'
})
</script>

<style scoped>
.layout-wrapper {
  display: flex;
  min-height: 100vh;
  direction: rtl;
}

.layout-main {
  flex: 1;
  margin-right: var(--sidebar-width);
  transition: margin-right 0.3s ease;
  display: flex;
  flex-direction: column;
}

.sidebar-collapsed .layout-main {
  margin-right: 80px;
}

.layout-content {
  flex: 1;
  padding: 24px;
  background: var(--bg-secondary);
  overflow-y: auto;
}

@media (max-width: 768px) {
  .layout-main {
    margin-right: 0;
  }
}
</style>
