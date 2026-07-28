# Standards — معايير الكود والتسمية

## لغة الكود
- **التعليقات:** عربي (كل التعليقات)
- **الأسماء (Variables, Functions, Classes):** إنجليزي
- **الواجهة:** عربي كامل (RTL)
- **Messages (API responses):** عربي

## التسمية

### Backend (Laravel)
| العنصر | النمط | مثال |
|--------|-------|------|
| Migrations | `snake_case` | `create_locations_table` |
| Models | `PascalCase` | `Location`, `Building` |
| Controllers | `PascalCase` + `Controller` | `LocationController` |
| Services | `PascalCase` + `Service` | `InvoiceService` |
| Requests | `PascalCase` + `Request` | `StoreLocationRequest` |
| Resources | `PascalCase` + `Resource` | `LocationResource` |
| Methods | `camelCase` | `getAvailableUnits()` |
| Variables | `camelCase` | `$buildingName` |
| Route names | `snake_case` | `locations.store` |
| Database columns | `snake_case` | `unit_number` |

### Frontend (Vue.js)
| العنصر | النمط | مثال |
|--------|-------|------|
| Components | `PascalCase` | `LocationForm.vue` |
| Views | `PascalCase` | `LocationsView.vue` |
| Stores | `camelCase` | `useLocationStore` |
| Services | `camelCase` | `locationService.js` |
| Variables | `camelCase` | `selectedBuilding` |
| Methods | `camelCase` | `fetchLocations()` |
| Props | `camelCase` | `:building-id` |
| Events | `kebab-case` | `@update-location` |

### Mobile (Flutter)
| العنصر | النمط | مثال |
|--------|-------|------|
| Classes | `PascalCase` | `LocationCard` |
| Files | `snake_case` | `location_card.dart` |
| Variables | `camelCase` | `selectedBuilding` |
| Functions | `camelCase` | `fetchLocations()` |
| Providers | `PascalCase` | `locationProvider` |

## Git (عند الحاجة)
```bash
# Commit messages بالعربي
git commit -m "إضافة نموذج إضافة مبنى"

# Branch naming
git checkout -b feature/locations-crud
git checkout -b fix/invoice-calculation
```

## هيكلة API Response
```json
{
  "success": true,
  "message": "تمت العملية بنجاح",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "total": 100
  }
}
```

## Error Response
```json
{
  "success": false,
  "message": "حدث خطأ",
  "errors": {
    "field_name": ["رسالة الخطأ"]
  }
}
```

## Validation Rules
- دائماً استخدم Form Requests في Laravel
- Validate من الجانبين (Backend + Frontend)
- رسائل الخطأ بالعربية

## Security
- جميع كلمات المرور Hashed (bcrypt)
- جميع API endpoints محمية بـ Sanctum (إلا login)
- Input sanitization
- SQL Injection محمي عبر Eloquent
- XSS محمي عبر Blade escaping أو Vue template escaping
- CSRF عبر Sanctum

## Performance
- eager loading للعلاقات (`->with()`)
- pagination للقوائم (15 items per page)
- index على الحقول المستخدمة في البحث
- caching للبيانات الثابتة (العملات، الإعدادات)
