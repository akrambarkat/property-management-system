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

        <slot name="filters"></slot>
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

        <!-- Export Dropdown / Button -->
        <button class="btn-toolbar" @click="exportCSV" title="تصدير البيانات">
          <i class="pi pi-file-excel"></i>
          <span class="btn-label">تصدير CSV</span>
        </button>

        <!-- Print Button -->
        <button class="btn-toolbar" @click="printTable" title="طباعة">
          <i class="pi pi-print"></i>
        </button>

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
        class="enterprise-saas-table sticky-header-table"
        @row-click="onRowClick"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
        currentPageReportTemplate="عرض {first} إلى {last} من {totalRecords} سجل"
      >
        <!-- Checkbox Selection Column -->
        <Column v-if="selectable" selectionMode="multiple" headerStyle="width: 3rem" />

        <slot :hiddenColumns="hiddenColumns"></slot>

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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  value: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  selectable: { type: Boolean, default: true },
  showSearch: { type: Boolean, default: true },
  searchPlaceholder: { type: String, default: 'بحث شامل في الجدول...' },
  emptyTitle: { type: String, default: 'لا توجد بيانات متاحة' },
  emptySubtitle: { type: String, default: 'لم يتم العثور على أي سجلات تطابق شروط البحث الحالية' },
  columns: { type: Array, default: () => [] }, // Format: [{ field: 'name', header: 'اسم' }]
  skeletonCols: { type: Number, default: 5 }
})

const emit = defineEmits(['row-click', 'export', 'bulk-export', 'refresh', 'update:selection'])

const searchQuery = ref('')
const selectedRows = ref([])
const rowsPerPage = ref(15)
const hiddenColumns = ref([])
const showColumnMenu = ref(false)
const colMenuRef = ref(null)

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

function exportSelected() {
  emit('bulk-export', selectedRows.value)
}

function printTable() {
  window.print()
}

function handleClickOutside(event) {
  if (colMenuRef.value && !colMenuRef.value.contains(event.target)) {
    showColumnMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
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
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  right: 12px;
  color: var(--text-muted, #94A3B8);
  font-size: 0.9rem;
}

.search-input {
  padding: 8px 36px 8px 30px !important;
  width: 280px !important;
  background: var(--bg-surface, #FFFFFF) !important;
  border: 1px solid var(--border, #E2E8F0) !important;
  border-radius: var(--radius-sm, 6px) !important;
  font-size: 13px !important;
  color: var(--text-primary, #0F172A) !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary, #2563EB) !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
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

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-toolbar {
  display: inline-flex;
  align-items: center;
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
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.btn-toolbar:hover {
  background: var(--bg-subtle, #F8FAFC);
  border-color: var(--border-hover, #CBD5E1);
  color: var(--primary, #2563EB);
}

.bulk-actions-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #0F172A;
  color: #FFFFFF;
  padding: 4px 14px;
  border-radius: var(--radius-full, 9999px);
  font-size: 12.5px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
}

.bulk-count {
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-bulk-action {
  background: var(--primary, #2563EB);
  color: #FFFFFF;
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
  background: #1D4ED8;
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
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-md, 8px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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

.table-surface-card {
  background: var(--bg-surface, #FFFFFF);
  border: 1px solid var(--border, #E2E8F0);
  border-radius: var(--radius-md, 10px);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
  overflow: hidden;
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
  background: linear-gradient(90deg, #F1F5F9 25%, #E2E8F0 50%, #F1F5F9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skeleton-cell.header-cell {
  height: 22px;
  background: #E2E8F0;
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
</style>
