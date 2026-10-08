# استقرار ملکینو (Deployment Guide)

> هیچ رمزی داخل این سند نیست. مقادیر محرمانه فقط در `config.secrets.php` (روی هاست، خارج از گیت).

## ۱. پیش‌نیازها
- PHP ≥ 8.1 با افزونه‌ها: pdo_mysql, mbstring, json, fileinfo, curl (+pdo_sqlite فقط برای تست)
- MySQL/MariaDB 10.3+ · وب‌سرور Apache (با mod_headers) یا Nginx
- بدون Composer/NPM — پروژه وابستگی پکیجی ندارد

## ۲. نصب
1. فایل‌ها را در webroot کپی کنید (نه محتویات `backups/` و `tools/` لازم نیست روی وب باشد).
2. `.htaccess` ریشه (موجود در مخزن) حتماً فعال باشد — AccessFileName را غیرفعال نکنید.
3. دیتابیس بسازید و `melkino-database.sql` را Import کنید (idempotent؛ به دادهٔ موجود آسیب نمی‌زند).
4. از `config.secrets.example.php` کپی به `config.secrets.php` بسازید و مقادیر واقعی را بگذارید
   (ترجیحاً ENV به‌جای فایل). هرگز این فایل را commit/آپلود عمومی نکنید.
5. ادمین اول: `php tools/create-admin.php --username=admin` (فقط CLI).
6. `MOCK_MODE` در production باید `false` بماند (config.php). اگر قبلاً با ads.json کار می‌کردید،
   قبل از قطع MOCK دادهٔ ads.json را به دیتابیس منتقل کنید.

## ۳. Cron
```
*/5 * * * * curl -s "https://YOURDOMAIN/sms-cron.php?token=YOUR_CRON_TOKEN"
```
توکن از تب «برنامهٔ پیامک» پنل ادمین تنظیم می‌شود؛ بدون توکن اجرا ۴۰۳ است.

## ۴. مجوزها
- قابل نوشتن: `uploads/` (و زیرپوشه‌ها)، `settings/`، `ads.json` (فقط اگر MOCK فعال)
- `config.secrets.php`: 640 یا 600

## ۵. بکاپ (استراتژی)
```text
Production DB → mysqldump (شبانه) → gzip → خارج از webroot (مثلاً /home/USER/backups)
→ رمزگذاری (openssl enc -aes-256-cbc) → انتقال به محل دوم (object storage/لوکال دیگر)
→ نگهداری ۳۰ روز · تست بازیابی ماهانه
```
- هرگز بکاپ داخل webroot یا مخزن گیت قرار نگیرد (الگو: backups/melkino-backup-*.zip که
  در audit حذف شد). `backups/admin-backup.php` خروجی را خارج از webroot منتقل کنید.

## ۶. مایگریشن‌ها
- ساختار: `melkino-database.sql` + `melkino-migrate.php` (ادمین/CLI) — همه idempotent.
- قبل از هر مایگریشن دستی: mysqldump کامل + ذخیرهٔ خروجی.
- ستون‌های قیمت (اختیاری، پس از پاک‌سازی داده): به DECIMAL(15,0) — اسکریپت در `docs/migrations/`.

## ۷. Rollback
- کد: به تگ/کامیت قبلی برگردید (deploy پوشه‌ای: پوشهٔ release قبلی را symlink کنید).
- DB: از بکاپ شبانه restore + اجرای مجدد مایگریشن‌های بعد از آن بکاپ.
- ابزارهای موقت (health/schema-fix/price-fix/deploy-check) پس از استفاده حذف شوند.

## ۸. تست قبل از انتشار
```
php tests/regression.php && php tests/regression-remediation.php
node tests/regression-js.js
```
+ چک‌لیست دستی: ورود مینی‌اپ، ثبت ملک، ذخیرهٔ ادمین (bulk_sync)، رزرو بازدید، ارسال OTP.

## ۹. چرخهٔ Secretها (پس از هر افشا)
DB password · BOT_TOKEN تلگرام (BotFather /revoke) · توکن بله · ایتایار · رمز ملی‌پیامک ·
رمز پنل. مقادیر افشاشدهٔ قبلی در تاریخچهٔ گیت: **فرض compromised** — rotate اجباری.
