<template>
  <div class="toast-container-wrapper" dir="rtl">
    <transition-group name="toast-spring" tag="div" class="toast-list">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="toast-card"
        :class="'toast-' + toast.type"
      >
        <div class="toast-icon-box">
          <i v-if="toast.type === 'success'" class="pi pi-check-circle animated-pop"></i>
          <i v-else-if="toast.type === 'error'" class="pi pi-exclamation-circle animated-shake"></i>
          <i v-else-if="toast.type === 'warning'" class="pi pi-exclamation-triangle"></i>
          <i v-else class="pi pi-info-circle"></i>
        </div>

        <div class="toast-content">
          <h4 v-if="toast.title" class="toast-title">{{ toast.title }}</h4>
          <p class="toast-message">{{ toast.message }}</p>
        </div>

        <!-- Undo Action Button -->
        <button
          v-if="toast.undoAction"
          class="toast-undo-btn"
          @click="toastStore.triggerUndo(toast)"
        >
          <i class="pi pi-undo"></i>
          <span>{{ toast.undoText }}</span>
        </button>

        <!-- Close Button -->
        <button class="toast-close-btn" @click="toastStore.remove(toast.id)" aria-label="إغلاق">
          <i class="pi pi-times"></i>
        </button>

        <!-- Progress Bar Indicator -->
        <div
          v-if="toast.duration > 0"
          class="toast-progress-bar"
          :style="{ animationDuration: toast.duration + 'ms' }"
        ></div>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()
</script>

<style scoped>
.toast-container-wrapper {
  position: fixed;
  bottom: 24px;
  left: 24px;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
  max-width: 420px;
  width: calc(100vw - 48px);
}

.toast-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.toast-card {
  pointer-events: auto;
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: var(--radius-md);
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--border);
  overflow: hidden;
  direction: rtl;
}

.toast-card.toast-success {
  border-right: 4px solid var(--success);
}
.toast-card.toast-success .toast-icon-box {
  color: var(--success);
  background: #F0FDF4;
}

.toast-card.toast-error {
  border-right: 4px solid var(--danger);
}
.toast-card.toast-error .toast-icon-box {
  color: var(--danger);
  background: #FEF2F2;
}

.toast-card.toast-warning {
  border-right: 4px solid var(--warning);
}
.toast-card.toast-warning .toast-icon-box {
  color: var(--warning);
  background: #FEF3C7;
}

.toast-card.toast-info {
  border-right: 4px solid var(--accent);
}
.toast-card.toast-info .toast-icon-box {
  color: var(--accent);
  background: #EFF6FF;
}

.toast-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.toast-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.toast-title {
  margin: 0;
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
}

.toast-message {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.4;
  word-break: break-word;
}

.toast-undo-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #EEF2FF;
  color: var(--accent);
  border: 1px solid #C7D2FE;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
  font-family: inherit;
}
.toast-undo-btn:hover {
  background: var(--accent);
  color: #FFFFFF;
  border-color: var(--accent);
  transform: translateY(-1px);
}
.toast-undo-btn:active {
  transform: scale(0.96);
}

.toast-close-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.toast-close-btn:hover {
  color: var(--text-primary);
  background: #F1F5F9;
}

.toast-progress-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(0, 0, 0, 0.08);
  animation: toastProgress linear forwards;
  transform-origin: right;
}

@keyframes toastProgress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

/* Animations */
.animated-pop {
  animation: popSuccess 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.animated-shake {
  animation: shakeError 0.4s ease-in-out;
}

/* Spring Transition */
.toast-spring-enter-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-spring-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 1, 1);
}
.toast-spring-enter-from {
  opacity: 0;
  transform: translateX(-40px) scale(0.9);
}
.toast-spring-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
