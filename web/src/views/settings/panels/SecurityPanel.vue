<template>
  <div class="panels-stack">
    <SettingsCard
      title="الأمان"
      subtitle="سياسات كلمة المرور والجلسات والحماية"
      icon="pi pi-shield"
      icon-tone="red"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-grid-2">
        <FormField label="أدنى طول لكلمة المرور" forId="sec-min" helpText="الحد الأدنى لعدد الأحرف">
          <InputNumber id="sec-min" v-model="form.password_min_length" class="w-full" :min="6" :max="64" />
        </FormField>

        <FormField label="مهلة الجلسة (دقيقة)" forId="sec-session" helpText="انتهاء الجلسة بعد فترة الخمول">
          <InputNumber id="sec-session" v-model="form.session_timeout" class="w-full" :min="1" :max="1440" />
        </FormField>

        <FormField label="محاولات تسجيل الدخول قبل القفل" forId="sec-attempts">
          <InputNumber id="sec-attempts" v-model="form.lockout_attempts" class="w-full" :min="1" :max="20" />
        </FormField>

        <FormField label="المصادقة الثنائية" forId="sec-2fa" helpText="تفعيل التحقق بخطوتين">
          <Select
            id="sec-2fa"
            :model-value="form.two_factor ? 'yes' : 'no'"
            :options="[{ value: 'yes', label: 'نعم' }, { value: 'no', label: 'لا' }]"
            optionLabel="label"
            optionValue="value"
            class="w-full"
            @change="form.two_factor = $event.value === 'yes'"
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

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== props.settings[k]) store.setValue('security', k, val[k])
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
</style>
