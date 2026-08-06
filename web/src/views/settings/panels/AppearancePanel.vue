<template>
  <div class="panels-stack">
    <SettingsCard
      title="المظهر"
      subtitle="اللغة، تنسيق التاريخ، وطريقة العرض"
      icon="pi pi-palette"
      icon-tone="indigo"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-palette"></i>
        <p>هذه الخيارات تغير شكل المنصة وطريقة قراءتها، لذلك رتبتها بشكل مختصر وواضح حتى لا تبقى أي خانة بدون معنى.</p>
      </div>

      <div class="theme-options-grid">
        <button
          v-for="t in themeOptions"
          :key="t.value"
          class="theme-option"
          :class="{ selected: form.theme === t.value }"
          @click="form.theme = t.value"
        >
          <i :class="t.icon"></i>
          <span>{{ t.label }}</span>
          <small>{{ t.description }}</small>
        </button>
      </div>

      <div class="form-grid-2">
        <div class="field-card">
          <div class="field-title">لغة الواجهة</div>
          <div class="field-subtitle">اللغة الظاهرة في القوائم والتنبيهات والتقارير</div>
          <FormField label="لغة الواجهة" forId="app-lang">
          <Select
            id="app-lang"
            v-model="form.language"
            :options="[{ value: 'ar', label: 'العربية' }, { value: 'en', label: 'English' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
          </FormField>
        </div>

        <div class="field-card">
          <div class="field-title">تنسيق التاريخ</div>
          <div class="field-subtitle">صيغة عرض التاريخ في المستندات والتقارير</div>
          <FormField label="تنسيق التاريخ" forId="app-datefmt">
          <Select
            id="app-datefmt"
            v-model="form.date_format"
            :options="dateFormats"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
          </FormField>
        </div>

        <div class="field-card">
          <div class="field-title">الوضع المضغوط</div>
          <div class="field-subtitle">تقليل الهوامش لعرض مزيد من البيانات</div>
          <FormField label="الوضع المضغوط" forId="app-compact">
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
  { value: 'light', label: 'فاتح', description: 'خلفية فاتحة', icon: 'pi pi-sun' },
  { value: 'dark', label: 'داكن', description: 'خلفية داكنة', icon: 'pi pi-moon' },
  { value: 'system', label: 'النظام', description: 'حسب الجهاز', icon: 'pi pi-desktop' }
]

const dateFormats = [
  { value: 'd/m/Y', label: '31/12/2026' },
  { value: 'd-m-Y', label: '31-12-2026' },
  { value: 'Y-m-d', label: '2026-12-31' }
]

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form.theme, (val) => {
  if (!val) return
  const dark = val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
  if (dark) {
    document.documentElement.classList.add('p-dark')
    document.documentElement.setAttribute('data-theme', 'dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('p-dark')
    document.documentElement.removeAttribute('data-theme')
    localStorage.setItem('theme', 'light')
  }
})

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
.section-note {
  display: flex; gap: 12px; padding: 14px 16px; border-radius: var(--radius-md);
  background: var(--bg-subtle); border: 1px solid var(--border);
}
.section-note i { color: var(--accent); font-size: 1rem; margin-top: 2px; }
.section-note p { margin: 0; font-size: 12.5px; line-height: 1.8; color: var(--text-secondary); }
.theme-options-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.theme-option {
  display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
  padding: 16px 14px; border: 1px solid var(--border); border-radius: var(--radius-md);
  background: var(--bg-surface); cursor: pointer; text-align: right;
  color: var(--text-secondary); transition: all 0.15s ease;
}
.theme-option i { font-size: 1.3rem; color: var(--accent); }
.theme-option span { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.theme-option small { font-size: 11.5px; color: var(--text-secondary); }
.theme-option.selected { border-color: var(--accent); background: var(--accent-light); }
.form-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.field-card{display:flex;flex-direction:column;gap:8px;padding:14px 16px;border:1px solid var(--border-light);border-radius:var(--radius-md);background:var(--bg-surface);}
.field-title{font-size:14px;font-weight:800;color:var(--text-primary);}
.field-subtitle{font-size:12px;line-height:1.7;color:var(--text-secondary);}
@media (max-width: 800px) {
  .theme-options-grid,
  .form-grid-2 { grid-template-columns: 1fr; }
}
</style>
