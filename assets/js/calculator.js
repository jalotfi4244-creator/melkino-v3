/* Melkino V2 — calculator page logic, extracted VERBATIM from property-calculator.php. */
/* V2: rates come from #mxCalcData JSON (CSP-safe). */
window.MELKINO_CALC_RATES = (function () {
    try {
        var el = document.getElementById('mxCalcData');
        var d = el ? JSON.parse(el.textContent || '{}') : {};
        return (d && d.rates) || {};
    } catch (e) { return {}; }
})();

(function () {
    var C = window.MelkinoPropertyCalc;
    if (!C) return;
    var R = C.rates();
    var flags = { waqf: 0, parking: 1, elevator: 1, yard: 0, rent: 0 };
    var extraFlags = {};

    function pctShow(rate) {
        return C.formatPct(rate);
    }
    function fillPark() {
        var el = document.getElementById('mkcParkRate');
        var hint = document.getElementById('mkcParkHint');
        if (!el) return;
        var def = (R.NO_PARKING_DISCOUNT_DEFAULT || 0) * 100;
        var mn = (R.NO_PARKING_DISCOUNT_MIN || 0) * 100;
        var mx = (R.NO_PARKING_DISCOUNT_MAX || 0) * 100;
        el.value = C.toFaDigits(String(def));
        el.setAttribute('data-min', String(mn));
        el.setAttribute('data-max', String(mx));
        if (hint) hint.textContent = 'بازه مجاز: ' + pctShow(R.NO_PARKING_DISCOUNT_MIN) + ' تا ' + pctShow(R.NO_PARKING_DISCOUNT_MAX);
        if (Math.abs(mx - mn) < 0.0000001) {
            el.readOnly = true;
        }
    }
    fillPark();

    document.querySelectorAll('.mkc-seg').forEach(function (seg) {
        seg.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            seg.querySelectorAll('button').forEach(function (b) { b.classList.remove('on'); });
            btn.classList.add('on');
            var key = seg.getAttribute('data-mkc');
            var v = Number(btn.getAttribute('data-v'));
            if (key === 'extra') {
                var id = seg.getAttribute('data-extra-id');
                extraFlags[id] = v === 1;
                var wrap = document.getElementById('mkcExtraArea_' + id);
                if (wrap) wrap.classList.toggle('mkc-hidden', v !== 1);
                return;
            }
            flags[key] = v;
            document.getElementById('mkcParkWrap').classList.toggle('mkc-hidden', flags.parking === 1);
            document.getElementById('mkcYardWrap').classList.toggle('mkc-hidden', flags.yard !== 1);
            document.getElementById('mkcRentWrap').classList.toggle('mkc-hidden', flags.rent !== 1);
        });
    });

    function bindMoney(id, wordsId) {
        var el = document.getElementById(id);
        var w = wordsId ? document.getElementById(wordsId) : null;
        if (!el) return;
        el.addEventListener('input', function () {
            var n = C.parseNumber(el.value);
            if (!isFinite(n) || n < 0) {
                if (w) w.textContent = '';
                return;
            }
            var grouped = C.formatGrouped(Math.round(n));
            if (el.value !== grouped) el.value = grouped;
            if (w) w.textContent = n > 0 ? C.tomanToWords(n) : '';
        });
        el.addEventListener('blur', function () {
            var n = C.parseNumber(el.value);
            if (isFinite(n) && n > 0) el.value = C.formatGrouped(Math.round(n));
        });
    }
    bindMoney('mkcNewMeter', 'mkcNewMeterWords');
    bindMoney('mkcExtraRent', 'mkcExtraRentWords');

    function val(id) { return (document.getElementById(id) || {}).value || ''; }
    function row(label, value, cls) {
        return '<div class="mkc-row' + (cls ? ' ' + cls : '') + '"><span>' + label + '</span><b>' + value + '</b></div>';
    }
    function collectExtras() {
        var out = {};
        document.querySelectorAll('.mkc-seg[data-mkc="extra"]').forEach(function (seg) {
            var id = seg.getAttribute('data-extra-id');
            var areaEl = document.getElementById('mkcExtraAreaInput_' + id);
            out[id] = {
                has: extraFlags[id] === true,
                area: areaEl ? areaEl.value : ''
            };
        });
        return out;
    }

    document.getElementById('mkcForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var errEl = document.getElementById('mkcError');
        var box = document.getElementById('mkcResult');
        var parkPct = C.parseNumber(val('mkcParkRate'));
        var out = C.calculate({
            newMeterPrice: val('mkcNewMeter'),
            area: val('mkcArea'),
            age: val('mkcAge'),
            floor: val('mkcFloor'),
            totalFloors: val('mkcFloors'),
            isWaqf: flags.waqf === 1,
            hasParking: flags.parking === 1,
            hasElevator: flags.elevator === 1,
            hasYard: flags.yard === 1,
            yardArea: val('mkcYardArea'),
            wantRent: flags.rent === 1,
            noParkingDiscount: isFinite(parkPct) ? parkPct / 100 : R.NO_PARKING_DISCOUNT_DEFAULT,
            extraRentMonthly: val('mkcExtraRent'),
            extras: collectExtras()
        });
        if (!out.ok) {
            errEl.innerHTML = out.errors.map(function (x) { return '• ' + x; }).join('<br>');
            errEl.classList.add('show');
            box.classList.remove('show');
            errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        errEl.classList.remove('show');
        var r = out.result;
        document.getElementById('mkcOutFinal').textContent = C.formatToman(r.finalPrice);
        document.getElementById('mkcOutMeter').textContent = C.formatToman(r.meterPrice);
        document.getElementById('mkcOutMeterWords').textContent = r.meterPriceWords || C.tomanToWords(r.meterPrice);
        var html = '';
        html += row('قیمت نوساز', C.formatToman(r.newMeterPrice));
        html += row('سن بنا', C.toFaDigits(r.age) + ' سال');
        html += row('درصد استهلاک', C.formatPct(r.depRate));
        html += row('قیمت محاسبه‌شده هر متر', C.formatToman(r.meterPrice));
        html += row('قیمت هر متر به حروف', r.meterPriceWords || C.tomanToWords(r.meterPrice));
        html += row('قیمت پایه ملک', C.formatToman(r.baseTotal));
        html += row('کسر وقفی', r.isWaqf ? ('− ' + C.formatToman(r.waqfCut) + ' (' + C.formatPct(r.rates.VAGHFI_DISCOUNT) + ')') : 'اعمال نشد', r.isWaqf ? 'cut' : '');
        html += row('کسر نداشتن پارکینگ', r.hasParking ? 'اعمال نشد' : ('− ' + C.formatToman(r.parkingCut) + ' (' + C.formatPct(r.parkingRate) + ')'), r.hasParking ? '' : 'cut');
        html += row('کسر آسانسور', r.hasElevator ? 'اعمال نشد' : (r.elevPct > 0 ? ('− ' + C.formatToman(r.elevCut) + ' (' + C.formatPct(r.elevPct) + ')') : 'طبقه همکف/اول — بدون کاهش'), r.elevPct > 0 ? 'cut' : '');
        html += row('ارزش حیاط اختصاصی', r.hasYard ? C.formatToman(r.yardValue) : 'اعمال نشد', r.hasYard ? 'add' : '');
        (r.extras || []).forEach(function (ex) {
            if (!ex.has) {
                html += row(ex.label, 'اعمال نشد');
                return;
            }
            var cls = ex.mode === 'percent_cut' ? 'cut' : 'add';
            var sign = ex.mode === 'percent_cut' ? '− ' : '';
            html += row(ex.label, sign + C.formatToman(ex.value) + ' (' + C.formatPct(ex.ratio) + ')', cls);
        });
        html += row('قیمت نهایی', C.formatToman(r.finalPrice), 'total');
        document.getElementById('mkcDetails').innerHTML = html;

        var rentCard = document.getElementById('mkcRentCard');
        if (r.wantRent) {
            var rh = '';
            rh += row('رهن کامل پیشنهادی', C.formatToman(r.fullRent), 'total');
            rh += row('معادل اجاره ماهانه', C.formatToman(r.monthlyFromFull));
            rh += row('معادل رهن برای اجاره واردشده', r.rentToDeposit != null ? C.formatToman(r.rentToDeposit) : 'اجاره‌ای وارد نشده');
            document.getElementById('mkcRentDetails').innerHTML = rh;
            rentCard.classList.remove('mkc-hidden');
        } else {
            rentCard.classList.add('mkc-hidden');
        }

        var f = [];
        f.push('<h3>فرمول‌ها</h3>');
        f.push('قیمت هر متر = قیمت نوساز × (۱ − سن بنا × ' + C.formatPct(r.rates.ESTEHKLAK_RATE) + ')');
        f.push('قیمت هر متر = ' + C.formatToman(r.newMeterPrice) + ' × (۱ − ' + C.formatPct(r.depRate) + ') = ' + C.formatToman(r.meterPrice));
        f.push(r.meterPriceWords || '');
        f.push('قیمت پایه = متراژ × قیمت هر متر = ' + C.toFaDigits(r.area) + ' × ' + C.formatToman(r.meterPrice) + ' = ' + C.formatToman(r.baseTotal));
        if (r.isWaqf) f.push('وقفی: قیمت × (۱ − ' + C.formatPct(r.rates.VAGHFI_DISCOUNT) + ') → کسر ' + C.formatToman(r.waqfCut));
        if (!r.hasParking) f.push('بدون پارکینگ: قیمت × (۱ − ' + C.formatPct(r.parkingRate) + ') → کسر ' + C.formatToman(r.parkingCut));
        if (!r.hasElevator && r.elevPct > 0) f.push('بدون آسانسور: (طبقه − ۱) × ' + C.formatPct(r.rates.NO_ELEVATOR_RATE) + ' = ' + C.formatPct(r.elevPct) + ' → کسر ' + C.formatToman(r.elevCut));
        else if (!r.hasElevator) f.push('بدون آسانسور در همکف/طبقه اول: بدون کاهش');
        if (r.hasYard) f.push('حیاط: (قیمت هر متر × ' + C.formatPct(r.rates.YARD_RATIO) + ') × متراژ حیاط = ' + C.formatToman(r.yardPerMeter) + ' × ' + C.toFaDigits(r.yardArea) + ' = ' + C.formatToman(r.yardValue));
        (r.extras || []).forEach(function (ex) {
            if (!ex.applied) return;
            if (ex.mode === 'area_ratio') {
                f.push(ex.label + ': متراژ × قیمت هر متر × ' + C.formatPct(ex.ratio) + ' = ' + C.formatToman(ex.value));
            } else if (ex.mode === 'percent_cut') {
                f.push(ex.label + ': قیمت × ' + C.formatPct(ex.ratio) + ' → کسر ' + C.formatToman(ex.value));
            } else {
                f.push(ex.label + ': قیمت × ' + C.formatPct(ex.ratio) + ' → افزایش ' + C.formatToman(ex.value));
            }
        });
        f.push('قیمت نهایی = ' + C.formatToman(r.finalPrice));
        if (r.wantRent) {
            f.push('رهن کامل = قیمت نهایی ÷ ' + C.toFaDigits(r.rates.FULL_RENT_DIVISOR) + ' = ' + C.formatToman(r.fullRent));
            f.push('اجاره ماهانه = (رهن ÷ ' + C.formatToman(r.rates.RENT_BASE) + ') × ' + C.formatToman(r.rates.RENT_PER_100M) + ' = ' + C.formatToman(r.monthlyFromFull));
            if (r.rentToDeposit != null) f.push('رهن معادل اجاره = (اجاره ÷ ' + C.formatToman(r.rates.RENT_PER_100M) + ') × ' + C.formatToman(r.rates.RENT_BASE) + ' = ' + C.formatToman(r.rentToDeposit));
        }
        document.getElementById('mkcFormula').innerHTML = f.join('<br>');
        box.classList.add('show');
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.getElementById('mkcReset').addEventListener('click', function () {
        document.getElementById('mkcForm').reset();
        flags = { waqf: 0, parking: 1, elevator: 1, yard: 0, rent: 0 };
        extraFlags = {};
        fillPark();
        document.querySelectorAll('.mkc-seg').forEach(function (seg) {
            var key = seg.getAttribute('data-mkc');
            var want = key === 'extra' ? '0' : String(flags[key]);
            seg.querySelectorAll('button').forEach(function (b) {
                b.classList.toggle('on', b.getAttribute('data-v') === want);
            });
        });
        document.querySelectorAll('[id^="mkcExtraArea_"]').forEach(function (el) { el.classList.add('mkc-hidden'); });
        document.getElementById('mkcParkWrap').classList.add('mkc-hidden');
        document.getElementById('mkcYardWrap').classList.add('mkc-hidden');
        document.getElementById('mkcRentWrap').classList.add('mkc-hidden');
        document.getElementById('mkcNewMeterWords').textContent = '';
        var rw = document.getElementById('mkcExtraRentWords');
        if (rw) rw.textContent = '';
        document.getElementById('mkcError').classList.remove('show');
        document.getElementById('mkcResult').classList.remove('show');
    });
})();
