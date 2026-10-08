<?php
/** Melkino V2 — admin users tab. Fragment VERBATIM from admin-panel.php (USERS section). */
?>
<!-- =========================================================
     USERS
     ========================================================= -->

<div
    role="tabpanel"
    class="tab-content"
    id="tab-users"
>

    <div class="admin-card">

        <div class="mk-page-head">
            <div>
                <h2 class="mk-page-title"><?= melkinoSvgIcon('users') ?> مدیریت کاربران</h2>
            </div>
        </div>

        <div class="stats-grid" style="padding:0 16px;">

            <div class="stat-card">
                <div class="number" id="usersStatTotal">…</div>
                <div class="label">کاربران ثبت‌شده</div>
            </div>

            <div class="stat-card">
                <div class="number" id="usersStatVisits">…</div>
                <div class="label">ورود ۲۴ ساعت اخیر</div>
            </div>

        </div>

        <div id="usersListContainer"></div>

    </div>

</div>
