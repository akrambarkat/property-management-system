<template>
  <div class="page-view profile-dashboard">
    <!-- Header with Back Button -->
    <div class="profile-header">
      <div class="header-left">
        <button class="btn-icon" @click="router.back()">
          <i class="pi pi-arrow-right"></i>
        </button>
        <div class="profile-title-block">
          <div class="flex-align gap-3">
            <div class="tenant-avatar">
              <i class="pi pi-user"></i>
            </div>
            <div>
              <h2 class="profile-title">شركة الأفق للاستشارات ذ.م.م</h2>
              <span class="profile-subtitle">
                <i class="pi pi-id-card text-muted"></i>
                رقم الهوية/السجل: 1029384756 | مستأجر منذ 3 سنوات
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-secondary">
          <i class="pi pi-send"></i> إرسال رسالة
        </button>
        <button class="btn-primary">
          <i class="pi pi-file-edit"></i> تعديل البيانات
        </button>
      </div>
    </div>

    <div class="profile-body-grid">
      <!-- Middle/Left Column -->
      <div class="profile-col-left">
        <!-- Quick Collection Center (Action Panel) -->
        <div class="dashboard-widget highlight-widget">
          <div class="widget-header">
            <h3>مركز التحصيل السريع</h3>
            <i class="pi pi-bolt text-warning text-xl"></i>
          </div>
          <div class="collection-center">
            <div class="outstanding-box">
              <span class="box-label">مستحقات متأخرة</span>
              <span class="box-value text-danger">15,000 ₪</span>
            </div>
            <div class="collection-actions">
              <button class="btn-primary w-full justify-center">
                <i class="pi pi-money-bill"></i> تسجيل دفعة جديدة
              </button>
              <button class="btn-secondary w-full justify-center">
                <i class="pi pi-whatsapp"></i> تذكير عبر واتساب
              </button>
            </div>
          </div>
        </div>

        <!-- Upcoming Contract Expiration -->
        <div class="dashboard-widget">
          <div class="widget-header">
            <h3>العقد الحالي</h3>
            <span class="badge badge-warning">ينتهي قريباً</span>
          </div>
          <div class="contract-card">
            <div class="contract-meta">
              <span class="c-label">رقم العقد</span>
              <span class="c-val">CNT-2024-089</span>
            </div>
            <div class="contract-meta">
              <span class="c-label">الوحدة</span>
              <span class="c-val">مكتب 501 (برج السلام)</span>
            </div>
            <div class="contract-meta">
              <span class="c-label">تاريخ الانتهاء</span>
              <span class="c-val text-danger font-bold">12 أغسطس 2026</span>
            </div>
            <button class="btn-xs-primary mt-2">تجديد العقد</button>
          </div>
        </div>
        
        <!-- Contact Info -->
        <div class="dashboard-widget">
          <div class="widget-header">
            <h3>معلومات التواصل</h3>
          </div>
          <div class="contact-list">
            <div class="contact-item">
              <i class="pi pi-phone text-muted"></i>
              <span>+970 599 123 456</span>
            </div>
            <div class="contact-item">
              <i class="pi pi-envelope text-muted"></i>
              <span>info@alufuq.com</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: History & Activities -->
      <div class="profile-col-right">
        
        <!-- Payment History Table -->
        <div class="dashboard-widget">
          <div class="widget-header">
            <h3>سجل الدفعات الأخير</h3>
            <router-link to="/payments" class="text-xs text-accent font-bold">عرض السجل الكامل</router-link>
          </div>
          <table class="simple-table">
            <thead>
              <tr>
                <th>رقم الإيصال</th>
                <th>التاريخ</th>
                <th>المبلغ</th>
                <th>طريقة الدفع</th>
                <th>الحالة</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>#RCP-1045</td>
                <td>01/07/2026</td>
                <td class="font-bold">5,000 ₪</td>
                <td>تحويل بنكي</td>
                <td><span class="status-badge status-active">مكتمل</span></td>
              </tr>
              <tr>
                <td>#RCP-1012</td>
                <td>01/06/2026</td>
                <td class="font-bold">5,000 ₪</td>
                <td>نقدي</td>
                <td><span class="status-badge status-active">مكتمل</span></td>
              </tr>
              <tr>
                <td>#RCP-0988</td>
                <td>01/05/2026</td>
                <td class="font-bold">5,000 ₪</td>
                <td>شيك</td>
                <td><span class="status-badge status-expired">مرتجع</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Recent Activities Timeline -->
        <div class="dashboard-widget mt-4">
          <div class="widget-header">
            <h3>الخط الزمني للأنشطة</h3>
          </div>
          <div class="timeline-container">
            <div class="timeline-row">
              <div class="t-icon bg-warning-light"><i class="pi pi-exclamation-triangle text-warning"></i></div>
              <div class="t-content">
                <span class="t-title">تذكير بدفعة متأخرة</span>
                <span class="t-time">اليوم, 10:30 صباحاً</span>
                <p class="t-desc">تم إرسال تذكير آلي عبر البريد الإلكتروني لدفع إيجار شهر أغسطس.</p>
              </div>
            </div>
            <div class="timeline-row">
              <div class="t-icon bg-info-light"><i class="pi pi-wrench text-info"></i></div>
              <div class="t-content">
                <span class="t-title">إغلاق طلب صيانة #MNT-202</span>
                <span class="t-time">قبل 3 أيام</span>
                <p class="t-desc">تم الانتهاء من صيانة مكيف المكتب وتسليمه للمستأجر.</p>
              </div>
            </div>
            <div class="timeline-row">
              <div class="t-icon bg-success-light"><i class="pi pi-file text-success"></i></div>
              <div class="t-content">
                <span class="t-title">توقيع الملحق الإضافي</span>
                <span class="t-time">قبل شهر</span>
                <p class="t-desc">تم توقيع ملحق لاستئجار موقف سيارة إضافي.</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const tenantId = route.params.id || '205'
