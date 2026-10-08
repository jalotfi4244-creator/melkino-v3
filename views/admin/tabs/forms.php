<?php
/** Melkino V2 — admin forms tab. Fragment VERBATIM from admin-panel.php (FORMS section,
 * inline <script> moved to admin-tab-forms.js). Requires form-options.php (controller loads it). */
?>

<!-- =========================================================
     FORMS — گزینه‌های کمبوباکس
     ========================================================= -->

<div
    role="tabpanel"
    class="tab-content"
    id="tab-forms"
>
    <div class="admin-card">
        <div class="admin-section-head">
            <div>
                <div class="admin-section-title">گزینه‌های کمبوباکس فرم‌ها</div>
                <div class="admin-section-help">موارد هر فهرست در ثبت ملک و ثبت درخواست از دیتابیس خوانده می‌شود. گزینه را اضافه/حذف کنید و حتماً دکمه ذخیره را بزنید.</div>
            </div>
            <button type="button" class="btn-primary" id="formsCombosSaveBtn" data-act="forms-save" style="min-width:220px;height:48px;padding:0 22px;font-size:15px;font-weight:800;cursor:pointer;">ذخیره فرم‌ها در دیتابیس</button>
        </div>
        <div id="formsCombosBox" style="display:grid;gap:14px;margin-top:12px;">
<?php
if (function_exists('melkinoFormComboCatalog')) {
    foreach (melkinoFormComboCatalog() as $comboKey => $comboDef) {
        $comboLabel = (string) ($comboDef['label'] ?? $comboKey);
        $comboItems = is_array($comboDef['items'] ?? null) ? $comboDef['items'] : [];
        echo '<div class="admin-field" data-combo="' . htmlspecialchars($comboKey, ENT_QUOTES, 'UTF-8') . '" style="border:1px solid rgba(212,175,55,.22);border-radius:12px;padding:12px;">';
        echo '<label style="font-weight:800;margin-bottom:8px;display:block;">' . htmlspecialchars($comboLabel, ENT_QUOTES, 'UTF-8') . '</label>';
        echo '<div class="forms-combo-list">';
        foreach ($comboItems as $comboItem) {
            $comboItem = trim((string) $comboItem);
            if ($comboItem === '') {
                continue;
            }
            echo '<div style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">';
            echo '<input type="text" value="' . htmlspecialchars($comboItem, ENT_QUOTES, 'UTF-8') . '" style="flex:1;">';
            echo '<button type="button" class="btn-icon-sm" data-act="forms-del">حذف</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="btn-icon-sm" data-act="forms-add">+ گزینه</button>';
        echo '</div>';
    }
}
?>
        </div>
        <p id="formsCombosMsg" class="admin-section-help" style="margin-top:10px;"></p>
        <button type="button" class="btn-primary" data-act="forms-save" style="min-width:220px;height:48px;padding:0 22px;font-size:15px;font-weight:800;margin-top:8px;cursor:pointer;">ذخیره فرم‌ها در دیتابیس</button>
    </div>
</div>
