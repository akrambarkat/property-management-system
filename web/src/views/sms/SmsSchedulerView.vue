<template>
  <div class="page-view">
    <EnterpriseTable
      :value="jobs"
      :loading="loading"
      searchPlaceholder="بحث في قواعد الإرسال..."
      emptyTitle="لا توجد قواعد إرسال"
      emptySubtitle="أنشئ قاعدة إرسال تلقائية لتنبيه المستأجرين"
      :columns="tableColumns"
      @refresh="fetchJobs"
    >
      <template #actions>
        <button class="btn-primary" @click="openCreate">
          <i class="pi pi-plus"></i>
          <span>قاعدة جديدة</span>
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('name')" field="name" header="القاعدة" sortable style="min-width: 170px;">
          <template #body="s">
            <div class="job-name-cell">
              <div class="job-icon" :class="'ev-' + s.data.event_type"><i :class="eventIcon(s.data.event_type)"></i></div>
              <div>
                <strong>{{ s.data.name }}</strong>
                <span class="event-type">{{ eventLabel(s.data.event_type) }}</span>
              </div>
            </div>
          </template>
        </Column>
        <Column header="القالب" style="min-width: 160px;">
          <template #body="s">
            <span v-if="s.data.template">{{ s.data.template.title }}</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>
        <Column field="days_before" header="قبل (يوم)" style="width: 90px;">
          <template #body="s">
            <span v-if="s.data.days_before != null">{{ s.data.days_before }} يوم</span>
            <span v-else class="text-muted">فوري</span>
          </template>
        </Column>
        <Column header="الحالة" style="width: 110px;">
          <template #body="s">
            <span class="status-badge" :class="s.data.is_active ? 'status-active' : 'status-neutral'">
              {{ s.data.is_active ? 'مفعّلة' : 'معطّلة' }}
            </span>
          </template>
        </Column>
        <Column header="آخر تشغيل" style="min-width: 140px;">
          <template #body="s">
            <span v-if="s.data.last_run_at" class="date-cell">{{ formatDate(s.data.last_run_at) }}</span>
            <span v-else class="text-muted">لم يُشغّل بعد</span>
          </template>
        </Column>
        <Column header="الإجراءات" style="width: 80px; text-align: center;" frozen alignFrozen="right">
          <template #body="s">
            <TableActionMenu :items="getRowActions(s.data)" />
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل القاعدة' : 'قاعدة إرسال جديدة'"
      modal
      :style="{ width: '620px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <div class="form-section">
          <div class="form-section-title"><i class="pi pi-bolt"></i><span>إعدادات القاعدة</span></div>
          <div class="form-grid-2" style="margin-top: 14px;">
            <FormField label="اسم القاعدة" required forId="job-name" :errorMessage="errors.name">
              <InputText id="job-name" v-model="form.name" class="w-full" placeholder="تذكير بدفع الإيجار" @input="clearError('name')" />
            </FormField>
            <FormField label="الحدث" required forId="job-event">
              <Select
                id="job-event"
                v-model="form.event_type"
                :options="eventOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
              />
            </FormField>
            <FormField label="قالب الرسالة" required forId="job-template">
              <Select
                id="job-template"
                v-model="form.template_id"
                :options="templates"
                optionLabel="title"
                optionValue="id"
                class="w-full"
                filter
              />
            </FormField>
            <FormField label="الإرسال قبل الحدث بـ (يوم)" forId="job-days" helpText="اتركه فارغًا للإرسال الفوري">
              <InputNumber id="job-days" v-model="form.days_before" class="w-full" :min="0" :max="365" />
            </FormField>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-save"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ القاعدة' }}</span>
          </button>
        </div>
      </div>
    </Dialog>

    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="حذف القاعدة"
      :message="`هل أنت متأكد من حذف قاعدة «${selectedJob?.name || ''}»؟`"
      variant="danger"
      confirmText="حذف"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const jobs = ref([])
const templates = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const showDeleteModal = ref(false)
const selectedJob = ref(null)

const form = reactive({ id: null, name: '', event_type: 'rent_due', template_id: null, days_before: null })
const errors = reactive({ name: '' })

const tableColumns = [
  { field: 'name', header: 'القاعدة' },
  { field: 'days_before', header: 'قبل (يوم)' }
]

const eventOptions = [
  { value: 'rent_due', label: 'استحقاق الإيجار' },
  { value: 'contract_expiry', label: 'انتهاء العقد' },
  { value: 'payment_confirmation', label: 'تأكيد الدفع' },
  { value: 'maintenance', label: 'تحديث صيانة' },
  { value: 'payment_failed', label: 'فشل الدفع' }
]

