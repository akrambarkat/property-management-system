# 🌐 API Routes — نقاط API

## القاعدة
```
Base URL: /api/v1
Format: JSON
Auth: Bearer Token (Sanctum)
Headers: Accept: application/json
```

---

## Authentication (لا يتطلب توكن)

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| POST | `/api/v1/login` | تسجيل الدخول |
| POST | `/api/v1/logout` | تسجيل الخروج (يتطلب توكن) |
| GET | `/api/v1/user` | بيانات المستخدم الحالي |

---

## Locations

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/locations` | قائمة المواقع |
| POST | `/api/v1/locations` | إضافة موقع |
| GET | `/api/v1/locations/{id}` | عرض موقع |
| PUT | `/api/v1/locations/{id}` | تعديل موقع |
| DELETE | `/api/v1/locations/{id}` | حذف موقع |

## Buildings

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/buildings` | قائمة المباني (filter: ?location_id=) |
| POST | `/api/v1/buildings` | إضافة مبنى |
| GET | `/api/v1/buildings/{id}` | عرض مبنى مع وحداته |
| PUT | `/api/v1/buildings/{id}` | تعديل مبنى |
| DELETE | `/api/v1/buildings/{id}` | حذف مبنى |

## Units

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/units` | قائمة الوحدات (filter: ?building_id=&status=&unit_type=) |
| POST | `/api/v1/units` | إضافة وحدة |
| GET | `/api/v1/units/{id}` | عرض وحدة مع المستأجر الحالي |
| PUT | `/api/v1/units/{id}` | تعديل وحدة |
| DELETE | `/api/v1/units/{id}` | حذف وحدة |
| PATCH | `/api/v1/units/{id}/status` | تغيير حالة الوحدة |

## Tenants

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/tenants` | قائمة المستأجرين (search: ?search=) |
| POST | `/api/v1/tenants` | إضافة مستأجر |
| GET | `/api/v1/tenants/{id}` | عرض مستأجر مع عقوده |
| PUT | `/api/v1/tenants/{id}` | تعديل مستأجر |
| DELETE | `/api/v1/tenants/{id}` | حذف مستأجر |

## Contracts

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/contracts` | قائمة العقود (filter: ?status=&unit_id=&tenant_id=) |
| POST | `/api/v1/contracts` | إضافة عقد (يغير حالة الوحدة → occupied) |
| GET | `/api/v1/contracts/{id}` | عرض عقد مع فواتيره |
| PUT | `/api/v1/contracts/{id}` | تعديل عقد |
| DELETE | `/api/v1/contracts/{id}` | حذف عقد (يرجع حالة الوحدة → available) |
| PATCH | `/api/v1/contracts/{id}/terminate` | إنهاء عقد |
| GET | `/api/v1/contracts/expiring` | العقود المنتهية قريباً (?days=30) |

## Invoices

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/invoices` | قائمة الفواتير (filter: ?status=&contract_id=&from=&to=) |
| POST | `/api/v1/invoices` | إضافة فاتورة (يحسب total تلقائياً) |
| GET | `/api/v1/invoices/{id}` | عرض فاتورة مع مدفوعاتها |
| PUT | `/api/v1/invoices/{id}` | تعديل فاتورة |
| DELETE | `/api/v1/invoices/{id}` | حذف فاتورة |
| GET | `/api/v1/invoices/{id}/pdf` | تحميل PDF |
| PATCH | `/api/v1/invoices/{id}/pay` | تسديد فاتورة |

## Payments

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/payments` | قائمة المدفوعات (filter: ?invoice_id=&from=&to=) |
| POST | `/api/v1/payments` | إضافة دفعة (يحدث paid_amount في الفاتورة) |
| GET | `/api/v1/payments/{id}` | عرض دفعة |
| DELETE | `/api/v1/payments/{id}` | حذف دفعة (يحدث الفاتورة) |
| GET | `/api/v1/payments/{id}/receipt` | طباعة إيصال PDF |

## Utility Readings

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/utility-readings` | قائمة القراءات (filter: ?unit_id=&type=&from=&to=) |
| POST | `/api/v1/utility-readings` | إضافة قراءة (يحسب consumption + total) |
| GET | `/api/v1/utility-readings/{id}` | عرض قراءة |
| PUT | `/api/v1/utility-readings/{id}` | تعديل قراءة |
| DELETE | `/api/v1/utility-readings/{id}` | حذف قراءة |

## Expenses

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/expenses` | قائمة المصروفات (filter: ?building_id=&category=&from=&to=) |
| POST | `/api/v1/expenses` | إضافة مصروف |
| GET | `/api/v1/expenses/{id}` | عرض مصروف |
| PUT | `/api/v1/expenses/{id}` | تعديل مصروف |
| DELETE | `/api/v1/expenses/{id}` | حذف مصروف |

## Maintenance Requests

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/maintenance` | قائمة طلبات الصيانة (filter: ?status=&priority=&unit_id=) |
| POST | `/api/v1/maintenance` | إضافة طلب |
| GET | `/api/v1/maintenance/{id}` | عرض طلب |
| PUT | `/api/v1/maintenance/{id}` | تعديل طلب |
| PATCH | `/api/v1/maintenance/{id}/status` | تغيير الحالة |

## Reports

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/reports/dashboard` | إحصائيات لوحة التحكم |
| GET | `/api/v1/reports/income` | تقرير الدخل (?from=&to=&building_id=) |
| GET | `/api/v1/reports/expenses` | تقرير المصروفات |
| GET | `/api/v1/reports/profit-loss` | أرباح وخسائر |
| GET | `/api/v1/reports/outstanding` | المدفوعات المتأخرة |
| GET | `/api/v1/reports/tenant-statement/{tenant_id}` | كشف حساب مستأجر |
| GET | `/api/v1/reports/building-performance` | أداء المباني |

## Users (Super Admin only)

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/users` | قائمة المستخدمين |
| POST | `/api/v1/users` | إضافة مستخدم |
| GET | `/api/v1/users/{id}` | عرض مستخدم |
| PUT | `/api/v1/users/{id}` | تعديل مستخدم |
| DELETE | `/api/v1/users/{id}` | حذف مستخدم |
| PATCH | `/api/v1/users/{id}/toggle-status` | تفعيل/تعطيل |

## Settings

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/settings` | عرض الإعدادات |
| PUT | `/api/v1/settings` | تحديث الإعدادات |
| GET | `/api/v1/currencies` | قائمة العملات |
| PUT | `/api/v1/currencies/{id}` | تحديث سعر عملة |
| PATCH | `/api/v1/currencies/{id}/default` | تعيين عملة افتراضية |

## Activity Logs (Super Admin only)

| الطريقة | الرابط | الشرح |
|---------|--------|-------|
| GET | `/api/v1/activity-logs` | سجل النشاطات (filter: ?user_id=&model=&from=&to=) |
