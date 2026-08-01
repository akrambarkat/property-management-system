<template>
  <div class="page-view">
    <EnterpriseTable
      :value="templates"
      :loading="loading"
      searchPlaceholder="بحث في القوالب..."
      emptyTitle="لا توجد قوالب"
      emptySubtitle="أنشئ قالبك الأول لإرسال رسائل احترافية"
      :columns="tableColumns"
      @refresh="fetchTemplates"
    >
      <template #filters>
        <Select
          v-model="statusFilter"
          :options="[{ value: 'all', label: 'كل الحالات' }, { value: 'active', label: 'مفعّل' }, { value: 'inactive', label: 'معطّل' }]"
          optionLabel="label"
          optionValue="value"
          class="filter-select"
          @change="fetchTemplates"
        />
      </template>

      <template #actions>
        <button class="btn-primary" @click="openCreate">
          <i class="pi pi-plus"></i>
          <span>قالب جديد</span>
        </button>
      </template>

      <template #default="{ hiddenColumns }">
        <Column v-if="!hiddenColumns.includes('title')" field="title" header="العنوان" sortable style="min-width: 180px;">
          <template #body="s">
            <div class="tpl-title-cell">
              <div class="tpl-icon"><i class="pi pi-comment"></i></div>
              <div>
                <strong>{{ s.data.title }}</strong>
                <span class="tpl-key">{{ s.data.key }}</span>
              </div>
            </div>
          </template>
        </Column>
        <Column v-if="!hiddenColumns.includes('subject')" field="subject" header="الموضوع" style="min-width: 160px;">
          <template #body="s">
            <span class="text-secondary">{{ s.data.subject || '—' }}</span>
          </template>
        </Column>
        <Column v-if="!hiddenColumns.includes('message')" field="message" header="الرسالة" style="min-width: 260px;">
          <template #body="s">
            <div class="msg-preview">{{ s.data.message }}</div>
          </template>
        </Column>
        <Column header="الحالة" style="width: 110px;">
          <template #body="s">
            <span class="status-badge" :class="s.data.is_active ? 'status-active' : 'status-neutral'">
              {{ s.data.is_active ? 'مفعّل' : 'معطّل' }}
            </span>
          </template>
        </Column>
        <Column v-if="!hiddenColumns.includes('variables')" field="variables" header="المتغيرات" style="min-width: 200px;">
          <template #body="s">
            <div class="var-chips">
              <span v-for="v in (s.data.variables || []).slice(0, 3)" :key="v" class="var-chip">{{ v }}</span>
              <span v-if="(s.data.variables || []).length > 3" class="var-more">+{{ s.data.variables.length - 3 }}</span>
            </div>
          </template>
        </Column>
        <Column header="الإجراءات" style="width: 80px; text-align: center;" frozen alignFrozen="right">
          <template #body="s">
            <TableActionMenu :items="getRowActions(s.data)" />
          </template>
        </Column>
      </template>
    </EnterpriseTable>

    <!-- Create / Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'تعديل القالب' : 'قالب جديد'"
      modal
      :style="{ width: '720px' }"
      class="saas-dialog"
      :onHide="handleDialogHide"
    >
      <div class="dialog-body">
        <div class="form-section">
          <div class="form-section-title"><i class="pi pi-comment"></i><span>محتوى القالب</span></div>
          <div class="form-grid-2" style="margin-top: 14px;">
            <FormField label="العنوان" required forId="tpl-title" :errorMessage="errors.title">
              <InputText id="tpl-title" v-model="form.title" class="w-full" placeholder="تذكير بدفع الإيجار" @input="clearError('title')" />
            </FormField>
            <FormField label="المعرف" forId="tpl-key" helpText="معرف فريد (اختياري)" :errorMessage="errors.key">
              <InputText id="tpl-key" v-model="form.key" class="w-full" dir="ltr" placeholder="rent_reminder" @input="clearError('key')" />
            </FormField>
          </div>
          <div class="form-field" style="margin-top: 14px;">
            <label for="tpl-subject">الموضوع</label>
            <InputText id="tpl-subject" v-model="form.subject" class="w-full" placeholder="تذكير بدفع الإيجار" />
          </div>
          <div class="form-field" style="margin-top: 14px;">
            <label for="tpl-message">نص الرسالة</label>
            <Textarea
              id="tpl-message"
              v-model="form.message"
              rows="5"
              class="w-full"
              placeholder="عزيزي {{tenant_name}}، ..."
            />
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="pi pi-tag"></i><span>المتغيرات المتاحة</span></div>
          <p class="var-hint">انقر على متغير لإدراجه في نص الرسالة</p>
          <div class="var-chips var-picker">
            <button
              v-for="(label, key) in availableVariables"
              :key="key"
              class="var-chip pickable"
              @click="insertVariable(key)"
              :title="label"
            >
              <span>&#123;&#123;{{ key }}&#125;&#125;</span>
            </button>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="pi pi-eye"></i><span>معاينة</span></div>
          <div class="preview-box">
            <p>{{ renderedPreview }}</p>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-secondary" @click="closeDialog">إلغاء</button>
          <button class="btn-primary" @click="saveItem" :disabled="saving">
            <i v-if="saving" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-save"></i>
            <span>{{ saving ? 'جاري الحفظ...' : 'حفظ القالب' }}</span>
          </button>
        </div>
      </div>
    </Dialog>

    <ConfirmModal
      v-model:visible="showDeleteModal"
      title="حذف القالب"
      :message="`هل أنت متأكد من حذف قالب «${selectedTemplate?.title || ''}»؟`"
      variant="danger"
      confirmText="حذف"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import EnterpriseTable from '@/components/common/EnterpriseTable.vue'