const eventLabels = {
  rent_due: 'استحقاق الإيجار', contract_expiry: 'انتهاء العقد',
  payment_confirmation: 'تأكيد الدفع', maintenance: 'تحديث صيانة', payment_failed: 'فشل الدفع'
}

const eventIcons = {
  rent_due: 'pi pi-dollar', contract_expiry: 'pi pi-file-edit',
  payment_confirmation: 'pi pi-check-circle', maintenance: 'pi pi-wrench', payment_failed: 'pi pi-exclamation-circle'
}

onMounted(() => { fetchJobs(); fetchTemplates() })

async function fetchJobs() {
  loading.value = true
  try {
    const { data } = await api.get('/sms/jobs')
    jobs.value = data.data
  } catch (err) {
    toast.error('خطأ في تحميل القواعد: ' + (err.response?.data?.message || err.message))
  } finally {
    loading.value = false
  }
}

async function fetchTemplates() {
  try {
    const { data } = await api.get('/sms/templates')
    templates.value = data.data
  } catch (err) { /* optional */ }
}

function eventLabel(e) { return eventLabels[e] || e }
function eventIcon(e) { return eventIcons[e] || 'pi pi-bolt' }

function openCreate() {
  resetForm()
  isEditing.value = false
  showDialog.value = true
}

function editItem(job) {
  resetForm()
  Object.assign(form, {
    id: job.id, name: job.name, event_type: job.event_type,
    template_id: job.template_id, days_before: job.days_before
  })
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  Object.assign(form, { id: null, name: '', event_type: 'rent_due', template_id: null, days_before: null })
  Object.keys(errors).forEach(k => errors[k] = '')
}

function closeDialog() { showDialog.value = false; resetForm() }
function handleDialogHide() { resetForm() }
function clearError(field) { if (errors[field]) errors[field] = '' }

async function saveItem() {
  if (!form.name.trim()) { errors.name = 'اسم القاعدة مطلوب'; return }
  if (!form.template_id) { toast.error('اختر قالب الرسالة'); return }
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/sms/jobs/${form.id}`, form)
      toast.success('تم تعديل القاعدة بنجاح')
    } else {
      await api.post('/sms/jobs', form)
      toast.success('تم إنشاء القاعدة بنجاح')
    }
    showDialog.value = false
    fetchJobs()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ القاعدة')
  } finally {
    saving.value = false
  }
}

function getRowActions(job) {
  return [
    { label: 'تعديل', icon: 'pi pi-pencil', action: () => editItem(job) },
    { label: job.is_active ? 'تعطيل' : 'تفعيل', icon: job.is_active ? 'pi pi-ban' : 'pi pi-check', action: () => toggleJob(job) },
    { label: 'حذف', icon: 'pi pi-trash', action: () => { selectedJob.value = job; showDeleteModal.value = true }, danger: true }
  ]
}

async function toggleJob(job) {
  try {
    const { data } = await api.patch(`/sms/jobs/${job.id}/toggle`)
    toast.success(data.message)
    fetchJobs()
  } catch (err) {
    toast.error('تعذر تغيير حالة القاعدة')
  }
}

async function confirmDelete() {
  try {
    await api.delete(`/sms/jobs/${selectedJob.value.id}`)
    toast.success('تم حذف القاعدة')
    fetchJobs()
  } catch (err) {
    toast.error('تعذر حذف القاعدة')
  } finally {
    showDeleteModal.value = false
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('ar-EG', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<style scoped>
.job-name-cell { display: flex; align-items: center; gap: 10px; }
.job-icon {
  width: 36px; height: 36px; border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center; font-size: 1rem;
}
.ev-rent_due { background: var(--warning-bg); color: var(--warning-contrast); }
.ev-contract_expiry { background: var(--info-bg); color: var(--info-contrast); }
.ev-payment_confirmation { background: var(--success-bg); color: var(--success-contrast); }
.ev-maintenance { background: var(--accent-light); color: var(--accent); }
.ev-payment_failed { background: var(--danger-bg); color: var(--danger-contrast); }
.job-name-cell strong { display: block; font-size: 13.5px; color: var(--text-primary); }
.event-type { font-size: 11.5px; color: var(--text-secondary); }
.date-cell { color: var(--text-secondary); font-size: 12.5px; }
.text-muted { color: var(--text-muted); }
</style>
