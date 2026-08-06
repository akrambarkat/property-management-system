<template>
  <div class="saas-table-wrapper" :class="{ 'is-loading': loading }">
    <!-- Top Action & Filter Toolbar -->
    <div class="table-toolbar">
      <!-- Search & Filters -->
      <div class="toolbar-search-filters">
        <div v-if="showSearch" class="search-box">
          <i class="pi pi-search search-icon"></i>
          <input
            type="text"
            v-model="searchQuery"
            :placeholder="searchPlaceholder"
            class="search-input"
          />
          <span v-if="searchQuery" class="clear-search-btn" @click="searchQuery = ''" title="مسح">×</span>
        </div>

        <!-- Filters: inline on desktop/tablet, docked bottom sheet on mobile -->
        <template v-if="hasFiltersSlot">
          <div class="filters-region" :class="{ 'mobile-open': filtersOpen }">
            <div class="filters-region-inner">
              <div class="filters-region-header">
                <span class="filters-sheet-title"><i class="pi pi-filter"></i> تصفية النتائج</span>
                <button type="button" class="filters-sheet-close" @click="filtersOpen = false" aria-label="إغلاق">×</button>
              </div>
              <div class="filters-region-body">
                <slot name="filters"></slot>
              </div>
              <div class="filters-region-footer">
                <button type="button" class="btn-primary filters-sheet-apply" @click="filtersOpen = false">
                  <i class="pi pi-check"></i> عرض النتائج
                </button>
              </div>
            </div>
          </div>
          <button type="button" class="btn-toolbar filters-toggle-btn" @click="filtersOpen = true">
            <i class="pi pi-filter"></i>
            <span class="btn-label">تصفية</span>
          </button>
        </template>
      </div>

      <!-- Actions (Bulk, Column Toggle, Export, Print, Refresh, Add) -->
      <div class="toolbar-actions">
        <!-- Multi-select Bulk Actions Pill -->
        <transition name="fade">
          <div v-if="selectedRows && selectedRows.length > 0" class="bulk-actions-pill">
            <span class="bulk-count">
              <i class="pi pi-check-square"></i> تم تحديد {{ selectedRows.length }} عنصر
            </span>
            <slot name="bulk-actions" :selected="selectedRows">
              <button class="btn-bulk-action" @click="exportSelected" title="تصدير المحدد">
                <i class="pi pi-download"></i> تصدير
              </button>
            </slot>
          </div>
        </transition>

        <!-- Column Visibility Dropdown -->
        <div v-if="columns && columns.length > 0" class="column-visibility-dropdown" ref="colMenuRef">
          <button
            type="button"
            class="btn-toolbar"
            @click="showColumnMenu = !showColumnMenu"
            title="إدارة الأعمدة"
          >
            <i class="pi pi-sliders-h"></i>
            <span class="btn-label">الأعمدة</span>
          </button>

          <transition name="dropdown-fade">
            <div v-if="showColumnMenu" class="columns-menu">
              <div class="menu-header">إظهار/إخفاء الأعمدة</div>
              <div
                v-for="col in columns"
                :key="col.field || col.header"
                class="column-item"
                @click="toggleColumn(col)"
              >
                <input
                  type="checkbox"
                  :checked="!hiddenColumns.includes(col.field || col.header)"
                  readonly
                />
                <span>{{ col.header }}</span>
              </div>
            </div>
          </transition>
        </div>

        <!-- Export Dropdown (PDF / Excel / CSV) -->
        <div class="export-dropdown" ref="exportMenuRef">
          <button
            type="button"
            class="btn-toolbar export-trigger"
            @click="toggleExportMenu"
            title="تصدير"
          >
            <i class="pi pi-download"></i>
            <span class="btn-label">تصدير</span>
            <i class="pi pi-angle-down export-caret"></i>
          </button>

          <transition name="dropdown-fade">
            <div v-if="showExportMenu" class="export-menu">
              <div class="menu-header">اختر صيغة التصدير</div>
              <button class="menu-item" @click="downloadServer('pdf')">
                <i class="pi pi-file-pdf"></i> <span>PDF</span>
              </button>
              <button class="menu-item" @click="downloadServer('excel')">
                <i class="pi pi-file-excel"></i> <span>Excel</span>
              </button>
              <button class="menu-item" @click="downloadServer('csv')">
                <i class="pi pi-file"></i> <span>CSV</span>
              </button>
            </div>
          </transition>
        </div>

        <!-- Refresh Button -->
        <button class="btn-toolbar" @click="$emit('refresh')" title="تحديث البيانات">
          <i class="pi pi-refresh" :class="{ 'pi-spin': loading }"></i>
        </button>

        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Datatable Surface Card Container -->
    <div class="table-surface-card">
      <!-- Skeleton Loading State -->
      <div v-if="loading && (!value || !value.length)" class="skeleton-table-container">
        <div class="skeleton-header">
          <div v-for="i in skeletonCols" :key="'h-'+i" class="skeleton-cell header-cell"></div>
        </div>
        <div v-for="r in rowsPerPage" :key="'r-'+r" class="skeleton-row">
          <div v-for="c in skeletonCols" :key="'c-'+c" class="skeleton-cell" :style="{ width: getSkeletonWidth(c) }"></div>
        </div>
      </div>

      <!-- PrimeVue Datatable Integration -->
      <DataTable
        v-else
        v-model:selection="selectedRows"
        :value="filteredValue"
        stripedRows
        paginator
        :rows="rowsPerPage"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        responsiveLayout="scroll"
        :pt="responsiveTablePT"
        class="enterprise-saas-table sticky-header-table responsive-table"
        @row-click="onRowClick"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
        currentPageReportTemplate="عرض {first} إلى {last} من {totalRecords} سجل"
      >
        <!-- Checkbox Selection Column -->
        <Column v-if="selectable" selectionMode="multiple" headerStyle="width: 3rem" />

        <slot :hiddenColumns="effectiveHidden"></slot>

        <!-- Empty State -->
        <template #empty>
          <div class="table-empty-state">
            <div class="empty-icon-circle">
              <i class="pi pi-inbox"></i>
            </div>
            <h4>{{ emptyTitle }}</h4>
            <p>{{ emptySubtitle }}</p>
            <slot name="empty-action"></slot>
          </div>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, useSlots } from 'vue'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const slots = useSlots()

