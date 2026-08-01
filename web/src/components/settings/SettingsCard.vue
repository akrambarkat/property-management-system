<template>
  <div class="settings-card" :class="{ 'is-dirty': dirty }">
    <div class="settings-card-header">
      <div class="settings-card-icon" :class="`icon-${iconTone}`">
        <i :class="icon"></i>
      </div>
      <div class="settings-card-heading">
        <h3>{{ title }}</h3>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
    </div>
    <div class="settings-card-body">
      <slot />
    </div>
    <div v-if="showFooter !== false" class="settings-card-footer">
      <div class="save-state" :class="saveStateClass">
        <i v-if="saving" class="pi pi-spin pi-spinner"></i>
        <i v-else-if="saved" class="pi pi-check-circle"></i>
        <i v-else-if="dirty" class="pi pi-exclamation-circle"></i>
        <span>{{ statusText }}</span>
      </div>
      <slot name="footer-actions">
        <button class="btn-primary" @click="$emit('save')" :disabled="saving || !dirty">
          <i v-if="saving" class="pi pi-spin pi-spinner"></i>
          <i v-else class="pi pi-save"></i>
          <span>{{ saving ? 'جاري الحفظ...' : 'حفظ التغييرات' }}</span>
        </button>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: 'pi pi-cog' },
  iconTone: { type: String, default: 'indigo' },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  saved: { type: Boolean, default: false },
  showFooter: { type: Boolean, default: true }
})

defineEmits(['save'])

const statusText = computed(() => {
  if (props.saving) return 'جاري الحفظ...'
  if (props.saved) return 'تم الحفظ بنجاح'
  if (props.dirty) return 'تغييرات غير محفوظة'
  return 'لا توجد تغييرات'
})

const saveStateClass = computed(() => {
  if (props.saving) return 'state-saving'
  if (props.saved) return 'state-saved'
  if (props.dirty) return 'state-dirty'
  return 'state-idle'
})
</script>

<style scoped>
.settings-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.settings-card.is-dirty {
  border-color: var(--warning-border);
}
.settings-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-light);
}
.settings-card-icon {
  width: 42px;
  height: 42px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}
.settings-card-heading h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}
.settings-card-heading p {
  margin: 3px 0 0;
  font-size: 12.5px;
  color: var(--text-secondary);
}
.settings-card-body {
  padding: 24px;
}
.settings-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 24px;
  border-top: 1px solid var(--border-light);
  background: var(--bg-subtle);
}
.save-state {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  font-weight: 600;
}
.save-state i {
  font-size: 0.95rem;
}
.state-saving { color: var(--text-secondary); }
.state-saved { color: var(--success-contrast); }
.state-dirty { color: var(--warning-contrast); }
.state-idle { color: var(--text-muted); }

.icon-indigo { background: var(--accent-light); color: var(--accent); }
.icon-blue { background: var(--info-bg); color: var(--info-contrast); }
.icon-green { background: var(--success-bg); color: var(--success-contrast); }
.icon-amber { background: var(--warning-bg); color: var(--warning-contrast); }
.icon-red { background: var(--danger-bg); color: var(--danger-contrast); }
.icon-purple { background: rgba(139, 92, 246, 0.12); color: #7C3AED; }

@media (max-width: 640px) {
  .settings-card-footer { flex-direction: column; align-items: stretch; }
  .settings-card-footer .btn-primary { width: 100%; }
}
</style>
