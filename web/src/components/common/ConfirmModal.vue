<template>
  <Dialog
    :visible="visible"
    :header="title"
    modal
    :style="{ width: width || '440px' }"
    class="saas-dialog confirm-modal-dialog"
    :closable="true"
    @update:visible="emit('update:visible', $event)"
    @keydown.esc="onCancel"
    @keydown.enter="onConfirm"
  >
    <div class="confirm-modal-body">
      <div class="confirm-icon-wrapper" :class="'icon-' + variant">
        <i :class="icon || defaultIcon"></i>
      </div>

      <div class="confirm-text-content">
        <h3 v-if="subtitle" class="confirm-subtitle">{{ subtitle }}</h3>
        <p class="confirm-message" v-html="message"></p>
        <span v-if="details" class="confirm-details">{{ details }}</span>
      </div>

      <div class="confirm-actions">
        <button class="btn-secondary" @click="onCancel" :disabled="loading">
          {{ cancelText || 'إلغاء' }}
        </button>
        <button
          class="btn-primary"
          :class="{ 'btn-danger-action': variant === 'danger' }"
          @click="onConfirm"
          :disabled="loading"
        >
          <i v-if="loading" class="pi pi-spin pi-spinner"></i>
          <span>{{ confirmText || 'تأكيد' }}</span>
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'تأكيد الإجراء' },
  subtitle: { type: String, default: '' },
  message: { type: String, required: true },
  details: { type: String, default: '' },
  icon: { type: String, default: '' },
  variant: { type: String, default: 'danger' }, // 'danger' | 'warning' | 'info'
  confirmText: { type: String, default: 'تأكيد الإجراء' },
  cancelText: { type: String, default: 'إلغاء' },
  loading: { type: Boolean, default: false },
  width: { type: String, default: '440px' }
})

const emit = defineEmits(['update:visible', 'confirm', 'cancel'])

const defaultIcon = computed(() => {
  if (props.variant === 'danger') return 'pi pi-trash'
  if (props.variant === 'warning') return 'pi pi-exclamation-triangle'
  return 'pi pi-info-circle'
})

function onConfirm() {
  emit('confirm')
}

function onCancel() {
  emit('update:visible', false)
  emit('cancel')
}
</script>

<style scoped>
.confirm-modal-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 12px 8px 4px 8px;
}

.confirm-icon-wrapper {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  margin-bottom: 16px;
  animation: popSuccess 0.35s ease;
}

.confirm-icon-wrapper.icon-danger {
  background: var(--danger-bg);
  color: var(--danger-contrast);
}

.confirm-icon-wrapper.icon-warning {
  background: var(--warning-bg);
  color: var(--warning-contrast);
}

.confirm-icon-wrapper.icon-info {
  background: var(--info-bg);
  color: var(--info-contrast);
}

.confirm-text-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 24px;
}

.confirm-subtitle {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}

.confirm-message {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.confirm-details {
  font-size: 12.5px;
  color: var(--text-muted);
  display: block;
}

.confirm-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
}
.confirm-actions button {
  min-width: 110px;
  justify-content: center;
}

.btn-danger-action {
  background: var(--danger) !important;
  border-color: var(--danger) !important;
}
.btn-danger-action:hover {
  background: var(--danger-hover) !important;
  border-color: var(--danger-hover) !important;
}

@media (max-width: 480px) {
  .confirm-actions {
    flex-direction: column;
    gap: 10px;
    padding-bottom: env(safe-area-inset-bottom, 0px);
  }
  .confirm-actions button {
    width: 100%;
    min-width: 0;
    min-height: 46px;
    justify-content: center;
  }
  .confirm-message {
    font-size: 13.5px;
  }
}
</style>
