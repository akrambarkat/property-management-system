# Backend Agent — تعليمات Laravel

## المهمة
بناء API Laravel كامل لنظام إدارة العقارات.

## المتطلبات
- PHP 8.3+
- Laravel 11.x
- MySQL
- Composer (أوفلاين — packages جاهزة)

## البداية
```bash
composer create-project laravel/laravel backend
cd backend
```

## الحزم المطلوبة (أوفلاين)
- `laravel/sanctum` — توثيق API
- `spatie/laravel-permission` — صلاحيات
- `barryvdh/laravel-dompdf` — تصدير PDF
- `maatwebsite/laravel-excel` — تصدير Excel
- `intervention/image-laravel` — معالجة الصور

## هيكل المجلدات
```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/   # API Controllers
│   │   ├── Requests/             # Form Requests
│   │   └── Resources/            # API Resources
│   ├── Models/                   # Eloquent Models
│   └── Services/                 # Business Logic
├── database/
│   ├── migrations/               # Migration files
│   ├── factories/                # Model factories
│   └── seeders/                  # Database seeders
└── routes/
    └── api.php                   # API routes
```

## Models
أنشئ Models لكل جدول في `03_DATABASE_SCHEMA.md` مع:
- `$fillable` أو `$guarded`
- `$casts` (للتحويل التلقائي)
- `relations` (hasMany, belongsTo)
- `$appends` (حقول محسوبة مثل `balance`)

## Controllers
```
app/Http/Controllers/Api/V1/
├── AuthController.php
├── LocationController.php
├── BuildingController.php
├── UnitController.php
├── TenantController.php
├── ContractController.php
├── InvoiceController.php
├── PaymentController.php
├── UtilityReadingController.php
├── ExpenseController.php
├── MaintenanceController.php
├── ReportController.php
├── UserController.php
└── SettingController.php
```

## نظام العملات
- جميع المبالغ تخزن بـ **الشيكل** في قاعدة البيانات
- عند العرض: تضرب في `exchange_rate` الخاصة بالعملة المختارة
- API يستقبل `currency` كـ query parameter (?currency=USD)
- مثال: عرض الإيجار بالدولار = `rent_amount * currency_rate`

## Authentication
- Sanctum API Tokens
- Login يعيد token
- كل request يمر على `auth:sanctum` middleware
- صلاحيات Spatie لكل route

## القواعد
- استخدم **Services** للـ Business Logic (لا تثقل Controllers)
- استخدم **Form Requests** للـ Validation
- استخدم **API Resources** للـ Response formatting
- استخدم **Transformation** للعملات في Resource
- كل استجابة API بصيغة: `{data: ..., message: "...", success: true/false}`

## المهمة
1. `php artisan make:model ... -m` لكل جدول
2. اكتب migrations
3. اكتب seeders (currencies + settings + admin user)
4. اكتب Models مع العلاقات
5. اكتب Form Requests
6. اكتب API Resources (مع تحويل العملة)
7. اكتب Services
8. اكتب Controllers
9. سجل Routes في `api.php`
10. اختبر API باستخدام Postman أو Insomnia
