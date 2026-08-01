<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات الإشعارات"
      subtitle="تحديد القنوات والأحداث المرسلة منها إشعارات"
      icon="pi pi-bell"
      icon-tone="amber"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="form-section-title"><i class="pi pi-sliders-h"></i><span>القنوات المتاحة</span></div>
      <div class="channel-grid">
        <div class="channel-toggle">
          <div><i class="pi pi-send"></i><span>رسائل SMS</span></div>
          <InputSwitch v-model="form.notify_sms" />
        </div>
        <div class="channel-toggle">
          <div><i class="pi pi-envelope"></i><span>البريد الإلكتروني</span></div>
          <InputSwitch v-model="form.notify_email" />
        </div>
        <div class="channel-toggle">
          <div><i class="pi pi-bell"></i><span>إشعارات النظام</span></div>
          <InputSwitch v-model="form.notify_system" />
        </div>
      </div>

      <div class="form-section-title" style="margin-top: 24px;"><i class="pi pi-flag"></i><span>الأحداث</span></div>
      <div class="event-list">
        <div class="event-toggle">
          <span>استلام دفعة</span>
          <InputSwitch v-model="form.notify_on_payment" />
        </div>
        <div class="event-toggle">
          <span>انتهاء / تجديد عقد</span>
          <InputSwitch v-model="form.notify_on_contract" />
        </div>
        <div class="event-toggle">
          <span>طلبات الصيانة</span>
          <InputSwitch v-model="form.notify_on_maintenance" />
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

watch(() => props.settings, (val) => {
  Object.keys(val || {}).forEach(k => { form[k] = !!val[k] })
}, { immediate: true, deep: true })

watch(() => form, (val) => {
  Object.keys(val).forEach(k => {
    if (props.settings && val[k] !== !!props.settings[k]) store.setValue('notifications', k, val[k])
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
.form-section-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; font-weight: 700; color: var(--text-primary);
  padding-bottom: 8px; border-bottom: 1px solid var(--border);
}
.form-section-title i { color: var(--accent); }
.channel-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 14px; }
.channel-toggle {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-sm);
  background: var(--bg-subtle);
}
.channel-toggle div { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 13.5px; }
.channel-toggle i { color: var(--accent); font-size: 1.1rem; }
.event-list { margin-top: 14px; }
.event-toggle {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 4px; border-bottom: 1px solid var(--border-light);
  font-size: 13.5px; font-weight: 600;
}
.event-toggle:last-child { border-bottom: none; }
@media (max-width: 640px) { .channel-grid { grid-template-columns: 1fr; } }
</style>
