<template>
  <div class="page-view settings-shell">
    <div v-if="store.loading" class="settings-loading">
      <div class="skeleton-cell hero-skeleton" />
      <div class="settings-loading-grid">
        <aside class="skeleton-panel">
          <div v-for="i in 9" :key="i" class="skeleton-cell nav-skeleton" />
        </aside>
        <section class="skeleton-panel">
          <div v-for="i in 5" :key="i" class="skeleton-cell panel-skeleton" />
        </section>
      </div>
    </div>

    <template v-else>
      <section class="settings-hero">
        <div class="hero-copy">
          <div class="eyebrow">
            <i class="pi pi-cog"></i>
            <span>مركز الإعدادات</span>
          </div>
          <h1>إعدادات النظام والرسائل والفواتير</h1>
          <p>
            من هنا نضبط الإعدادات الأساسية، بيانات الشركة، SMS، المظهر، الفواتير، الأمان،
            والنسخ الاحتياطي من مكان واحد واضح ومنظم.
          </p>
          <div class="hero-actions">
            <button
              class="btn-primary"
              :disabled="!store.isDirty(activeCategory) || store.savingGroup"
              @click="saveCurrent"
            >
              <i v-if="store.savingGroup" class="pi pi-spin pi-spinner"></i>
              <i v-else class="pi pi-save"></i>
              <span>{{ store.savingGroup ? 'جارٍ الحفظ...' : 'حفظ القسم الحالي' }}</span>
            </button>
            <button class="btn-secondary" @click="refreshAll">
              <i class="pi pi-refresh"></i>
              <span>تحديث البيانات</span>
            </button>
          </div>
        </div>

        <div class="hero-metrics">
          <div v-for="metric in heroMetrics" :key="metric.label" class="metric-card">
            <div class="metric-icon" :class="metric.tone">
              <i :class="metric.icon"></i>
            </div>
            <div class="metric-copy">
              <strong>{{ metric.value }}</strong>
              <span>{{ metric.label }}</span>
            </div>
          </div>
        </div>
      </section>

      <section class="settings-layout">
        <aside class="settings-nav">
          <div class="nav-header">
            <h2>الأقسام</h2>
            <p>انتقل بين مجموعات الإعدادات حسب الموضوع.</p>
          </div>

          <nav class="nav-list">
            <template v-for="section in navSections" :key="section.key">
              <div v-if="section.items.length" class="nav-group">{{ section.label }}</div>
              <button
                v-for="item in section.items"
                :key="item.key"
                class="nav-item"
                :class="{ active: activeCategory === item.key, dirty: dirtyGroups.includes(item.key) }"
                @click="switchCategory(item.key)"
              >
                <i :class="item.icon"></i>
                <span class="nav-copy">
                  <span>{{ item.label }}</span>
                  <small>{{ item.description }}</small>
                </span>
                <span v-if="dirtyGroups.includes(item.key)" class="dirty-dot" title="تغييرات غير محفوظة"></span>
              </button>
            </template>
          </nav>
        </aside>

        <section class="settings-panel">
          <div class="panel-header">
            <div>
              <div class="panel-eyebrow">
                <i :class="activeCategoryMeta.icon"></i>
                <span>{{ activeCategoryMeta.groupLabel }}</span>
              </div>
              <h2>{{ activeCategoryMeta.label }}</h2>
              <p>{{ activeCategoryMeta.description }}</p>
            </div>

            <div class="panel-meta">
              <span class="status-chip" :class="{ dirty: dirtyGroups.includes(activeCategory) }">
                <i :class="dirtyGroups.includes(activeCategory) ? 'pi pi-exclamation-circle' : 'pi pi-check-circle'"></i>
                {{ dirtyGroups.includes(activeCategory) ? 'تغييرات غير محفوظة' : 'الوضع مستقر' }}
              </span>
              <span v-if="store.lastSavedAt" class="panel-note">
                آخر حفظ: {{ formatDate(store.lastSavedAt) }}
              </span>
            </div>
          </div>

          <div class="focus-strip">
            <span v-for="item in activeCategoryMeta.focus" :key="item" class="focus-chip">{{ item }}</span>
          </div>

          <transition name="fade-slide" mode="out-in">
            <component
              :is="activeComponent"
              :key="activeCategory"
              :settings="store.groups[activeCategory]"
              :dirty="store.isDirty(activeCategory)"
              :saving="store.savingGroup === activeCategory"
              @save="saveCurrent"
            />
          </transition>
        </section>
      </section>
    </template>

    <ConfirmModal
      v-model:visible="showLeaveConfirm"
      title="تغييرات غير محفوظة"
      message="هناك تغييرات لم يتم حفظها بعد. هل تريد المتابعة بدون حفظها؟"
      variant="warning"
      confirmText="متابعة"
      cancelText="إلغاء"
      @confirm="proceedLeave"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useSettingsStore } from '@/stores/settings'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