const props = defineProps({
  value: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  selectable: { type: Boolean, default: true },
  showSearch: { type: Boolean, default: true },
  searchPlaceholder: { type: String, default: 'بحث شامل في الجدول...' },
  emptyTitle: { type: String, default: 'لا توجد بيانات متاحة' },
  emptySubtitle: { type: String, default: 'لم يتم العثور على أي سجلات تطابق شروط البحث الحالية' },
  columns: { type: Array, default: () => [] }, // Format: [{ field: 'name', header: 'اسم', tabletHidden: true }]
  skeletonCols: { type: Number, default: 5 },
  entity: { type: String, default: '' }, // Backend export entity key (e.g. 'buildings')
  exportParams: { type: Object, default: () => ({}) }, // Filters passed to the export endpoint
  mobileBreakpoint: { type: Number, default: 768 }, // Below this → rows become cards
  tabletBreakpoint: { type: Number, default: 992 } // Below this → tabletHidden columns collapse
})

const emit = defineEmits(['row-click', 'export', 'bulk-export', 'refresh', 'update:selection'])

const searchQuery = ref('')
const selectedRows = ref([])
const rowsPerPage = ref(15)
const hiddenColumns = ref([])
const showColumnMenu = ref(false)
const colMenuRef = ref(null)
const showExportMenu = ref(false)
const exportMenuRef = ref(null)
const exporting = ref(false)
const filtersOpen = ref(false)
const isMobile = ref(false)
const isTablet = ref(false)

const hasFiltersSlot = computed(() => !!slots.filters)

const filteredValue = computed(() => {
  if (!searchQuery.value.trim() || !props.value) return props.value
  const q = searchQuery.value.toLowerCase().trim()
  return props.value.filter(row => {
    return Object.values(row).some(val => {
      if (val === null || val === undefined) return false
      if (typeof val === 'object') {
        return Object.values(val).some(nestedVal =>
          nestedVal !== null && nestedVal !== undefined && String(nestedVal).toLowerCase().includes(q)
        )
      }
      return String(val).toLowerCase().includes(q)
    })
  })
})

/* Combine user-toggled hidden columns with responsive (tablet) hidden columns
   so views can collapse low-priority columns automatically on tablet. */
const effectiveHidden = computed(() => {
  const list = [...hiddenColumns.value]
  if (isTablet.value) {
    ;(props.columns || []).forEach(col => {
      if (col.tabletHidden) {
        const key = col.field || col.header
        if (key && !list.includes(key)) list.push(key)
      }
    })
  }
  return list
})

/* Inject the column header as data-label on every body cell so the mobile
   "rows as cards" layout can display each field's label via CSS. */