import TableActionMenu from '@/components/common/TableActionMenu.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()

const templates = ref([])
const availableVariables = ref({})
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)
const isEditing = ref(false)
const showDeleteModal = ref(false)
const selectedTemplate = ref(null)
const statusFilter = ref('all')

const form = reactive({ id: null, title: '', key: '', subject: '', message: '', variables: [] })
const errors = reactive({ title: '', key: '' })
const initialForm = JSON.stringify(form)

const tableColumns = [
  { field: 'title', header: 'العنوان' },
  { field: 'subject', header: 'الموضوع' },
  { field: 'message', header: 'الرسالة' },
  { field: 'variables', header: 'المتغيرات' }
]

const renderedPreview = computed(() => {
  const sample = {
    tenant_name: 'أكرم بركات', property: 'الإعمار بلازا', building: 'المبنى أ', unit: '12',
    invoice_number: 'INV-2026-0001', amount: '1,000 ₪', remaining: '400 ₪',
    due_date: '31/08/2026', payment_date: '05/08/2026', contract_end: '31/12/2026',
    company: 'شركة الإعمار للعقارات', phone: '0599000000', website: 'www.emaarplus.com'
  }
  return form.message.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi, (m, key) => sample[key] ?? m)
})

onMounted(async () => {
  fetchTemplates()
  if (route.query.new === '1') {
    await new Promise(r => setTimeout(r, 300))
    openCreate()
    router.replace({ path: route.path, query: {} })
  }
})

async function fetchTemplates() {
  loading.value = true
  try {
    const { data } = await api.get('/sms/templates')
    templates.value = data.data
    availableVariables.value = data.variables || {}
  } catch (err) {
    toast.error('خطأ في تحميل القوالب: ' + (err.response?.data?.message || err.message))
  } finally {
    loading.value = false
  }
}

function openCreate() {
  resetForm()
  isEditing.value = false
  showDialog.value = true
}

function editItem(tpl) {
  resetForm()
  Object.assign(form, {
    id: tpl.id, title: tpl.title, key: tpl.key, subject: tpl.subject,
    message: tpl.message, variables: tpl.variables || []
  })
  isEditing.value = true
  showDialog.value = true
}

