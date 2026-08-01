<template>
  <div class="page-view">
    <div class="sms-overview-page">
      <div class="page-title-row">
        <div>
          <h2 class="page-title">مركز الرسائل SMS</h2>
          <p class="page-subtitle">نظرة شاملة على أداء رسائلك النصية ومزوديها</p>
        </div>
        <div class="page-title-actions">
          <router-link to="/sms/bulk" class="btn-primary">
            <i class="pi pi-megaphone"></i>
            <span>إرسال جماعي</span>
          </router-link>
        </div>
      </div>
      <SmsOverview />
    </div>
  </div>
</template>

<script setup>
import SmsOverview from '@/components/sms/SmsOverview.vue'
</script>

<style scoped>
.sms-overview-page { display: flex; flex-direction: column; gap: 20px; }
.page-title-row {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px;
}
.page-title { margin: 0; font-size: 20px; font-weight: 800; color: var(--text-primary); }
.page-subtitle { margin: 4px 0 0; font-size: 13px; color: var(--text-secondary); }
</style>
