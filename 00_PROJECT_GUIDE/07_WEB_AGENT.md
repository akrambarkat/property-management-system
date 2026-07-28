# Web Agent — تعليمات Vue.js

## المهمة
بناء واجهة ويب SPA (Single Page Application) باستخدام Vue.js تتصل بـ Laravel API.

## المتطلبات
- Node.js 18+
- Vue 3 (Composition API)
- PrimeVue 4 (UI Framework)
- Vue Router 4
- Pinia (State Management)
- Axios (HTTP Client)

## البداية
```bash
npm create vue@latest web
cd web
npm install primevue primeicons axios pinia vue-router@4
```

## الخطوط
- حمل ملفات الخط من `00_PROJECT_GUIDE/fonts/`
- ضعها في `web/src/assets/fonts/`
- عرف `@font-face` في `main.css` لكل وزن

## هيكل المجلدات
```
web/src/
├── assets/
│   ├── fonts/          # iOS SF Pro Fonts
│   ├── images/         # الصور
│   └── styles/         # CSS files
├── components/         # مكونات قابلة لإعادة الاستخدام
│   ├── layout/         # Sidebar, Header, Footer
│   └── shared/         # DataTable, Cards, Buttons
├── views/              # صفحات كاملة
│   ├── auth/           # Login
│   ├── dashboard/      # Dashboard
│   ├── locations/      # Locations
│   ├── buildings/      # Buildings
│   ├── units/          # Units
│   ├── tenants/        # Tenants
│   ├── contracts/      # Contracts
│   ├── invoices/       # Invoices
│   ├── payments/       # Payments
│   ├── utilities/      # Utility Readings
│   ├── expenses/       # Expenses
│   ├── maintenance/    # Maintenance
│   ├── reports/        # Reports
│   ├── users/          # Users
│   └── settings/       # Settings
├── router/             # Vue Router config
├── stores/             # Pinia stores
├── services/           # Axios API calls
├── utils/              # Helpers (currency, date)
├── App.vue
└── main.js
```

## PrimeVue Setup
استخدم مكونات PrimeVue:
- `DataTable` — للجداول
- `Card` — للبطاقات
- `Dialog` — للنوافذ المنبثقة
- `InputText`, `Dropdown`, `DatePicker` — للإدخال
- `Button` — للأزرار
- `Toast` — للإشعارات
- `Sidebar` — للقائمة الجانبية
- `Chart` — للرسوم البيانية

## التصميم (من `05_DESIGN_SYSTEM.md`)
- **الألوان:** أبيض + كحلي (`#1B2A4A`)
- **الخط:** iOS SF Pro
- **Dashboard:** بطاقات إحصاءات في الأعلى + جداول أسفل

## نظام العملات
اعرض العملة الحالية من المستخدم (`preferred_currency`)
- احصل على العملات وسعر الصرف من API
- خزن العملة المختارة في Pinia store
- عرض المبالغ: `(amount * rate).toFixed(2) + symbol`

## الصفحات الرئيسية

### Login
- نموذج دخول (بريد + كلمة مرور)
- تخزين token في localStorage
- توجيه إلى Dashboard

### Dashboard
- 4 بطاقات: مجموع الوحدات، الإيراد الشهري، المتأخرات، نسبة الإشغال
- جدول آخر 10 مدفوعات
- جدول الفواتير المتأخرة

### Locations / Buildings / Units
- PrimeVue DataTable مع فرز وبحث
- زر إضافة → Dialog
- زر تعديل/حذف في كل صف

### Tenants / Contracts / Invoices
- نفس النمط: DataTable + Dialog للإضافة/التعديل
- Contracts: خيار إنهاء العقد
- Invoices: زر طباعة PDF

### Reports
- تقارير بتصفية (تاريخ، مبنى)
- عرض بجداول
- أزرار PDF / Excel

### Settings
- تغيير اسم التطبيق
- إدارة العملات (تحديث سعر الصرف)
- اختيار العملة الافتراضية

## الربط مع API
- `services/api.js`: Axios instance مع Bearer token
- كل View تستدعي Service منفصل
- استخدم `async/await` مع try/catch

## القواعد
- RTL (اتجاه من اليمين لليسار)
- كل صفحة: `PrimeVue DataTable + Dialog`
- لا نصوص إنجليزية في الواجهة (كل شيء عربي)
- Responsive: تعمل على الجوال والتابلت
