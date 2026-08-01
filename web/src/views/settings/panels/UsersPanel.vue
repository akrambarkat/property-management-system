<template>
  <div class="panels-stack">
    <SettingsCard
      title="المستخدمون والصلاحيات"
      subtitle="إدارة مستخدمي النظام وصلاحيات كل دور"
      icon="pi pi-users"
      icon-tone="green"
      :show-footer="false"
    >
      <div class="perm-intro">
        <p>يتم إدارة المستخدمين من صفحة المستخدمين المخصصة. يمكنك هنا مراجعة صلاحيات كل دور.</p>
        <router-link to="/users" class="btn-primary">
          <i class="pi pi-user-edit"></i>
          <span>الانتقال لإدارة المستخدمين</span>
        </router-link>
      </div>

      <div class="perm-matrix">
        <table>
          <thead>
            <tr>
              <th>الصلاحية</th>
              <th v-for="r in roles" :key="r.key" class="role-col">
                <span class="role-chip" :class="`role-${r.key}`">{{ r.label }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="perm in permissionRows" :key="perm.key">
              <td>
                <div class="perm-name">{{ perm.label }}</div>
                <div class="perm-key">{{ perm.key }}</div>
              </td>
              <td v-for="r in roles" :key="r.key" class="role-col">
                <i
                  :class="canRole(r.key, perm.key) ? 'pi pi-check perm-yes' : 'pi pi-times perm-no'"
                ></i>
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
  { key: 'edit-sms-settings', label: 'تعديل إعدادات الرسائل' },
  { key: 'send-sms', label: 'إرسال الرسائل' },
  { key: 'view-sms-logs', label: 'عرض سجلات الرسائل' },
  { key: 'manage-templates', label: 'إدارة القوالب' },
  { key: 'manage-providers', label: 'إدارة المزودين' },
  { key: 'export-logs', label: 'تصدير السجلات' },
  { key: 'manage-users', label: 'إدارة المستخدمين' }
]

const roleMatrix = {
  super_admin: permissionRows.map(p => p.key),
  employee: ['view-settings', 'view-sms', 'view-sms-logs', 'send-sms', 'manage-templates', 'export-logs'],
  guard: ['view-sms-logs']
}

function canRole(role, perm) {
  if (role === 'super_admin') return true
  return roleMatrix[role]?.includes(perm) ?? false
}
</script>

<style scoped>
.panels-stack { display: flex; flex-direction: column; gap: 20px; }
.perm-intro {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  background: var(--bg-subtle); padding: 14px 18px; border-radius: var(--radius-sm);
  margin-bottom: 20px; flex-wrap: wrap;
}
.perm-intro p { margin: 0; font-size: 13px; color: var(--text-secondary); }
.perm-matrix { overflow-x: auto; }
.perm-matrix table { width: 100%; border-collapse: collapse; min-width: 560px; }
.perm-matrix th, .perm-matrix td { padding: 10px 14px; text-align: center; font-size: 13px; }
.perm-matrix thead th { border-bottom: 1px solid var(--border); }
.perm-matrix tbody td { border-bottom: 1px solid var(--border-light); }
.perm-name { font-weight: 600; color: var(--text-primary); text-align: right; }
.perm-key { font-size: 11px; color: var(--text-muted); direction: ltr; text-align: right; }
.perm-yes { color: var(--success); }
.perm-no { color: var(--border-hover); }
.role-chip {
  display: inline-block; padding: 3px 10px; border-radius: var(--radius-full);
  font-size: 12px; font-weight: 600;
}
</style>