const responsiveTablePT = {
  bodyCell: ({ props: colProps }) => {
    if (!colProps || !colProps.header) return {}
    return { 'data-label': colProps.header }
  }
}

function updateViewport() {
  const w = window.innerWidth
  isMobile.value = w < props.mobileBreakpoint
  isTablet.value = w < props.tabletBreakpoint
  if (!isMobile.value) filtersOpen.value = false
}

function toggleColumn(col) {
  const colKey = col.field || col.header
  const idx = hiddenColumns.value.indexOf(colKey)
  if (idx > -1) {
    hiddenColumns.value.splice(idx, 1)
  } else {
    hiddenColumns.value.push(colKey)
  }
}

function onRowClick(event) {
  emit('row-click', event)
}

function getSkeletonWidth(colIndex) {
  const widths = ['35%', '20%', '25%', '15%', '10%']
  return widths[(colIndex - 1) % widths.length]
}

function exportCSV() {
  if (!props.value || !props.value.length) return

  const headers = props.columns.length
    ? props.columns.map(c => c.header).join(',')
    : Object.keys(props.value[0]).join(',')

  const rows = props.value.map(row => {
    if (props.columns.length) {
      return props.columns.map(c => {
        const val = row[c.field] !== undefined ? row[c.field] : ''
        return `"${String(val).replace(/"/g, '""')}"`
      }).join(',')
    }
    return Object.values(row).map(val => `"${String(val || '').replace(/"/g, '""')}"`).join(',')
  })

  const csvContent = '\uFEFF' + [headers, ...rows].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.setAttribute('download', `table_export_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  emit('export', props.value)
}

function toggleExportMenu() {
  showExportMenu.value = !showExportMenu.value
}

async function downloadServer(format) {
  if (exporting.value) return
  showExportMenu.value = false
  if (!props.entity) {
    if (format === 'csv') {
      exportCSV()
      return
    }
    toast.error('التصدير PDF/Excel غير متاح لهذا الجدول')
    return
  }
  exporting.value = true
  try {
    const params = { entity: props.entity, format }
    for (const [key, val] of Object.entries(props.exportParams || {})) {
      if (val !== null && val !== undefined && val !== '') params[key] = val
    }
    const { data } = await api.get('/reports/export', { params, responseType: 'blob' })
    const ext = format === 'pdf' ? 'pdf' : 'csv'
    const url = URL.createObjectURL(new Blob([data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${props.entity}_${new Date().toISOString().slice(0, 10)}.${ext}`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    toast.error('تعذر تصدير البيانات: ' + (err.response?.data?.message || err.message))
  } finally {
    exporting.value = false
  }
}

function exportSelected() {
  emit('bulk-export', selectedRows.value)
}

function handleClickOutside(event) {
  if (colMenuRef.value && !colMenuRef.value.contains(event.target)) {
    showColumnMenu.value = false
  }
  if (exportMenuRef.value && !exportMenuRef.value.contains(event.target)) {
    showExportMenu.value = false
  }
}

