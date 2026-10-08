<?php
/**
 * Melkino V2 — index landing (standalone; head/body VERBATIM, style/scripts extracted).
 * Vars: $logged_in, $profile, $logo_url, $first_logo, $first_title, $first_text.
 */
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
<script src="https://tapi.bale.ai/miniapp.js?3"></script>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"
    >

    <meta name="theme-color" content="#031D1D">

    <title>ملکینو | انتخابی فراتر از یک ملک</title>

    <link
        href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css"
        rel="stylesheet" media="print" onload="this.media='all'"
    >

    

<!-- ورود خودکارِ مینی‌اپ تلگرام/بله -->

    <!-- همگام‌سازی پروفایل: به‌محض ورود کاربر به مینی‌اپ، اطلاعات او
         در پایگاه داده ساخته/به‌روزرسانی می‌شود (حتی بدون ثبت آگهی) -->
    
<?= \Melkino\Support\Assets::css('assets/css/index-legacy.css') ?>
<script type="application/json" id="mxIndexData"><?= json_encode(['loggedIn' => (bool)($logged_in ?? false), 'profile' => ($profile ?? null)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?= \Melkino\Support\Assets::js('assets/js/index.js', false) ?>
</head>


<body>

<div class="mk-page">

    <!-- Background -->
    <div class="mk-grid"></div>
    <div class="mk-glow mk-glow-one"></div>
    <div class="mk-glow mk-glow-two"></div>
    <div class="mk-ring"></div>


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <header class="mk-topbar">

        <div class="mk-brand">

            <div class="mk-logo-box">

                <?php if ($logo_url): ?>

                    <img
                        src="<?= htmlspecialchars(
                            $logo_url,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="لوگوی ملکینو"
                    >

                <?php else: ?>

                    <svg
                        class="mk-fallback-logo"
                        viewBox="0 0 24 24"
                        fill="none"
                    >

                        <path
                            d="M3 10L12 3L21 10V20C21 20.55 20.55 21 20 21H4C3.45 21 3 20.55 3 20V10Z"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M9 21V13H15V21"
                            stroke="currentColor"
                            stroke-width="1.6"
                        />

                    </svg>

                <?php endif; ?>

            </div>


            <div class="mk-brand-text">

                <div class="mk-brand-name">
                    ملکینو
                </div>

                <div class="mk-brand-sub">
                    اولین پلتفرم ملکی شاهرود
                </div>

            </div>

        </div>


        <button
            type="button"
            class="mk-skip"
            id="skipBtn"
        >
            ورود به خانه
        </button>

    </header>



    <!-- صفحهٔ اول؛ بدون اسلایدر -->

    <main class="mk-slider mk-single" id="slider">

        <section class="mk-slide">

            <div class="mk-slide-inner">

                <div class="mk-visual">

                    <div class="mk-visual-card">

                        <?php if ($first_logo): ?>
                            <img src="<?= htmlspecialchars($first_logo, ENT_QUOTES, 'UTF-8') ?>" alt="ملکینو">
                        <?php else: ?>
                            <svg class="mk-icon" viewBox="0 0 24 24" fill="none">
                                <path d="M4 18H20" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                <path d="M5 18L4 7L9 11L12 5L15 11L20 7L19 18" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                            </svg>
                        <?php endif; ?>

                    </div>

                </div>

                <h1 class="mk-title"><?= htmlspecialchars($first_title, ENT_QUOTES, 'UTF-8') ?></h1>

                <p class="mk-description"><?= nl2br(htmlspecialchars($first_text, ENT_QUOTES, 'UTF-8'), false) ?></p>

            </div>

        </section>

    </main>

    <div class="mk-controls">
        <button type="button" class="mk-nav-btn primary" id="goHomeBtn" style="flex:1;max-width:360px;margin:0 auto;">
            ورود به خانه
            <span class="mk-nav-arrow">←</span>
        </button>
    </div>



<?= \Melkino\Support\Assets::js('assets/js/index-buttons.js', false) ?>
</body>
</html>