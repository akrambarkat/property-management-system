# 🗃️ هيكل قاعدة البيانات — Database Schema

## قواعد التسمية
- أسماء الجداول: `snake_case` جمع (مثال: `locations`, `buildings`, `units`)
- أسماء الأعمدة: `snake_case` (مثال: `first_name`, `contract_start_date`)
- المفاتيح الأساسية: `id`
- المفاتيح الخارجية: `table_name_id` (مثال: `building_id`)

---

## قائمة الجداول

### 1. `users`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | المعرف |
| name | varchar(191) | الاسم |
| email | varchar(191) | البريد (unique) |
| password | varchar(191) | كلمة المرور (hashed) |
| phone | varchar(50) | رقم الهاتف |
| role | enum('super_admin','employee','guard') | الدور |
| is_active | boolean | مفعل؟ |
| preferred_currency | varchar(3) | العملة المفضلة (ILS/JOD/USD) |
| timestamps | - | created_at, updated_at |

### 2. `locations`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| name | varchar(191) | اسم الموقع |
| address | text | العنوان |
| is_active | boolean | |
| timestamps | - | |

### 3. `buildings`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| location_id | FK → locations.id | الموقع |
| name | varchar(191) | اسم المبنى |
| address | text | |
| floors | integer | عدد الطوابق |
| is_active | boolean | |
| timestamps | - | |

### 4. `units`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| building_id | FK → buildings.id | المبنى |
| unit_number | varchar(50) | رقم الوحدة |
| unit_type | enum('apartment','shop','warehouse') | النوع |
| floor | integer | الطابق |
| area | decimal(10,2) | المساحة (م²) |
| rent_amount | decimal(10,2) | قيمة الإيجار (بالشيكل) |
| status | enum('available','occupied','maintenance') | الحالة |
| notes | text | ملاحظات |
| is_active | boolean | |
| timestamps | - | |

### 5. `tenants`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| first_name | varchar(191) | الاسم الأول |
| last_name | varchar(191) | اسم العائلة |
| id_number | varchar(50) | رقم الهوية (unique) |
| phone | varchar(50) | الهاتف |
| email | varchar(191) | البريد |
| address | text | عنوان السكن |
| notes | text | |
| is_active | boolean | |
| timestamps | - | |

### 6. `contracts`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| contract_number | varchar(50) | رقم العقد (unique, auto) |
| unit_id | FK → units.id | الوحدة |
| tenant_id | FK → tenants.id | المستأجر |
| start_date | date | تاريخ البداية |
| end_date | date | تاريخ النهاية |
| rent_amount | decimal(10,2) | قيمة الإيجار (بالشيكل) |
| contract_type | enum('monthly','yearly') | نوع العقد |
| status | enum('active','expired','terminated','renewed') | الحالة |
| document_path | varchar(191) | مسار المستند (nullable) |
| notes | text | |
| timestamps | - | |

### 7. `invoices`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| contract_id | FK → contracts.id | العقد |
| invoice_number | varchar(50) | رقم الفاتورة (unique) |
| issue_date | date | تاريخ الإصدار |
| due_date | date | تاريخ الاستحقاق |
| rent_amount | decimal(10,2) | الإيجار |
| electricity_amount | decimal(10,2) | الكهرباء |
| water_amount | decimal(10,2) | الماء |
| internet_amount | decimal(10,2) | الإنترنت |
| services_amount | decimal(10,2) | خدمات إضافية |
| total_amount | decimal(10,2) | المجموع (بالشيكل) |
| status | enum('unpaid','paid','partial','overdue') | الحالة |
| paid_amount | decimal(10,2) | المدفوع |
| notes | text | |
| timestamps | - | |