onMounted(() => {
  updateViewport()
  window.addEventListener('resize', updateViewport)
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateViewport)
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.saas-table-wrapper {
  display: flex;
  flex-direction: column;
  gap: 14px;
  width: 100%;
}

.table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.toolbar-search-filters {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  flex: 1;
  min-width: 0;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  flex: 0 1 auto;
}

.search-icon {
  position: absolute;
  right: 12px;
  color: var(--text-muted, #94A3B8);
  font-size: 0.9rem;
  pointer-events: none;
}

.search-input {
  padding: 8px 36px 8px 30px !important;
  width: 280px !important;
  background: var(--input-bg) !important;
  border: 1px solid var(--input-border) !important;
  border-radius: var(--radius-sm, 6px) !important;
  font-size: 13px !important;
  color: var(--text-primary) !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--border-active) !important;
  box-shadow: var(--shadow-focus) !important;
}

.clear-search-btn {
  position: absolute;
  left: 10px;
  color: var(--text-muted, #94A3B8);
  cursor: pointer;
  font-size: 14px;
  font-weight: bold;
}

.clear-search-btn:hover {
  color: var(--text-primary, #0F172A);
}

/* ---- Filters region: inline on desktop, bottom sheet on mobile ---- */
.filters-region {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.filters-region-inner {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.filters-region-header,
.filters-region-footer,
.filters-toggle-btn {
  display: none;
}

/* ---- Toolbar actions ---- */
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-toolbar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-sm, 6px);
  color: var(--text-primary, #1E293B);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: var(--shadow-xs);
  font-family: var(--font-family);
}

.btn-toolbar:hover {
  background: var(--bg-hover);
  border-color: var(--border-hover);
  color: var(--text-primary);
}

.bulk-actions-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--bg-active);
  color: var(--text-primary);
  border: 1px solid var(--border-hover);
  padding: 4px 14px;
  border-radius: var(--radius-full, 9999px);
  font-size: 12.5px;
  font-weight: 500;
  box-shadow: var(--shadow-sm);
}

.bulk-count {
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-bulk-action {
  background: var(--accent);
  color: var(--accent-contrast);
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 11.5px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: background 0.15s ease;
}

.btn-bulk-action:hover {
  background: var(--accent-hover);
}

.column-visibility-dropdown {
  position: relative;
}

.columns-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  z-index: 1000;
  width: 200px;
  max-width: calc(100vw - 24px);
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-md, 8px);
  box-shadow: var(--shadow-lg);
  padding: 8px;
}

.menu-header {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text-muted, #64748B);
  text-transform: uppercase;
  padding: 4px 8px 8px 8px;
  border-bottom: 1px solid var(--border, #E2E8F0);
  margin-bottom: 4px;
}

.column-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  font-size: 13px;
  cursor: pointer;
  border-radius: 4px;
}

.column-item:hover {
  background: var(--bg-subtle, #F1F5F9);
}

.export-dropdown {
  position: relative;
}

.export-caret {
  font-size: 10px;
  margin-left: 2px;
}

.export-menu {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  z-index: 1000;
  width: 180px;
  max-width: calc(100vw - 24px);
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-md, 8px);
  box-shadow: var(--shadow-lg);
  padding: 8px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px;
  font-size: 13px;
  font-family: inherit;
  color: var(--text-primary, #0F172A);
  background: transparent;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-align: right;
}

.menu-item:hover {
  background: var(--bg-subtle, #F1F5F9);
}

.menu-item .pi-file-pdf { color: var(--danger, #EF4444); }
.menu-item .pi-file-excel { color: var(--success, #16A34A); }
.menu-item .pi-file { color: var(--info-contrast, #2563EB); }

.export-trigger.is-exporting { opacity: 0.6; pointer-events: none; }

.table-surface-card {
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-md, 10px);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
  overflow: hidden;
  width: 100%;
  min-width: 0;
}

/* Skeleton Loading */
.skeleton-table-container {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-header {
  display: flex;
  gap: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--border-light, #F1F5F9);
}

.skeleton-row {
  display: flex;
  gap: 16px;
  padding: 8px 0;
}

.skeleton-cell {
  height: 18px;
  background: linear-gradient(90deg, var(--bg-skeleton-base) 25%, var(--bg-skeleton-highlight) 50%, var(--bg-skeleton-base) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skeleton-cell.header-cell {
  height: 22px;
  background: var(--bg-skeleton-highlight);
  flex: 1;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Empty State */
.table-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 20px;
  gap: 10px;
  color: var(--text-muted, #94A3B8);
}

.empty-icon-circle {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: var(--bg-subtle, #F1F5F9);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  color: var(--text-secondary, #64748B);
  margin-bottom: 4px;
}

.table-empty-state h4 {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary, #0F172A);
  margin: 0;
}

.table-empty-state p {
  font-size: 13px;
  color: var(--text-secondary, #64748B);
  margin: 0;
  text-align: center;
  max-width: 420px;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* ==================================================================
   RESPONSIVE TABLE LAYER
   Desktop:   normal DataTable
   Tablet:    reduced spacing + low-priority columns collapse
   Mobile:    rows transform into premium cards (no horizontal scroll)
   ================================================================== */

@media (max-width: 992px) {
  .search-input { width: 220px !important; }
  .table-surface-card { border-radius: var(--radius-sm, 8px); }
}

@media (max-width: 768px) {
  .table-toolbar { gap: 10px; }

  .toolbar-search-filters {
    width: 100%;
  }

  .search-box { width: 100%; }
  .search-input { width: 100% !important; }

  /* Filters: docked bottom sheet, opened via toggle button */
  .filters-toggle-btn { display: inline-flex; }

  .filters-region {
    display: none;
    position: fixed;
    inset-inline: 12px;
    bottom: 12px;
    left: 12px;
    right: 12px;
    z-index: 1200;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow-xl);
    padding: 16px;
    flex-direction: column;
    align-items: stretch;
  }
  .filters-region.mobile-open { display: flex; }

  .filters-region-inner {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .filters-region-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-light);
  }
  .filters-sheet-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-primary);
  }
  .filters-sheet-title i { color: var(--accent); }
  .filters-sheet-close {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    border: none;
    background: var(--bg-subtle);
    color: var(--text-secondary);
    font-size: 16px;
    cursor: pointer;
  }

  .filters-region-body {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .filters-region-body :deep(.filter-select),
  .filters-region-body :deep(.p-select),
  .filters-region-body :deep(.p-datepicker) {
    width: 100% !important;
    min-width: 0 !important;
  }

  .filters-region-footer { display: block; }
  .filters-sheet-apply { width: 100%; justify-content: center; }

  .toolbar-actions { width: 100%; }
  .toolbar-actions > .btn-toolbar { flex: 1 1 auto; }
  .toolbar-actions > .btn-primary { flex: 1 1 auto; justify-content: center; }

  .bulk-actions-pill {
    width: 100%;
    justify-content: space-between;
  }

  .columns-menu, .export-menu {
    position: fixed;
    top: auto;
    left: 12px;
    right: 12px;
    bottom: 12px;
    width: auto;
  }
}

/* ---- Mobile: DataTable rows → cards ---- */
@media (max-width: 768px) {
  .responsive-table :deep(.p-datatable-wrapper) {
    overflow: visible !important;
  }

  .responsive-table :deep(.p-datatable-table) {
    display: block;
    width: 100% !important;
    table-layout: auto !important;
  }

  .responsive-table :deep(.p-datatable-thead) { display: none; }
  .responsive-table :deep(.p-datatable-tfoot) { display: none; }

  .responsive-table :deep(.p-datatable-tbody) { display: block; }

  .responsive-table :deep(.p-datatable-tbody > tr) {
    display: block;
    width: 100%;
    margin: 0 0 12px 0;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--bg-surface);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
  }

  .responsive-table :deep(.p-datatable-tbody > tr:hover) {
    background: var(--bg-surface);
  }

  .responsive-table :deep(.p-datatable-tbody > tr.p-highlight) {
    background: var(--accent-light) !important;
    border-color: var(--accent);
  }

  .responsive-table :deep(.p-datatable-tbody > tr > td) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    width: 100% !important;
    padding: 10px 14px;
    border: none;
    border-bottom: 1px solid var(--border-light);
    text-align: right;
    position: static !important;
    inset-inline-end: auto !important;
    overflow-wrap: anywhere;
  }

  .responsive-table :deep(.p-datatable-tbody > tr > td:last-child) {
    border-bottom: none;
  }

  .responsive-table :deep(.p-datatable-tbody > tr > td > *) {
    min-width: 0;
    max-width: 100%;
  }

  /* Field label on the right (RTL) */
  .responsive-table :deep(.p-datatable-tbody > tr > td[data-label]::before) {
    content: attr(data-label);
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    min-width: 88px;
  }

  /* Selection checkbox → card header strip */
  .responsive-table :deep(.p-datatable-tbody > tr > td[data-p-selection-column="true"]) {
    justify-content: flex-start;
    background: var(--bg-subtle);
    border-bottom: 1px solid var(--border-light);
    padding: 8px 14px;
  }
  .responsive-table :deep(.p-datatable-tbody > tr > td[data-p-selection-column="true"]::before) { display: none; }

  /* Frozen actions column → full-width card footer */
  .responsive-table :deep(.p-datatable-tbody > tr > td[data-p-frozen-column="true"]) {
    justify-content: space-between;
    background: var(--bg-subtle);
    border-top: 1px solid var(--border-light);
    border-bottom: none;
  }
  .responsive-table :deep(.p-datatable-tbody > tr > td[data-p-frozen-column="true"]::before) {
    color: var(--text-secondary);
  }

  /* Open action menus upward so they never overflow the viewport */
  .responsive-table :deep(.table-action-menu-wrapper .action-dropdown-menu) {
    top: auto;
    bottom: calc(100% + 4px);
  }

  /* Small phones: keep labels compact */
  @media (max-width: 400px) {
    .responsive-table :deep(.p-datatable-tbody > tr > td) {
      gap: 10px;
      padding: 9px 12px;
    }
    .responsive-table :deep(.p-datatable-tbody > tr > td[data-label]::before) {
      min-width: 76px;
      font-size: 10.5px;
    }
  }
}
</style>
