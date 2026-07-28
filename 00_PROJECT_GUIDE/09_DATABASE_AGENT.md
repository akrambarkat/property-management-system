# Database Agent — تعليمات قاعدة البيانات

## المهمة
بناء قاعدة بيانات MySQL كاملة مع Migrations و Seeders.

## قاعدة البيانات
- **الاسم:** `emaarplus`
- **Collation:** `utf8mb4_unicode_ci`
- **المحرك:** InnoDB

## الجداول (14 جدول)

راجع `03_DATABASE_SCHEMA.md` للتفاصيل الكاملة لكل جدول:

| # | الجدول | المفتاح الأساسي | العلاقات |
|---|--------|----------------|-----------|
| 1 | users | id | - |
| 2 | locations | id | → buildings |
| 3 | buildings | id | → locations, units, expenses |
| 4 | units | id | → buildings, contracts, utility_readings, maintenance |
| 5 | tenants | id | → contracts |
| 6 | contracts | id | → units, tenants, invoices |
| 7 | invoices | id | → contracts, payments |
| 8 | payments | id | → invoices |
| 9 | utility_readings | id | → units |
| 10 | expenses | id | → buildings |
| 11 | maintenance_requests | id | → units |
| 12 | currencies | id | - |
| 13 | activity_logs | id | - |
| 14 | settings | id | - |

## Migrations
- استخدم `$table->id()` للمفتاح الأساسي
- استخدم `$table->foreignId('...')->constrained()->cascadeOnDelete()` للمفاتيح الخارجية
- استخدم `$table->softDeletes()` للحذف الناعم (اختياري)
- أضف `->index()` للحقول المستخدمة بكثرة في البحث

## Seeders
### UserSeeder
- Super Admin: `admin@emaarplus.com` / `password`

### CurrencySeeder
```php
[
    ['ILS', 'شيكل', '₪', 1.0000, true],
    ['JOD', 'دينار أردني', 'د.أ', 0.2000, false],
    ['USD', 'دولار أمريكي', '$', 0.2800, false],
]
```

### SettingSeeder
```php
[
    ['app_name', 'EMAARPlus'],
    ['default_currency', 'ILS'],
    ['electricity_unit_price', '0.50'],
    ['water_unit_price', '3.00'],
    ['invoice_prefix', 'INV-'],
    ['contract_prefix', 'CTR-'],
]
```

## العلاقات (ERD)

```
locations 1─→N buildings
buildings 1─→N units
buildings 1─→N expenses
units 1─→N contracts
units 1─→N utility_readings
units 1─→N maintenance_requests
tenants 1─→N contracts
contracts 1─→N invoices
invoices 1─→N payments
users 1─→N activity_logs
users 1─→N payments (created_by)
users 1─→N expenses (created_by)
users 1─→N utility_readings (recorded_by)
users 1─→N maintenance_requests (requested_by/assigned_to)
```

## خطوات العمل
1. إنشاء قاعدة البيانات في MySQL
2. `php artisan make:migration create_[table]_table` لكل جدول
3. كتابة الأعمدة والعلاقات
4. `php artisan migrate`
5. إنشاء Seeders
6. `php artisan db:seed`
7. التحقق من البيانات
