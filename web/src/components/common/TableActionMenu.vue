<template>
  <div class="table-action-menu-wrapper" ref="dropdownRef">
    <button 
      type="button" 
      class="action-trigger-btn" 
      :class="{ active: isOpen }" 
      @click.stop="toggleMenu"
      title="الإجراءات"
    >
      <i class="pi pi-ellipsis-v"></i>
    </button>

    <transition name="dropdown-fade">
      <div v-if="isOpen" class="action-dropdown-menu" :class="[alignMenu]">
        <div 
          v-for="(item, index) in items" 
          :key="index"
          class="menu-item"
          :class="[item.danger ? 'danger-item' : '', item.disabled ? 'disabled' : '']"
          @click.stop="handleAction(item)"
        >
          <i v-if="item.icon" :class="item.icon" class="item-icon"></i>
          <span class="item-label">{{ item.label }}</span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
    // Example: [{ label: 'تعديل', icon: 'pi pi-pencil', action: () => {}, danger: false }]
  },
  align: {
    type: String,
    default: 'left' // 'left' | 'right'
  }
})

const isOpen = ref(false)
const dropdownRef = ref(null)

const alignMenu = props.align === 'right' ? 'align-right' : 'align-left'

function toggleMenu() {
  isOpen.value = !isOpen.value
}

function handleAction(item) {
  if (item.disabled) return
  isOpen.value = false
  if (item.command && typeof item.command === 'function') {
    item.command()
  } else if (item.action && typeof item.action === 'function') {
    item.action()
  }
}

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.table-action-menu-wrapper {
  position: relative;
  display: inline-block;
}

.action-trigger-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm, 6px);
  border: 1px solid transparent;
  background: transparent;
  color: var(--text-secondary, #64748B);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  font-size: 14px;
}

.action-trigger-btn:hover,
.action-trigger-btn.active {
  background: var(--bg-subtle, #F8FAFC);
  border-color: var(--border, #E2E8F0);
  color: var(--text-primary, #0F172A);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.action-dropdown-menu {
  position: absolute;
  top: calc(100% + 4px);
  z-index: 1000;
  min-width: 150px;
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-md, 8px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
  padding: 6px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.align-left {
  left: 0;
}

.align-right {
  right: 0;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: var(--radius-sm, 6px);
  font-size: 13px;
  font-weight: 500;
  color: var(--text-primary, #1E293B);
  cursor: pointer;
  transition: background 0.12s ease, color 0.12s ease;
}

.menu-item:hover {
  background: var(--bg-subtle, #F1F5F9);
  color: var(--primary, #2563EB);
}

.menu-item.danger-item {
  color: #EF4444;
}

.menu-item.danger-item:hover {
  background: var(--danger-bg, #FEF2F2);
  color: #DC2626;
}

.menu-item.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.item-icon {
  font-size: 13px;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.97);
}
</style>
