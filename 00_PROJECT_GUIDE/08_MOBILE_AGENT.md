# Mobile Agent — تعليمات Flutter

## المهمة
بناء تطبيق موبايل Flutter (Android + iOS) يتصل بنفس Laravel API.

## المتطلبات
- Flutter 3.x
- Dart 3.x
- نفس API المستخدم في الويب

## الحزم المطلوبة (pubspec.yaml)
```yaml
dependencies:
  dio: ^5.x              # HTTP Client
  flutter_riverpod: ^2.x # State Management
  go_router: ^14.x       # Routing
  flutter_secure_storage: ^9.x # تخزين التوكن
  intl: ^0.19.x          # تنسيق أرقام وعملات
  pdf: ^3.x              # توليد PDF
  printing: ^5.x         # طباعة
  flutter_localizations: # دعم العربية
    sdk: flutter
```

## الخطوط
- ضع ملفات الخط في `mobile/assets/fonts/`
- عرفها في `pubspec.yaml`:
```yaml
fonts:
  - family: SFPRO
    fonts:
      - asset: assets/fonts/ios-15-heavy.ttf
        weight: 900
      - asset: assets/fonts/ios-15-semibold.ttf
        weight: 600
      - asset: assets/fonts/ios-15-medium.ttf
        weight: 500
      - asset: assets/fonts/ios-15-r-medium.ttf
        weight: 400
```

## هيكل المجلدات
```
mobile/lib/
├── main.dart
├── app.dart                  # MaterialApp مع RTL
├── config/
│   ├── theme.dart            # ألوان + خطوط
│   └── constants.dart        # ثوابت
├── core/
│   ├── network/
│   │   ├── api_client.dart   # Dio instance
│   │   └── api_interceptors.dart
│   └── storage/
│       └── secure_storage.dart
├── features/
│   ├── auth/
│   │   ├── data/
│   │   ├── presentation/
│   │   └── providers/
│   ├── dashboard/
│   ├── locations/
│   ├── buildings/
│   ├── units/
│   ├── tenants/
│   ├── contracts/
│   ├── invoices/
│   ├── payments/
│   ├── utilities/
│   ├── expenses/
│   ├── maintenance/
│   ├── reports/
│   └── settings/
│       └── presentation/
│           └── currency_settings.dart
└── shared/
    ├── widgets/              # مكونات مشتركة
    └── utils/                # Helpers
```

## التصميم
- نفس نظام الألوان: كحلي (`#1B2A4A`) + أبيض
- نفس الخط: iOS SF Pro
- Material 3 مع RTL
- Bottom Navigation للتبويبات الرئيسية

## الصفحات

### Login
- شاشة دخول بسيطة
- حفظ token في secure storage
- توجيه إلى Home

### Home (Dashboard)
- 4 بطاقات إحصاءات
- قائمة آخر المدفوعات
- قائمة الفواتير المتأخرة

### Features (قائمة منسدلة على Navigation)
1. Locations → قائمة + صفحة تفاصيل
2. Buildings → قائمة + فلترة حسب الموقع
3. Units → قائمة + فلترة حسب المبنى والحالة
4. Tenants → قائمة + بحث + تفاصيل
5. Contracts → قائمة + إضافة عقد
6. Invoices → قائمة + تفاصيل + طباعة
7. Payments → إضافة دفعة
8. Utilities → تسجيل قراءة عداد
9. Expenses → قائمة + إضافة
10. Maintenance → طلبات الصيانة
11. Reports → تقارير مع تصفية
12. Settings → إعدادات العملة

## نظام العملات
- اعرض العملة من `User.preferred_currency`
- API call مع `?currency=USD` parameter
- اعرض `₪`, `د.أ`, `$` حسب العملة
- إعدادات العملة: تحديث سعر الصرف

## API Integration
- `api_client.dart`: Dio instance
- Base URL من الإعدادات
- Bearer token من secure storage
- Error handling مع SnackBar

## القواعد
- RTL كامل
- كل شيء بالعربية
- Dark/Light Mode (اختياري)
- استخدام Riverpod لإدارة الحالة
- Responsive لجميع أحجام الشاشات
- Pull to refresh في القوائم
