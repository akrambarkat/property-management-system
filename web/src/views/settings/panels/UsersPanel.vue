<template>
  <div class="panels-stack">
    <SettingsCard
      title="المستخدمون والصلاحيات"
      subtitle="مرجع سريع يوضح الأدوار الأساسية وما يمكن لكل دور الوصول إليه"
      icon="pi pi-users"
      icon-tone="green"
      :show-footer="false"
    >
      <div class="perm-intro">
        <div>
          <strong>نظام الصلاحيات</strong>
          <p>هذه الصفحة للعرض السريع فقط. إدارة المستخدمين نفسها تتم من صفحة المستخدمين المخصصة.</p>
        </div>
        <router-link to="/users" class="btn-primary">
          <i class="pi pi-user-edit"></i>
          <span>الانتقال إلى إدارة المستخدمين</span>
        </router-link>
      </div>

      <div class="legend">
        <span><i class="pi pi-check perm-yes"></i> متاح</span>
        <span><i class="pi pi-times perm-no"></i> غير متاح</span>
      </div>

      <div class="perm-matrix">
        <table>
          <thead>
            <tr>
              <th>الصلاحية</th>
              <th v-for="role in roles" :key="role.key" class="role-col">
                <span class="role-chip" :class="`role-${role.key}`">{{ role.label }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="perm in permissionRows" :key="perm.key">
              <td class="perm-label-cell">
                <div class="perm-name">{{ perm.label }}</div>
                <div class="perm-key">{{ perm.key }}</div>
              </td>
              <td v-for="role in roles" :key="role.key" class="role-col">
                <i :class="canRole(role.key, perm.key) ? 'pi pi-check perm-yes' : 'pi pi-times perm-no'"></i>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </SettingsCard>
  </div>
</template>

<script setup>
const roles = [
  { key: 'super_admin', label: 'مدير النظام' },
  { key: 'employee', label: 'موظف' },
  { key: 'guard', label: 'حارس' }
]

const permissionRows = [
  { key: 'view-settings', label: 'عرض الإعدادات' },
  { key: 'edit-settings', label: 'تعديل الإعدادات' },
  { key: 'view-sms', label: 'عرض الرسائل' },
  { key: 'edit-sms-settings', label: 'تعديل إعدادات SMS' },
  { key: 'send-sms', label: 'إرسال الرسائل' },
  { key: 'view-sms-logs', label: 'عرض سجل الرسائل' },
  { key: 'manage-templates', label: 'إدارة القوالب' },
  { key: 'manage-providers', label: 'إدارة المزودين' },
  { key: 'export-logs', label: 'تصدير السجلات' },
  { key: 'manage-users', label: 'إدارة المستخدمين' }
]

const roleMatrix = {
  super_admin: permissionRows.map(item => item.key),
  employee: ['view-settings', 'view-sms', 'view-sms-logs', 'send-sms', 'manage-templates', 'export-logs'],
  guard: ['view-sms-logs']
}

function canRole(role, perm) {
  if (role === 'super_admin') return true
  return roleMatrix[role]?.includes(perm) ?? false
}
</script>

<style scoped>
.panels-stack {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.perm-intro {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: var(--bg-subtle);
  padding: 14px 18px;
  border-radius: var(--radius-md);
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.perm-intro strong {
  display: block;
  font-size: 14px;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.perm-intro p {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.7;
}

.legend {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 12px;
  font-size: 12px;
  color: var(--text-secondary);
}

.legend span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.perm-matrix {
  overflow-x: auto;
}

.perm-matrix table {
  width: 100%;
  min-width: 620px;
  border-collapse: collapse;
}

.perm-matrix th,
.perm-matrix td {
  padding: 11px 14px;
  text-align: center;
  font-size: 13px;
  border-bottom: 1px solid var(--border-light);
}

.perm-matrix thead th {
  border-bottom: 1px solid var(--border);
  color: var(--text-primary);
}

.perm-label-cell {
  text-align: right;
}

.perm-name {
  font-weight: 700;
  color: var(--text-primary);
}

.perm-key {
  font-size: 11px;
  color: var(--text-muted);
  direction: ltr;
  margin-top: 2px;
}

.perm-yes {
  color: var(--success);
}

.perm-no {
  color: var(--border-hover);
}

.role-chip {
  display: inline-block;
  padding: 3px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
}

@media (max-width: 760px) {
  .perm-intro {
    align-items: flex-start;
  }
}
</style>