import GeneralPanel from './panels/GeneralPanel.vue'
import CompanyPanel from './panels/CompanyPanel.vue'
import SmsPanel from './panels/SmsPanel.vue'
import NotificationsPanel from './panels/NotificationsPanel.vue'
import InvoicesPanel from './panels/InvoicesPanel.vue'
import ContractsPanel from './panels/ContractsPanel.vue'
import AppearancePanel from './panels/AppearancePanel.vue'
import SecurityPanel from './panels/SecurityPanel.vue'
import BackupPanel from './panels/BackupPanel.vue'
import SystemPanel from './panels/SystemPanel.vue'
import UsersPanel from './panels/UsersPanel.vue'
import ActivityLogsPanel from './panels/ActivityLogsPanel.vue'

const store = useSettingsStore()
const activeCategory = ref('general')
const showLeaveConfirm = ref(false)
let pendingCategory = null

const categories = [
  { key: 'general', label: 'عام', icon: 'pi pi-sliders-h', component: GeneralPanel, section: 'system', groupLabel: 'الإعدادات الأساسية', description: 'اسم التطبيق، العملة، وبادئات المستندات.', focus: ['الهوية العامة', 'العملة', 'البادئات'] },
  { key: 'appearance', label: 'المظهر', icon: 'pi pi-palette', component: AppearancePanel, section: 'system', groupLabel: 'واجهة الاستخدام', description: 'اللغة، تنسيق التاريخ، والوضع الليلي.', focus: ['اللغة', 'التنسيق', 'الوضع الداكن'] },
  { key: 'company', label: 'بيانات الشركة', icon: 'pi pi-building', component: CompanyPanel, section: 'system', groupLabel: 'الهوية المؤسسية', description: 'معلومات الشركة الرسمية المستخدمة في الفواتير والتقارير.', focus: ['الاسم القانوني', 'العنوان', 'الهوية الضريبية'] },
  { key: 'sms', label: 'SMS', icon: 'pi pi-send', component: SmsPanel, section: 'comms', groupLabel: 'بوابة الرسائل', description: 'إعداد مزود الرسائل واختبار الاتصال وإدارة القوالب.', focus: ['المزود', 'الاتصال', 'الأمان'] },
  { key: 'notifications', label: 'التنبيهات', icon: 'pi pi-bell', component: NotificationsPanel, section: 'comms', groupLabel: 'سياسات التنبيه', description: 'قنوات الإشعارات والتذكيرات التلقائية المرتبطة بالإيجار.', focus: ['القنوات', 'التذكير', 'القوالب'] },
  { key: 'invoices', label: 'الفواتير', icon: 'pi pi-file-invoice', component: InvoicesPanel, section: 'finance', groupLabel: 'التحصيل والفوترة', description: 'بادئات الفواتير والإعدادات المالية الأساسية.', focus: ['الترقيم', 'الضرائب', 'الاستحقاق'] },
  { key: 'contracts', label: 'العقود', icon: 'pi pi-file-edit', component: ContractsPanel, section: 'finance', groupLabel: 'إدارة العقود', description: 'المدة، التذكير، والتجديد التلقائي للعقود.', focus: ['التجديد', 'التذكير', 'الشروط'] },
  { key: 'security', label: 'الأمان', icon: 'pi pi-shield', component: SecurityPanel, section: 'admin', groupLabel: 'الحماية', description: 'كلمة المرور، الجلسات، والمصادقة الثنائية.', focus: ['كلمة المرور', 'الجلسة', 'التحقق'] },
  { key: 'backup', label: 'النسخ الاحتياطي', icon: 'pi pi-database', component: BackupPanel, section: 'admin', groupLabel: 'الاستمرارية', description: 'سياسة النسخ والاستعادة والاحتفاظ.', focus: ['الجدولة', 'الاحتفاظ', 'الوجهة'] },
  { key: 'users', label: 'المستخدمون', icon: 'pi pi-users', component: UsersPanel, section: 'admin', groupLabel: 'الصلاحيات', description: 'مرجع سريع للصلاحيات والأدوار.', focus: ['الأدوار', 'الوصول', 'الإدارة'] },
  { key: 'activity', label: 'سجل النشاط', icon: 'pi pi-history', component: ActivityLogsPanel, section: 'admin', groupLabel: 'التدقيق', description: 'عرض سجل التغييرات والأحداث داخل النظام.', focus: ['التغييرات', 'التدقيق', 'الأثر الأمني'] },
  { key: 'system', label: 'النظام', icon: 'pi pi-cog', component: SystemPanel, section: 'system', groupLabel: 'صحة النظام', description: 'إعدادات التشغيل العامة والقيود الفنية.', focus: ['الصيانة', 'الرفع', 'المراقبة'] }
]

