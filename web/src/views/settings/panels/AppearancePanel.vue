<template>
  <div class="panels-stack">
    <SettingsCard
      title="المظهر"
      subtitle="المظهر العام للنظام ولغة العرض"
      icon="pi pi-palette"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="المظهر الافتراضي" forId="app-theme" helpText="اختيار وضع العرض للنظام">
          <div class="theme-options">
            <button
              v-for="t in themeOptions"
              :key="t.value"
              class="theme-option"
              :class="{ selected: form.theme === t.value }"
              @click="form.theme = t.value"
            >
              <i :class="t.icon"></i>
              <span>{{ t.label }}</span>
            </button>
          </div>
        </FormField>

        <FormField label="لغة العرض" forId="app-lang">
          <Select
            id="app-lang"
            v-model="form.language"
            :options="[{ value: 'ar', label: 'العربية' }, { value: 'en', label: 'English' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="صيغة التاريخ" forId="app-datefmt">
          <Select
            id="app-datefmt"
            v-model="form.date_format"
            :options="dateFormats"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </FormField>

        <FormField label="الوضع المضغوط" forId="app-compact" helpText="تقليل المسافات لعرض بيانات أكثر">
          <Select
            id="app-compact"
            :model-value="form.compact_mode ? 'yes' : 'no'"
            :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="form.compact_mode = $event.value === 'yes'"
          />
        </FormField>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import SettingsCard from '@/components/settings/SettingsCard.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])
const store = useSettingsStore()
const savedFlash = ref(false)
const form = reactive({})

const themeOptions = [
  { value: 'light', label: 'فاتح', icon: 'pi pi-sun' },
  { value: 'dark', label: 'داكن', icon: 'pi pi-moon' },
  { value: 'system', label: 'النظام', icon: 'pi pi-desktop' }
]

const dateFormats = [
  { value: 'd/m/Y', label: '31/12/2026' },
  { value: 'd-m-Y', label: '31-12-2026' },
  { value: 'Y-m-d', label: '2026-12-31' }
]

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('appearance', k, val[k])
  })
}, { deep: true })

function handleSave() {
  savedFlash.value = true
  setTimeout(() => { savedFlash.value = false }, 2500)
  emit('save')
}
</script>

<style scoped>
.panels-stack { display: flex; flex-direction: column; gap: 20px; }
.theme-options { display: flex; gap: 10px; }
.theme-option {
  flex: 1;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: 16px 10px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  cursor: pointer;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-secondary);
  transition: all 0.15s ease;
}
.theme-option i { font-size: 1.3rem; }
.theme-option:hover { border-color: var(--border-hover); }
.theme-option.selected { border-color: var(--accent); background: var(--accent-light); color: var(--accent); }
</style>
