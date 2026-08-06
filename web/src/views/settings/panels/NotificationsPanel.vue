<template>
  <div class="panels-stack">
    <SettingsCard
      title="إعدادات الإشعارات"
      subtitle="القنوات، التنبيهات التلقائية، ورسائل التذكير المرتبطة بالإيجار"
      icon="pi pi-bell"
      icon-tone="amber"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="section-note">
        <i class="pi pi-info-circle"></i>
        <p>كل خيار هنا يحدد من أين يخرج التنبيه ومتى يتم إرساله. أبقِ فقط القنوات التي تحتاجها فعلاً حتى لا تتضاعف الرسائل على المستخدمين.</p>
      </div>

      <div class="section-grid">
        <div class="setting-switch-card" v-for="item in channelItems" :key="item.key">
          <div class="switch-copy">
            <div class="field-title">{{ item.title }}</div>
            <p>{{ item.description }}</p>
          </div>
          <InputSwitch v-model="form[item.key]" />
        </div>
      </div>
    </SettingsCard>

    <SettingsCard
      title="التذكيرات التلقائية للإيجار"
      subtitle="إرسال رسائل قبل الاستحقاق وبعد التأخر"
      icon="pi pi-calendar-clock"
      icon-tone="blue"
      :dirty="dirty"
      :saving="saving"
      :saved="savedFlash"
      @save="handleSave"
    >
      <div class="automation-banner" :class="{ enabled: form.sms_reminder_enabled }">
        <i :class="form.sms_reminder_enabled ? 'pi pi-check-circle' : 'pi pi-info-circle'"></i>
        <div>
          <strong>{{ form.sms_reminder_enabled ? 'التذكيرات التلقائية مفعلة' : 'التذكيرات التلقائية معطلة' }}</strong>
          <p>عندما تكون مفعلة، سيتم إرسال الرسائل وفق الجدول المحدد أدناه.</p>
        </div>
        <div class="automation-master">
          <span>تفعيل الأتمتة</span>
          <InputSwitch v-model="form.sms_reminder_enabled" />
        </div>
      </div>

      <div class="form-grid-2" :class="{ disabled: !form.sms_reminder_enabled }">
        <div class="field-card">
          <div class="field-title">أيام التذكير قبل الاستحقاق</div>
          <div class="field-subtitle">اختر الأيام التي ستُرسل فيها الرسالة قبل موعد السداد</div>
          <FormField label="أيام التذكير قبل الاستحقاق" forId="notif-before-days">
          <div class="days-grid">
            <label v-for="d in reminderDayOptions" :key="d" class="day-pill" :class="{ selected: reminderDays.includes(d) }">
              <input type="checkbox" :value="d" v-model="reminderDays" class="day-checkbox" />
              <i class="pi pi-clock"></i>
              <span>قبل {{ d }} {{ d === 1 ? 'يوم' : d === 2 ? 'يومين' : 'أيام' }}</span>
            </label>
          </div>
          </FormField>
        </div>

        <div class="field-card">
          <div class="field-title">التذكير بعد التأخر</div>
          <div class="field-subtitle">تفعيل رسالة تلقائية بعد تجاوز تاريخ الاستحقاق</div>
          <FormField label="التذكير بعد التأخر" forId="notif-overdue">
          <div class="inline-switch">
            <InputSwitch v-model="form.sms_reminder_overdue_enabled" />
            <span>إرسال تذكير متأخر</span>
          </div>
          </FormField>
        </div>

        <div class="field-card" v-if="form.sms_reminder_overdue_enabled">
          <div class="field-title">عدد أيام التأخر</div>
          <div class="field-subtitle">عدد الأيام بعد الاستحقاق قبل إرسال التنبيه</div>
          <FormField label="عدد أيام التأخر" forId="notif-overdue-days" :errorMessage="errors.sms_reminder_overdue_days">
          <InputNumber
            id="notif-overdue-days"
            v-model="form.sms_reminder_overdue_days"
            class="w-full"
            :min="1"
            :max="365"
            @blur="clearError('sms_reminder_overdue_days')"
          />
          </FormField>
        </div>

        <div class="field-card">
          <div class="field-title">قالب رسالة التذكير</div>
          <div class="field-subtitle">القالب الذي سيستخدمه النظام عند إرسال التذكيرات</div>
          <FormField label="قالب رسالة التذكير" forId="notif-tpl">
          <Select
            id="notif-tpl"
            v-model="form.sms_reminder_template_id"
            :options="templates"
            optionLabel="title"
            optionValue="id"
            class="w-full"
            placeholder="اختر قالباً"
            showClear
          />
          </FormField>
        </div>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import api from '@/services/api'
import SettingsCard from '@/components/settings/SettingsCard.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save'])
const store = useSettingsStore()
const toast = useToastStore()
const savedFlash = ref(false)
const templates = ref([])
const reminderDays = ref([])
const form = reactive({})
const errors = reactive({ sms_reminder_overdue_days: '' })

