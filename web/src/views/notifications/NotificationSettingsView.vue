<template>
  <div class="page-view">
    <div class="settings-container">
      <!-- Header -->
      <div class="settings-header">
        <div class="header-info">
          <h1>إعدادات الإشعارات</h1>
          <p>تخصيص الإشعارات وقنوات الاستلام حسب احتياجك</p>
        </div>
      </div>

      <!-- Settings List -->
      <div v-if="store.settingsLoading" class="settings-loading">
        <div v-for="i in 5" :key="i" class="setting-skeleton">
          <div class="skel-lines">
            <div class="skel-title"></div>
            <div class="skel-desc"></div>
          </div>
          <div class="skel-toggles">
            <div class="skel-toggle"></div>
            <div class="skel-toggle"></div>
            <div class="skel-toggle"></div>
          </div>
        </div>
      </div>

      <div v-else class="settings-list">
        <div class="settings-table-header">
          <div class="col-label">نوع الإشعار</div>
          <div class="col-toggles">
            <span class="toggle-label">مفعّل</span>
            <span class="toggle-label">في التطبيق</span>
            <span class="toggle-label">SMS</span>
            <span class="toggle-label">بريد إلكتروني</span>
          </div>
        </div>

        <div
          v-for="setting in store.settings"
          :key="setting.type"
          class="setting-row"
        >
          <div class="setting-info">
            <div class="setting-icon" :class="'icon-' + setting.type">
              <i :class="getSettingIcon(setting.type)"></i>
            </div>
            <div class="setting-text">
              <span class="setting-title">{{ setting.title }}</span>
              <span class="setting-desc">{{ setting.description }}</span>
            </div>
          </div>
          <div class="setting-toggles">
            <label class="toggle-switch" data-label="مفعّل">
              <input
                type="checkbox"
                :checked="setting.is_enabled"
                @change="toggleSetting(setting.type, 'is_enabled', $event.target.checked)"
              />
              <span class="toggle-slider"></span>
            </label>
            <label class="toggle-switch" data-label="في التطبيق">
              <input
                type="checkbox"
                :checked="setting.in_app_enabled"
                :disabled="!setting.is_enabled"
                @change="toggleSetting(setting.type, 'in_app_enabled', $event.target.checked)"
              />
              <span class="toggle-slider"></span>
            </label>
            <label class="toggle-switch" data-label="SMS">
              <input
                type="checkbox"
                :checked="setting.sms_enabled"
                :disabled="!setting.is_enabled"
                @change="toggleSetting(setting.type, 'sms_enabled', $event.target.checked)"
              />
              <span class="toggle-slider"></span>
            </label>
            <label class="toggle-switch" data-label="بريد إلكتروني">
              <input
                type="checkbox"
                :checked="setting.email_enabled"
                :disabled="!setting.is_enabled"
                @change="toggleSetting(setting.type, 'email_enabled', $event.target.checked)"
              />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useToastStore } from '@/stores/toast'

const store = useNotificationsStore()
const toast = useToastStore()

function getSettingIcon(type) {
  return {
    contract_expiration: 'pi pi-file',
    overdue_invoice: 'pi pi-dollar',
    new_payment: 'pi pi-wallet',
    maintenance_request: 'pi pi-wrench',
    sms_failure: 'pi pi-comments',
    system_alert: 'pi pi-cog',
    login_alert: 'pi pi-shield',
  }[type] || 'pi pi-bell'
}

async function toggleSetting(type, field, value) {
  await store.updateSetting(type, { [field]: value })
  toast.success('تم تحديث الإعدادات')
}

onMounted(() => {
  store.fetchSettings()
})
</script>

<style scoped>
.settings-container {
  max-width: 800px;
  margin: 0 auto;
}

.settings-header {
  margin-bottom: 24px;
}

.settings-header h1 {
  margin: 0 0 4px;
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
}

.settings-header p {
  margin: 0;
  font-size: 13.5px;
  color: var(--text-secondary);
}

.settings-loading {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.setting-skeleton {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--surface-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}

.skel-title {
  height: 14px;
  width: 140px;
  background: var(--bg-subtle);
  border-radius: 4px;
  margin-bottom: 6px;
  animation: pulse 1.5s infinite;
}

.skel-desc {
  height: 11px;
  width: 250px;
  background: var(--bg-subtle);
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

.skel-toggles {
  display: flex;
  gap: 20px;
}

.skel-toggle {
  width: 40px;
  height: 22px;
  background: var(--bg-subtle);
  border-radius: 11px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.settings-table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 20px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.col-toggles {
  display: flex;
  gap: 18px;
  min-width: 240px;
  justify-content: space-between;
}

.toggle-label {
  width: 50px;
  text-align: center;
}

.settings-list {
  display: flex;
  flex-direction: column;
  gap: 1px;
  background: var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border);
}

.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--surface-card);
  transition: background 0.15s ease;
}

.setting-row:hover {
  background: var(--bg-hover);
}

.setting-info {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
}

.setting-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.icon-contract_expiration { background: var(--info-bg); color: var(--info-contrast); }
.icon-overdue_invoice { background: #fee2e2; color: #dc2626; }
.icon-new_payment { background: #d1fae5; color: #059669; }
.icon-maintenance_request { background: var(--warning-bg); color: var(--warning-contrast); }
.icon-sms_failure { background: #ede9fe; color: #7c3aed; }
.icon-system_alert { background: var(--bg-subtle); color: var(--text-secondary); }
.icon-login_alert { background: #dbeafe; color: #2563eb; }

.setting-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.setting-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

.setting-desc {
  font-size: 12.5px;
  color: var(--text-secondary);
}

.setting-toggles {
  display: flex;
  gap: 18px;
  min-width: 240px;
  justify-content: space-between;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 42px;
  height: 22px;
  cursor: pointer;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--border);
  border-radius: 11px;
  transition: 0.3s;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: 0.3s;
}

input:checked + .toggle-slider {
  background: var(--accent);
}

input:checked + .toggle-slider::before {
  transform: translateX(20px);
}

input:disabled + .toggle-slider {
  opacity: 0.4;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .settings-table-header {
    display: none;
  }
  .setting-row {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  .setting-toggles {
    min-width: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 16px;
    width: 100%;
    justify-content: normal;
    border-top: 1px solid var(--border-light);
    padding-top: 12px;
  }
  .toggle-switch {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    height: 24px;
  }
  .toggle-switch::after {
    content: attr(data-label);
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
  }
  .toggle-switch .toggle-slider {
    left: 0;
    right: auto;
    width: 42px;
  }
}
</style>
