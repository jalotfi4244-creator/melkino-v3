<?php
/** Melkino V2 — register-choose (same two paths as round-25 legacy; partnership stays out). */
use Melkino\UI\Icons\IconRegistry;
?>
<div class="mx-page-head mx-text-center"><h1>چه چیزی می‌خواهید ثبت کنید؟</h1>
<p class="mx-small">یکی از مسیرهای زیر را انتخاب کنید تا به فرم مربوطه هدایت شوید.</p></div>
<div class="mx-grid mx-grid--2">
  <a class="mx-card-surface mx-p-4 mx-choose" href="register-step1.php">
    <span class="mx-choose__icon"><?= IconRegistry::svg('home', 30) ?></span>
    <b>ثبت ملک</b><small class="mx-muted">برای مالکین و مشاورین املاک</small>
    <p class="mx-small">اگر ملکی برای فروش، رهن یا اجاره دارید، مشخصات آن را ثبت کنید تا آگهی شما ساخته شود و بتوانید آن را در سایت، کانال تلگرام و بله منتشر کنید.</p>
    <span class="mx-link">ادامهٔ ثبت ملک ‹</span>
  </a>
  <a class="mx-card-surface mx-p-4 mx-choose" href="property-request.php">
    <span class="mx-choose__icon"><?= IconRegistry::svg('doc', 30) ?></span>
    <b>ثبت درخواست</b><small class="mx-muted">برای خریداران و مستأجرین</small>
    <p class="mx-small">اگر دنبال ملک هستید، ویژگی‌های ملک مورد نظرتان (نوع، بودجه، محله و …) را ثبت کنید تا فایل‌های مناسب شما پیدا شوند و وقتی ملک مطابق درخواستتان ثبت شد، باخبر شوید.</p>
    <span class="mx-link">ادامهٔ ثبت درخواست ‹</span>
  </a>
</div>