const reminderDayOptions = [1, 3, 7]
const channelItems = [
  { key: 'notify_sms', title: 'SMS', description: 'إرسال إشعارات نصية قصيرة' },
  { key: 'notify_email', title: 'البريد الإلكتروني', description: 'إرسال إشعارات عبر البريد' },
  { key: 'notify_system', title: 'تنبيهات النظام', description: 'إظهار تنبيهات داخل المنصة' }
]

watch(() => props.settings, (val) => {
  const v = val || {}
  Object.keys(v).forEach(k => { form[k] = v[k] })
  reminderDays.value = Array.isArray(v.sms_reminder_days_before) ? [...v.sms_reminder_days_before] : []
  form.sms_reminder_enabled = !!v.sms_reminder_enabled
  form.sms_reminder_overdue_enabled = !!v.sms_reminder_overdue_enabled
  form.sms_reminder_overdue_days = v.sms_reminder_overdue_days ?? 1
  form.sms_reminder_template_id = v.sms_reminder_template_id || null
}, { immediate: true, deep: true })

watch([reminderDays, () => form], () => {
  store.setValue('notifications', 'sms_reminder_days_before', [...reminderDays.value].sort((a, b) => b - a))
  Object.keys(form).forEach(k => {
    if (props.settings && form[k] !== props.settings[k]) store.setValue('notifications', k, form[k])
  })
}, { deep: true })

onMounted(async () => {
  try {
    const { data } = await api.get('/sms/templates')
    templates.value = data.data || []
  } catch (err) {
    toast.error('تعذر تحميل قوالب الرسائل')
  }
})

function clearError(field) {
  if (errors[field]) errors[field] = ''
}

function validateForm() {
  let ok = true
  Object.keys(errors).forEach(k => (errors[k] = ''))
  if (form.sms_reminder_overdue_enabled) {
    if (form.sms_reminder_overdue_days !== null && form.sms_reminder_overdue_days !== undefined && (form.sms_reminder_overdue_days < 1 || form.sms_reminder_overdue_days > 365)) {
      errors.sms_reminder_overdue_days = 'يجب أن يكون بين 1 و 365 يوم'
      ok = false
    }
  }
  return ok
}

function handleSave() {
  if (!validateForm()) return
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
.section-note i { color: var(--info-contrast); font-size: 1rem; margin-top: 2px; }
.section-note p { margin: 0; font-size: 12.5px; line-height: 1.8; color: var(--text-secondary); }
.section-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.setting-switch-card {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;
  padding: 16px; border: 1px solid var(--border); border-radius: var(--radius-md);
  background: var(--bg-subtle);
}
.switch-copy strong { display: block; font-size: 13.5px; color: var(--text-primary); }
.switch-copy p { margin: 4px 0 0; font-size: 12px; color: var(--text-secondary); line-height: 1.6; }
.automation-banner {
  display: flex; align-items: center; gap: 14px; padding: 16px;
  border-radius: var(--radius-md); border: 1px solid var(--border);
  background: var(--bg-subtle); flex-wrap: wrap;
}
.automation-banner > i { font-size: 1.6rem; color: var(--text-muted); flex-shrink: 0; }
.automation-banner.enabled { border-color: var(--success-border); background: var(--success-bg); }
.automation-banner.enabled > i { color: var(--success); }
.automation-banner strong { display: block; font-size: 14px; color: var(--text-primary); }
.automation-banner p { margin: 2px 0 0; font-size: 12.5px; color: var(--text-secondary); }
.automation-master { margin-right: auto; display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 13px; }
.form-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px; }
.field-card{display:flex;flex-direction:column;gap:8px;padding:14px 16px;border:1px solid var(--border-light);border-radius:var(--radius-md);background:var(--bg-surface);}
.field-title{font-size:14px;font-weight:800;color:var(--text-primary);}
.field-subtitle{font-size:12px;line-height:1.7;color:var(--text-secondary);}
.inline-switch { display: flex; align-items: center; gap: 10px; }
.days-grid { display: flex; gap: 10px; flex-wrap: wrap; }
.day-pill {
  display: flex; align-items: center; gap: 8px; padding: 10px 16px;
  border: 1px solid var(--border); border-radius: var(--radius-full);
  cursor: pointer; font-size: 13px; font-weight: 600; color: var(--text-secondary);
  transition: all 0.15s ease;
}
.day-pill.selected { border-color: var(--accent); background: var(--accent-light); color: var(--accent); }
.day-checkbox { display: none; }
.disabled { opacity: 0.5; pointer-events: none; }
@media (max-width: 900px) {
  .section-grid,
  .form-grid-2 { grid-template-columns: 1fr; }
}
</style>