const navSections = [
  { key: 'system', label: 'عام والشركة', items: categories.filter(item => item.section === 'system') },
  { key: 'comms', label: 'الرسائل والتنبيهات', items: categories.filter(item => item.section === 'comms') },
  { key: 'finance', label: 'المالية والعقود', items: categories.filter(item => item.section === 'finance') },
  { key: 'admin', label: 'الأمان والإدارة', items: categories.filter(item => item.section === 'admin') }
]

const activeCategoryMeta = computed(() => categories.find(item => item.key === activeCategory.value) || categories[0])
const activeComponent = computed(() => categories.find(item => item.key === activeCategory.value)?.component)
const dirtyGroups = computed(() => store.dirtyGroups)

const heroMetrics = computed(() => [
  { label: 'أقسام تحتاج حفظ', value: dirtyGroups.value.length, icon: 'pi pi-exclamation-triangle', tone: 'warning' },
  { label: 'آخر حفظ', value: store.lastSavedAt ? formatShortDate(store.lastSavedAt) : 'لم يتم', icon: 'pi pi-clock', tone: 'blue' },
  { label: 'SMS', value: store.groups.sms?.sms_api_url ? 'جاهز' : 'غير مكتمل', icon: 'pi pi-send', tone: store.groups.sms?.sms_api_url ? 'green' : 'warning' },
  { label: 'الأمان', value: store.groups.security?.two_factor ? 'مفعل' : 'أساسي', icon: 'pi pi-shield', tone: store.groups.security?.two_factor ? 'green' : 'blue' }
])

function switchCategory(key) {
  if (key !== activeCategory.value && store.dirtyGroups.length > 0) {
    pendingCategory = key
    showLeaveConfirm.value = true
    return
  }
  activeCategory.value = key
}

function proceedLeave() {
  showLeaveConfirm.value = false
  if (pendingCategory) {
    activeCategory.value = pendingCategory
    pendingCategory = null
  }
}

function saveCurrent() {
  return store.saveGroup(activeCategory.value)
}

function refreshAll() {
  store.fetchAll()
}

function formatDate(value) {
  if (!value) return '—'
  const date = value instanceof Date ? value : new Date(value)
  return date.toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' })
}

function formatShortDate(value) {
  if (!value) return '—'
  const date = value instanceof Date ? value : new Date(value)
  return date.toLocaleDateString('ar-EG', { month: 'short', day: 'numeric' })
}

onMounted(() => {
  if (!store.loaded) store.fetchAll()
})

onBeforeRouteLeave(() => {
  if (store.dirtyGroups.length > 0) {
    showLeaveConfirm.value = true
    return false
  }
  return true
})