</script>

<style scoped>
.profile-dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.profile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}
.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.tenant-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #EFF6FF;
  color: #3B82F6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  border: 2px solid #BFDBFE;
}
.flex-align {
  display: flex;
  align-items: center;
}
.gap-3 { gap: 12px; }

.profile-title {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}
.profile-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
}
.header-actions {
  display: flex;
  gap: 12px;
}

.profile-body-grid {
  display: grid;
  grid-template-columns: 1fr 2.2fr;
  gap: 20px;
}
@media (max-width: 1024px) {
  .profile-body-grid {
    grid-template-columns: 1fr;
  }
}

.dashboard-widget {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  box-shadow: var(--shadow-sm);
  margin-bottom: 16px;
}
.highlight-widget {
  background: linear-gradient(180deg, #FFFFFF 0%, #FEF9C3 100%);
  border-color: #FDE047;
}

.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.widget-header h3 {
  font-size: 14.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}
.badge-warning { background: #FEF3C7; color: #D97706; }

/* Collection Center */
.collection-center {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.outstanding-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px;
  background: #FEF2F2;
  border-radius: var(--radius-sm);
  border: 1px solid #FECACA;
}
.box-label { font-size: 12.5px; color: #991B1B; font-weight: 600; }
.box-value { font-size: 28px; font-weight: 900; margin-top: 4px; }
.text-danger { color: #DC2626; }
.text-warning { color: #D97706; }

.collection-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.w-full { width: 100%; }
.justify-center { justify-content: center; }

/* Contract Card */
.contract-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #F8FAFC;
  padding: 14px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-light);
}
.contract-meta {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}
.c-label { color: var(--text-secondary); }
.c-val { font-weight: 600; color: var(--text-primary); }
.font-bold { font-weight: 700; }
.mt-2 { margin-top: 8px; }

/* Contact List */
.contact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.contact-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: var(--text-primary);
}

/* Simple Table */
.simple-table {
  width: 100%;
  border-collapse: collapse;
}
.simple-table th {
  text-align: right;
  padding: 10px 12px;
  border-bottom: 1px solid var(--border);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
}
.simple-table td {
  padding: 12px;
  border-bottom: 1px solid var(--border-light);
  font-size: 13.5px;
}
.status-badge {
  padding: 3px 8px;
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 600;
}
.status-active { background: #ECFDF5; color: #059669; }
.status-expired { background: #FEF2F2; color: #DC2626; }

/* Timeline */
.timeline-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-left: 8px;
}
.timeline-row {
  display: flex;
  gap: 16px;
  position: relative;
}
.timeline-row:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 40px;
  right: 17px;
  bottom: -20px;
  width: 2px;
  background: var(--border-light);
}
.t-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 1;
}
.bg-warning-light { background: #FEF3C7; }
.bg-info-light { background: #E0F2FE; }
.text-info { color: #0284C7; }
.bg-success-light { background: #DCFCE7; }
.text-success { color: #16A34A; }

.t-content {
  display: flex;
  flex-direction: column;
  padding-top: 4px;
}
.t-title { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.t-time { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
.t-desc { font-size: 13px; color: var(--text-secondary); margin: 6px 0 0 0; line-height: 1.5; }

.btn-xs-primary {
  background: var(--accent);
  color: #fff;
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11.5px;
  cursor: pointer;
  font-weight: 600;
}
</style>
