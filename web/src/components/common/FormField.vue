<template>
  <div class="form-field" :class="{ 'form-field-invalid': !!errorMessage }">
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
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, default: '' },
  required: { type: Boolean, default: false },
  errorMessage: { type: String, default: '' },
  helpText: { type: String, default: '' },
  forId: { type: String, default: '' },
  maxlength: { type: Number, default: null },
  currentLength: { type: Number, default: undefined }
})

const helpId = computed(() => props.forId ? `${props.forId}-help` : undefined)
const errorId = computed(() => props.forId ? `${props.forId}-error` : undefined)

const describedBy = computed(() => {
  if (props.errorMessage) return errorId.value
  if (props.helpText) return helpId.value
  return undefined
})
</script>

<style scoped>
.form-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
