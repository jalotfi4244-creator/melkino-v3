<?php
/**
 * Melkino V2 — calculator view (mkc-* skeleton VERBATIM; engine + logic external).
 * Vars: $calcRates, $calcEnabled, $calcIsAdmin.
 */
?>
<script type="application/json" id="mxCalcData"><?= json_encode(['rates' => $calcRates ?? []], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<div class="mkc-page">
    <div class="mkc-wrap">
        <section class="mkc-hero">
            <div class="mkc-kicker"><i></i> ابزار مشاور املاک</div>
            <h1>ماشین‌حساب <span>قیمت‌گذاری ملک</span></h1>
            <p>قیمت تقریبی آپارتمان را با استهلاک سن بنا، وقفی، پارکینگ، آسانسور، حیاط و آیتم‌های اضافی مثل انباری محاسبه کنید.</p>
        </section>

<?php if (!$calcEnabled && !$calcIsAdmin): ?>
        <div class="mkc-card mkc-off">
            <h2>غیرفعال</h2>
            <p>ماشین‌حساب قیمت ملک فعلاً در دسترس نیست.</p>
        </div>
<?php else: ?>
        <?php if (!$calcEnabled && $calcIsAdmin): ?>
        <div class="mkc-error show">ماشین‌حساب برای عموم خاموش است؛ شما به‌عنوان مدیر می‌توانید تست کنید.</div>
        <?php endif; ?>

        <form id="mkcForm" novalidate>
            <div class="mkc-card">
                <h2>اطلاعات اصلی ملک</h2>
                <div class="mkc-grid">
                    <div class="mkc-field full">
                        <label for="mkcNewMeter">قیمت هر مترمربع آپارتمان نوساز (تومان)</label>
                        <input id="mkcNewMeter" name="newMeterPrice" inputmode="numeric" placeholder="مثلاً ۶۰٬۰۰۰٬۰۰۰" autocomplete="off">
                        <div class="mkc-words" id="mkcNewMeterWords"></div>
                        <div class="mkc-hint">عدد را به تومان وارد کنید؛ جداکنندهٔ سه‌رقمی خودکار است.</div>
                    </div>
                    <div class="mkc-field">
                        <label for="mkcArea">متراژ آپارتمان (متر)</label>
                        <input id="mkcArea" name="area" inputmode="decimal" placeholder="مثلاً ۱۰۰">
                    </div>
                    <div class="mkc-field">
                        <label for="mkcAge">سن بنا (سال)</label>
                        <input id="mkcAge" name="age" inputmode="decimal" placeholder="مثلاً ۸">
                    </div>
                    <div class="mkc-field">
                        <label for="mkcFloor">طبقه <span class="mkc-hint" style="font-weight:600">(همکف = ۰)</span></label>
                        <input id="mkcFloor" name="floor" inputmode="numeric" placeholder="مثلاً ۳">
                    </div>
                    <div class="mkc-field">
                        <label for="mkcFloors">تعداد طبقات ساختمان</label>
                        <input id="mkcFloors" name="totalFloors" inputmode="numeric" placeholder="مثلاً ۵">
                    </div>
                </div>
            </div>

            <div class="mkc-card">
                <h2>امکانات و وضعیت ملک</h2>
                <div class="mkc-grid">
                    <div class="mkc-field">
                        <label>آیا ملک وقفی است؟</label>
                        <div class="mkc-seg" data-mkc="waqf">
                            <button type="button" data-v="0" class="on">خیر</button>
                            <button type="button" data-v="1">بله</button>
                        </div>
                    </div>
                    <div class="mkc-field">
                        <label>آیا پارکینگ دارد؟</label>
                        <div class="mkc-seg" data-mkc="parking">
                            <button type="button" data-v="1" class="on">بله</button>
                            <button type="button" data-v="0">خیر</button>
                        </div>
                    </div>
                    <div class="mkc-field full mkc-hidden" id="mkcParkWrap">
                        <label for="mkcParkRate">درصد کاهش بابت نداشتن پارکینگ</label>
                        <input id="mkcParkRate" inputmode="decimal" step="0.001">
                        <div class="mkc-hint" id="mkcParkHint"></div>
                    </div>
                    <div class="mkc-field">
                        <label>آیا آسانسور دارد؟</label>
                        <div class="mkc-seg" data-mkc="elevator">
                            <button type="button" data-v="1" class="on">بله</button>
                            <button type="button" data-v="0">خیر</button>
                        </div>
                    </div>
                    <div class="mkc-field">
                        <label>آیا حیاط اختصاصی دارد؟</label>
                        <div class="mkc-seg" data-mkc="yard">
                            <button type="button" data-v="0" class="on">خیر</button>
                            <button type="button" data-v="1">بله</button>
                        </div>
                    </div>
                    <div class="mkc-field full mkc-hidden" id="mkcYardWrap">
                        <label for="mkcYardArea">متراژ حیاط اختصاصی (متر)</label>
                        <input id="mkcYardArea" name="yardArea" inputmode="decimal" placeholder="مثلاً ۳۰">
                    </div>
                    <?php foreach ($calcExtras as $ex):
                        $eid = htmlspecialchars((string)$ex['id'], ENT_QUOTES, 'UTF-8');
                        $elabel = htmlspecialchars((string)$ex['label'], ENT_QUOTES, 'UTF-8');
                        $emode = (string)($ex['mode'] ?? 'area_ratio');
                    ?>
                    <div class="mkc-field">
                        <label>آیا <?= $elabel ?> دارد؟</label>
                        <div class="mkc-seg" data-mkc="extra" data-extra-id="<?= $eid ?>">
                            <button type="button" data-v="0" class="on">خیر</button>
                            <button type="button" data-v="1">بله</button>
                        </div>
                    </div>
                    <?php if ($emode === 'area_ratio'): ?>
                    <div class="mkc-field mkc-hidden" id="mkcExtraArea_<?= $eid ?>">
                        <label for="mkcExtraAreaInput_<?= $eid ?>">متراژ <?= $elabel ?> (متر)</label>
                        <input id="mkcExtraAreaInput_<?= $eid ?>" inputmode="decimal" placeholder="مثلاً ۸">
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mkc-card">
                <h2>رهن و اجاره</h2>
                <div class="mkc-field">
                    <label>آیا محاسبه رهن و اجاره نیز انجام شود؟</label>
                    <div class="mkc-seg" data-mkc="rent">
                        <button type="button" data-v="0" class="on">خیر</button>
                        <button type="button" data-v="1">بله</button>
                    </div>
                </div>
                <div class="mkc-field mkc-hidden" id="mkcRentWrap" style="margin-top:10px;">
                    <label for="mkcExtraRent">اجاره ماهانه برای تبدیل به رهن معادل (تومان) — اختیاری</label>
                    <input id="mkcExtraRent" name="extraRentMonthly" inputmode="numeric" placeholder="مثلاً ۱۵٬۰۰۰٬۰۰۰">
                    <div class="mkc-words" id="mkcExtraRentWords"></div>
                    <div class="mkc-hint">اگر عددی وارد کنید، رهن معادل همان اجاره هم محاسبه می‌شود.</div>
                </div>
            </div>

            <div class="mkc-actions">
                <button type="submit" class="mkc-btn primary">محاسبه قیمت ملک</button>
                <button type="button" class="mkc-btn ghost" id="mkcReset">پاک کردن اطلاعات</button>
            </div>
        </form>

        <div class="mkc-error" id="mkcError"></div>

        <div class="mkc-result" id="mkcResult">
            <div class="mkc-hero-price">
                <small>قیمت تخمینی ملک</small>
                <strong id="mkcOutFinal">—</strong>
                <em>قیمت هر متر: <span id="mkcOutMeter">—</span></em>
                <span class="mkc-meter-words" id="mkcOutMeterWords"></span>
            </div>
            <div class="mkc-card">
                <h2>جزئیات محاسبه</h2>
                <div class="mkc-kv" id="mkcDetails"></div>
            </div>
            <div class="mkc-card mkc-hidden" id="mkcRentCard">
                <h2>بخش رهن و اجاره</h2>
                <div class="mkc-kv" id="mkcRentDetails"></div>
            </div>
            <div class="mkc-card">
                <h2>نحوه محاسبه</h2>
                <div class="mkc-formula" id="mkcFormula"></div>
            </div>
        </div>
<?php endif; ?>
    </div>
</div>

<?php if ($calcEnabled || $calcIsAdmin): ?>



<?php endif; ?>
<?php require_once dirname(__DIR__, 2) . '/
