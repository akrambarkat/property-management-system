# 🏗️ هيكلة النظام — Architecture

## نظرة عامة

```
┌─────────────────────────────────────────────────────┐
│                     Frontend                        │
├──────────────────┬──────────────────┬───────────────┤
│   Web (Vue.js)   │  Mobile (Flutter)│               │
│   (SPA)          │  (Android/iOS)   │               │
└────────┬─────────┴────────┬─────────┘               │
         │                  │                          │
         └────────┬─────────┘                          │
                  │ REST API (JSON)                    │
         ┌────────┴─────────┐                          │
         │   Laravel API    │                          │
         │   (Backend)      │                          │
         └────────┬─────────┘                          │
                  │                                    │
         ┌────────┴─────────┐                          │
         │     MySQL        │                          │
         │   (Database)     │                          │
         └──────────────────┘                          │
└─────────────────────────────────────────────────────┘
```

## المكونات الأساسية

### 1. Laravel Backend (API)
- **إصدار PHP:** 8.3+
- **إصدار Laravel:** 11.x
- **نظام التوثيق:** Sanctum (API Tokens)
- **نظام الصلاحيات:** Spatie Laravel-permission
- **قاعدة البيانات:** MySQL مع Eloquent ORM
- **المكتبات:** كلها أوفلاين (راجع `06_BACKEND_AGENT.md`)

### 2. Vue.js Web (SPA)
- **إصدار Vue:** 3.x مع Composition API
- **UI Framework:** PrimeVue 4 (مكتبة Vue جاهزة بتصميم عصري)
- **Router:** Vue Router 4
- **State Management:** Pinia
- **HTTP Client:** Axios
- **الخط:** iOS SF Pro (موجود في `00_PROJECT_GUIDE/fonts/`)

### 3. Flutter Mobile
- **إصدار Flutter:** 3.x
- **State Management:** Provider أو Riverpod
- **HTTP Client:** Dio
- **الخط:** نفس خط iOS SF Pro
- **الأوفلاين:** جميع الحزم في pubspec.lock + cache

### 4. قاعدة البيانات (MySQL)
- **InnoDB** لجميع الجداول
- **Collation:** utf8mb4_unicode_ci (يدعم العربي)
- **Relationships:** Foreign Keys + Indexes

## تدفق البيانات

```
User → Web/Mobile → HTTP Request → Laravel API → 
  → Controller → Service → Eloquent → MySQL
  → JSON Response ← ← ← ← ← ← ← ← ←
```

## API Design
- **Base URL:** `/api/v1/`
- **Authentication:** Bearer Token (Sanctum)
- **Format:** JSON
- **RESTful:** نعم
- **Headers:** Accept: application/json

## آلية العمل مع العملات
- العملة الأساسية في النظام: **شيكل** (افتراضي)
- العملات المدعومة: **شيكل | دينار أردني | دولار أمريكي**
- سعر الصرف: يتم تخزينه في جدول `currencies` ويتغير من الإعدادات
- يتم عرض المبالغ حسب العملة المختارة في الجلسة أو المفضلة للمستخدم