function resetForm() {
  Object.assign(form, { id: null, title: '', key: '', subject: '', message: '', variables: [] })
  Object.keys(errors).forEach(k => errors[k] = '')
}

function closeDialog() {
  showDialog.value = false
  resetForm()
}

function handleDialogHide() { resetForm() }

function clearError(field) { if (errors[field]) errors[field] = '' }

function validateForm() {
  let ok = true
  if (!form.title.trim()) { errors.title = 'العنوان مطلوب'; ok = false }
  if (!form.message.trim()) { toast.error('نص الرسالة مطلوب'); ok = false }
  return ok
}

function insertVariable(key) {
  const tag = `{{${key}}}`
  form.message += form.message && !form.message.endsWith(' ') ? ' ' + tag : tag
}

async function saveItem() {
  if (!validateForm()) return
  saving.value = true
  try {
    const payload = {
      title: form.title, key: form.key || null, subject: form.subject,
      message: form.message, variables: Object.keys(availableVariables.value)
    }
    if (isEditing.value) {
      await api.put(`/sms/templates/${form.id}`, payload)
      toast.success('تم تعديل القالب بنجاح')
    } else {
      await api.post('/sms/templates', payload)
      toast.success('تم إنشاء القالب بنجاح')
    }
    showDialog.value = false
    resetForm()
    fetchTemplates()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حفظ القالب')
  } finally {
    saving.value = false
  }
}

function getRowActions(tpl) {
  return [
    { label: 'تعديل', icon: 'pi pi-pencil', action: () => editItem(tpl) },
    { label: tpl.is_active ? 'تعطيل' : 'تفعيل', icon: tpl.is_active ? 'pi pi-ban' : 'pi pi-check', action: () => toggleTemplate(tpl) },
    { label: 'حذف', icon: 'pi pi-trash', action: () => { selectedTemplate.value = tpl; showDeleteModal.value = true }, danger: true }
  ]
}

async function toggleTemplate(tpl) {
  try {
    const { data } = await api.patch(`/sms/templates/${tpl.id}/toggle`)
    toast.success(data.message)
    fetchTemplates()
  } catch (err) {
    toast.error('تعذر تغيير حالة القالب')
  }
}

async function confirmDelete() {
  try {
    await api.delete(`/sms/templates/${selectedTemplate.value.id}`)
    toast.success('تم حذف القالب')
    fetchTemplates()
  } catch (err) {
    toast.error(err.response?.data?.message || 'تعذر حذف القالب')
  } finally {
    showDeleteModal.value = false
  }
}
</script>

<style scoped>
.tpl-title-cell { display: flex; align-items: center; gap: 10px; }
.tpl-icon {
  width: 36px; height: 36px; border-radius: var(--radius-sm);
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.tpl-title-cell strong { display: block; font-size: 13.5px; color: var(--text-primary); }
.tpl-key { font-size: 11px; color: var(--text-muted); direction: ltr; display: block; }
.text-secondary { color: var(--text-secondary); }
.msg-preview {
  font-size: 12.5px; color: var(--text-secondary);
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.var-chips { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; }
.var-chip {
  background: var(--bg-subtle); border: 1px solid var(--border);
  color: var(--accent); font-size: 11px; font-weight: 600;
  padding: 2px 8px; border-radius: var(--radius-full);
}
.var-more { font-size: 11px; color: var(--text-muted); }
.var-hint { font-size: 12.5px; color: var(--text-secondary); margin: 8px 0 10px; }
.var-picker { gap: 8px; }
.var-chip.pickable {
  cursor: pointer; background: var(--bg-subtle); border: 1px dashed var(--border-hover);
  transition: all 0.15s ease; padding: 4px 10px;
}
.var-chip.pickable:hover { border-color: var(--accent); background: var(--accent-light); }
.preview-box {
  background: var(--bg-subtle); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 16px; margin-top: 12px;
  white-space: pre-wrap; font-size: 13px; color: var(--text-primary);
  min-height: 80px;
}
</style>
