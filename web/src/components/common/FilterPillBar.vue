<template>
  <div class="filter-pill-bar">
    <div class="active-pills-list">
      <div v-for="filter in activeFilters" :key="filter.key" class="filter-pill">
        <span class="pill-label">{{ filter.label }}:</span>
        <span class="pill-value">{{ filter.displayValue }}</span>
        <button class="pill-remove-btn" @click="removeFilter(filter.key)">×</button>
      </div>

      <!-- Add Filter Popover Trigger Button -->
      <div class="add-filter-wrapper">
        <button class="btn-add-filter" @click="showPopover = !showPopover">
          <i class="pi pi-filter"></i>
          <span>تصفية إضافية</span>
          <i class="pi pi-chevron-down text-xs"></i>
        </button>

        <transition name="fade">
          <div v-if="showPopover" class="filter-popover">
            <div class="popover-header">
              <span>تصفية النتائج حسب</span>
              <button class="close-popover-btn" @click="showPopover = false">×</button>
            </div>
            <div class="popover-body">
              <div v-for="opt in availableOptions" :key="opt.key" class="popover-option" @click="selectFilterKey(opt)">
                <i :class="opt.icon"></i>
                <span>{{ opt.label }}</span>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- Batch Selection Floating Toolbar -->
    <div v-if="selectedCount > 0" class="batch-action-toolbar">
      <span class="selected-count-badge">تم تحديد {{ selectedCount }} عنصر</span>
      <div class="batch-buttons">
        <slot name="batch-actions">
          <button class="btn-secondary btn-sm" @click="$emit('batch-export')">
            <i class="pi pi-download"></i> تصدير المحددة
          </button>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  options: { type: Array, default: () => [] }, // [{ key: 'status', label: 'الحالة', icon: 'pi pi-tag', choices: [...] }]
  modelValue: { type: Object, default: () => ({}) },
  selectedCount: { type: Number, default: 0 }
})

const emit = defineEmits(['update:modelValue', 'batch-export'])

const showPopover = ref(false)

const activeFilters = computed(() => {
  const list = []
  Object.keys(props.modelValue).forEach(key => {
    const val = props.modelValue[key]
    if (val !== null && val !== undefined && val !== '') {
      const optDef = props.options.find(o => o.key === key)
      const label = optDef ? optDef.label : key
      let displayValue = val
      if (optDef && optDef.choices) {
        const match = optDef.choices.find(c => c.value === val)
        if (match) displayValue = match.label
      }
      list.push({ key, label, displayValue, val })
    }
  })
  return list
})

const availableOptions = computed(() => {
  return props.options.filter(o => !props.modelValue[o.key])
})

function removeFilter(key) {
  const updated = { ...props.modelValue }
  delete updated[key]
  emit('update:modelValue', updated)
}

function selectFilterKey(opt) {
  showPopover.value = false
  if (opt.choices && opt.choices.length > 0) {
    const updated = { ...props.modelValue, [opt.key]: opt.choices[0].value }
    emit('update:modelValue', updated)
  }
}
</script>

<style scoped>
.filter-pill-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}

.active-pills-list {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #EFF6FF;
  border: 1px solid #BFDBFE;
  color: var(--accent);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12.5px;
  font-weight: 500;
}
.pill-label {
  color: var(--text-secondary);
}
.pill-value {
  font-weight: 600;
}
.pill-remove-btn {
  background: transparent;
  border: none;
  color: var(--accent);
  cursor: pointer;
  font-size: 14px;
  font-weight: 700;
  line-height: 1;
}

.add-filter-wrapper {
  position: relative;
}
.btn-add-filter {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #FFFFFF;
  border: 1px dashed var(--border);
  border-radius: var(--radius-full);
  font-family: var(--font-family);
  font-size: 12.5px;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-add-filter:hover {
  background: #F8FAFC;
  border-color: var(--accent);
  color: var(--accent);
}

.filter-popover {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 6px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-lg);
  width: 200px;
  z-index: 1050;
  overflow: hidden;
}

.popover-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: #F8FAFC;
  border-bottom: 1px solid var(--border);
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
}
.close-popover-btn {
  background: transparent;
  border: none;
  font-size: 14px;
  cursor: pointer;
}

.popover-body {
  padding: 4px;
  display: flex;
  flex-direction: column;
}
.popover-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  font-size: 12.5px;
  color: var(--text-primary);
  border-radius: var(--radius-sm);
  cursor: pointer;
}
.popover-option:hover {
  background: #F1F5F9;
}

.batch-action-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #0F172A;
  color: #FFFFFF;
  padding: 6px 14px;
  border-radius: var(--radius-full);
}
.selected-count-badge {
  font-size: 12.5px;
  font-weight: 600;
}
.btn-sm {
  padding: 4px 10px !important;
  font-size: 12px !important;
}
</style>
