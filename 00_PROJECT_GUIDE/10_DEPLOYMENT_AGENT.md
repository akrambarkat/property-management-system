# Deployment Agent — تعليمات الرفع

## المهمة
رفع مشروع AqarMaster إلى هوست (Shared Hosting مع cPanel أو VPS).

## المتطلبات
- PHP 8.3+
- MySQL 8+
- Composer
- Node.js (لبناء Vue.js)

## 1. Laravel Backend

### Shared Hosting (cPanel)
```bash
# 1. رفع مجلد backend إلى public_html/api/
# 2. نقل محتوى public/ إلى public_html/
# 3. تعديل path في index.php
# 4. تعديل .env
```

### ملف .env
```env
APP_NAME=AqarMaster
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=AqarMaster_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DRIVER=cookie
SESSION_DOMAIN=.yourdomain.com
```

### الأوفلاين
لأن النت ضعيف، جهّز مجلد `vendor` كامل من بيئة تطوير مع نت قوي، ثم ارفعه مع المشروع.

```bash
# في بيئة مع نت قوي
composer install --no-dev
# انسخ مجلد vendor إلى الهوست
```

## 2. Vue.js Web

```bash
cd web
npm install  # في بيئة مع نت
npm run build  # ينتج مجلد dist/
```

- ارفع محتوى `dist/` إلى `public_html/` (أو مجلد فرعي)
- عرف Apache rewrite rule لتوجيه كل المسارات إلى `index.html`

### Apache (.htaccess)
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

## 3. Flutter Mobile

```bash
cd mobile
flutter build apk --release  # Android
flutter build ios --release  # iOS (يتطلب Mac)
```

- ملف APK: `mobile/build/app/outputs/flutter-apk/app-release.apk`
- ارفع APK إلى أي رابط أو متجر

## 4. تحسينات الإنتاج

### Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### قاعدة البيانات
- جدول `currencies` يبقى ثابت
- مجلد `storage` قابل للكتابة
- تفعيل HTTPS
- نسخ احتياطي يومي (cron job)

## 5. VPS (اختياري)

إذا كان VPS:
```bash
# تثبيت LEMP Stack
sudo apt install nginx mysql-server php8.3-fpm

# إعداد Nginx
sudo nano /etc/nginx/sites-available/AqarMaster

# إعداد SSL مع Certbot
sudo certbot --nginx -d yourdomain.com

# إعداد Cron للنسخ الاحتياطي
0 3 * * * /usr/bin/php /var/www/AqarMaster/artisan backup:run
```