### 8. `payments`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| invoice_id | FK → invoices.id | الفاتورة |
| amount | decimal(10,2) | المبلغ (بالشيكل) |
| payment_date | date | تاريخ الدفع |
| payment_method | varchar(50) | طريقة الدفع (نقدي/تحويل/شيك) |
| reference_number | varchar(100) | رقم مرجعي (nullable) |
| notes | text | |
| receipt_number | varchar(50) | رقم الإيصال |
| created_by | FK → users.id | من أضافه |
| timestamps | - | |

### 9. `utility_readings`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| unit_id | FK → units.id | الوحدة |
| reading_date | date | تاريخ القراءة |
| utility_type | enum('electricity','water') | النوع |
| previous_reading | decimal(10,2) | القراءة السابقة |
| current_reading | decimal(10,2) | القراءة الحالية |
| consumption | decimal(10,2) | الاستهلاك (محسوب) |
| unit_price | decimal(10,2) | سعر الوحدة |
| total | decimal(10,2) | المجموع |
| recorded_by | FK → users.id | من سجل |
| notes | text | |
| timestamps | - | |

### 10. `expenses`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| building_id | FK → buildings.id | المبنى |
| category | enum('maintenance','plumbing','electrical','cleaning','security','general') | التصنيف |
| amount | decimal(10,2) | المبلغ (بالشيكل) |
| expense_date | date | التاريخ |
| description | text | الوصف |
| receipt_path | varchar(191) | مسار الإيصال (nullable) |
| created_by | FK → users.id | من أضافه |
| timestamps | - | |

### 11. `maintenance_requests`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| unit_id | FK → units.id | الوحدة |
| requested_by | FK → users.id | مقدم الطلب |
| description | text | الوصف |
| priority | enum('low','medium','high','urgent') | الأولوية |
| status | enum('pending','in_progress','completed','cancelled') | الحالة |
| assigned_to | FK → users.id (nullable) | المسند لـ |
| cost | decimal(10,2) | التكلفة (nullable) |
| completed_at | datetime | تاريخ الإنجاز |
| notes | text | |
| timestamps | - | |

### 12. `currencies`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| code | varchar(3) | كود العملة (ILS/JOD/USD) |
| name | varchar(50) | الاسم (شيكل/دينار/دولار) |
| symbol | varchar(10) | الرمز (₪/د.أ/$) |
| exchange_rate | decimal(10,4) | سعر الصرف مقابل الشيكل |
| is_default | boolean | هل هي الافتراضية؟ |
| is_active | boolean | |
| timestamps | - | |

### 13. `activity_logs`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| user_id | FK → users.id | المستخدم |
| action | varchar(191) | الإجراء (created/updated/deleted) |
| model | varchar(191) | النموذج (User/Unit/Contract...) |
| model_id | bigint | المعرف |
| description | text | وصف |
| ip_address | varchar(45) | |
| user_agent | text | |
| timestamps | - | |

### 14. `settings`
| العمود | النوع | الشرح |
|--------|------|-------|
| id | bigint AI | |
| key | varchar(191) | المفتاح (unique) |
| value | text | القيمة |
| timestamps | - | |

---

## العلاقات (Relationships)

| الجدول | العلاقة | الجدول الآخر |
|--------|---------|-------------|
| locations | 1 → many | buildings |
| buildings | 1 → many | units |
| buildings | 1 → many | expenses |
| units | 1 → many | contracts |
| units | 1 → many | utility_readings |
| units | 1 → many | maintenance_requests |
| tenants | 1 → many | contracts |
| contracts | 1 → many | invoices |
| invoices | 1 → many | payments |
| users | 1 → many | activity_logs |

---

## Seeders (بيانات أولية)

### currencies
| code | name | symbol | rate | default |
|------|------|--------|------|---------|
| ILS | شيكل | ₪ | 1.0000 | true |
| JOD | دينار أردني | د.أ | 0.2000 | false |
| USD | دولار أمريكي | $ | 0.2800 | false |

### settings
| key | value |
|-----|-------|
| app_name | AqarMaster |
| default_currency | ILS |
| electricity_unit_price | 0.50 |
| water_unit_price | 3.00 |