watch(showLeaveConfirm, (visible) => {
  if (!visible) pendingCategory = null
})
</script>

<style scoped>
.settings-shell {
  min-height: calc(100vh - 160px);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.settings-loading,
.settings-loading-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.hero-skeleton {
  height: 180px;
  border-radius: 20px;
}

.settings-loading-grid {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
}

.skeleton-panel {
  background: var(--bg-surface);
  border: 1px solid var(--border-light);
  border-radius: 20px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.nav-skeleton,
.panel-skeleton {
  height: 44px;
  border-radius: 14px;
}

.settings-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.9fr);
  gap: 20px;
  padding: 28px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(79, 70, 229, 0.15), transparent 30%),
    linear-gradient(135deg, var(--bg-surface), var(--bg-subtle));
  box-shadow: var(--shadow-sm);
}

.hero-copy {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.eyebrow,
.panel-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  width: fit-content;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--accent-light);
  color: var(--accent);
  font-size: 12px;
  font-weight: 700;
}

.hero-copy h1,
.panel-header h2 {
  margin: 0;
  color: var(--text-primary);
  line-height: 1.2;
}

.hero-copy h1 {
  font-size: clamp(24px, 2.6vw, 36px);
}

.hero-copy p,
.panel-header p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.8;
}

.hero-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.hero-metrics {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  align-content: start;
}

.metric-card {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 16px;
  border: 1px solid var(--border-light);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.56);
  backdrop-filter: blur(8px);
}

.metric-icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.metric-icon.warning { background: var(--warning-bg); color: var(--warning-contrast); }
.metric-icon.blue { background: var(--info-bg); color: var(--info-contrast); }
.metric-icon.green { background: var(--success-bg); color: var(--success-contrast); }

.metric-copy {
  display: flex;
  flex-direction: column;
}

.metric-copy strong {
  font-size: 18px;
  color: var(--text-primary);
}

.metric-copy span {
  font-size: 12px;
  color: var(--text-secondary);
}

.settings-layout {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

.settings-nav,
.settings-panel {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: 24px;
  box-shadow: var(--shadow-sm);
}

.settings-nav {
  padding: 18px;
  position: sticky;
  top: 20px;
}

.nav-header h2 {
  margin: 0;
  font-size: 18px;
  color: var(--text-primary);
}

.nav-header p {
  margin: 6px 0 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.nav-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 16px;
}

.nav-group {
  margin: 12px 0 4px;
  padding: 0 8px;
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 14px;
  border: 1px solid transparent;
  border-radius: 16px;
  background: transparent;
  cursor: pointer;
  text-align: right;
  transition: all 0.15s ease;
}

.nav-item i {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-subtle);
  color: var(--text-secondary);
  flex-shrink: 0;
}

.nav-copy {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.nav-copy span {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
}

.nav-copy small {
  font-size: 11.5px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.nav-item:hover {
  background: var(--bg-subtle);
}

.nav-item.active {
  background: var(--accent-light);
  border-color: rgba(79, 70, 229, 0.2);
}

.nav-item.active i,
.nav-item.active .nav-copy span {
  color: var(--accent);
}

.dirty-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: var(--warning);
  flex-shrink: 0;
}

.settings-panel {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.panel-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: var(--bg-subtle);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 700;
}

.status-chip.dirty {
  background: var(--warning-bg);
  color: var(--warning-contrast);
}

.panel-note {
  font-size: 12px;
  color: var(--text-muted);
}

.focus-strip {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.focus-chip {
  padding: 7px 12px;
  border-radius: 999px;
  background: var(--bg-subtle);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
}

@media (max-width: 1100px) {
  .settings-hero,
  .settings-layout,
  .settings-loading-grid {
    grid-template-columns: 1fr;
  }

  .settings-nav {
    position: static;
  }
}

@media (max-width: 700px) {
  .settings-hero,
  .settings-panel {
    padding: 18px;
  }

  .hero-metrics {
    grid-template-columns: 1fr;
  }

  .panel-header {
    flex-direction: column;
  }

  .panel-meta {
    align-items: flex-start;
  }
}
</style>
