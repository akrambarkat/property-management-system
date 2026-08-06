<template>
  <div class="form-field" :class="{ 'form-field-invalid': !!errorMessage, 'form-field-valid': !!successMessage && !errorMessage }">
    <div class="form-label-row">
      <label :for="forId" v-if="label">
        <span>{{ label }}</span>
        <span v-if="required" class="required-star" aria-hidden="true">*</span>
      </label>
      <span v-if="maxlength && currentLength !== undefined" class="char-counter">
        {{ currentLength }}/{{ maxlength }}
      </span>
    </div>

    <!-- Default slot for input element -->
    <slot :aria-invalid="!!errorMessage" :aria-describedby="describedBy" />

    <span v-if="helpText && !errorMessage" :id="helpId" class="form-help-text">
      {{ helpText }}
    </span>

    <span v-if="errorMessage" :id="errorId" class="form-error-msg" role="alert">
      <i class="pi pi-exclamation-circle"></i>
      {{ errorMessage }}
    </span>

    <span v-else-if="successMessage" :id="successId" class="form-success-msg" role="status">
      <i class="pi pi-check-circle"></i>
      {{ successMessage }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, default: '' },
  required: { type: Boolean, default: false },
  errorMessage: { type: String, default: '' },
  helpText: { type: String, default: '' },
  successMessage: { type: String, default: '' },
  forId: { type: String, default: '' },
  maxlength: { type: Number, default: null },
  currentLength: { type: Number, default: undefined }
})

const helpId = computed(() => props.forId ? `${props.forId}-help` : undefined)
const errorId = computed(() => props.forId ? `${props.forId}-error` : undefined)
const successId = computed(() => props.forId ? `${props.forId}-success` : undefined)

const describedBy = computed(() => {
  if (props.errorMessage) return errorId.value
  if (props.successMessage) return successId.value
  if (props.helpText) return helpId.value
  return undefined
})
</script>

<style scoped>
.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 14px 0 16px;
  border-bottom: 1px solid var(--border-light);
}

.form-field:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.form-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.form-label-row label {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary, #0F172A);
  display: flex;
  align-items: center;
  gap: 4px;
  margin: 0;
  line-height: 1.4;
}

.form-label-row label .required-star {
  color: var(--danger, #EF4444);
  font-weight: 700;
}

.char-counter {
  font-size: 11px;
  color: var(--text-muted, #94A3B8);
  font-weight: 500;
  white-space: nowrap;
}

.form-help-text {
  font-size: 12px;
  color: var(--text-secondary, #475569);
  line-height: 1.65;
}

.form-error-msg {
  font-size: 12px;
  color: var(--danger-contrast, #991B1B);
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
  animation: fieldFadeIn 0.2s ease-in-out;
}

.form-success-msg {
  font-size: 12px;
  color: var(--success-contrast, #065F46);
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
  animation: fieldFadeIn 0.2s ease-in-out;
}

.form-field-invalid :deep(.p-inputtext),
.form-field-invalid :deep(.p-select),
.form-field-invalid :deep(.p-inputnumber-input),
.form-field-invalid :deep(input),
.form-field-invalid :deep(textarea) {
  border-color: var(--danger, #EF4444) !important;
  background-color: var(--danger-bg, #FEF2F2) !important;
}

.form-field-valid :deep(.p-inputtext),
.form-field-valid :deep(.p-select),
.form-field-valid :deep(.p-inputnumber-input),
.form-field-valid :deep(input),
.form-field-valid :deep(textarea) {
  border-color: var(--success, #10B981) !important;
  background-color: var(--success-bg, #ECFDF5) !important;
}

@keyframes fieldFadeIn {
  from { opacity: 0; transform: translateY(-2px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
