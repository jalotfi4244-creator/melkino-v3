/* Melkino V2 — studio/presets: simplified preset groups (spec §42). */
(function () {
  'use strict';
  window.Melkino = window.Melkino || {};
  var M = window.Melkino;
  M.Studio = M.Studio || {};
  M.Studio.presets = {
    groups: [
      { key: 'style', label: 'استایل', options: [['minimal', 'مینیمال'], ['modern', 'مدرن'], ['luxury', 'لوکس']] },
      { key: 'color', label: 'رنگ', options: [['teal', 'سبز'], ['light', 'روشن'], ['dark', 'تیره']] },
      { key: 'card', label: 'کارت', options: [['standard', 'استاندارد'], ['premium', 'ویژه'], ['compact', 'فشرده']] },
      { key: 'radius', label: 'گردی', options: [['tight', 'کم'], ['soft', 'متوسط'], ['rounded', 'زیاد']] },
      { key: 'shadow', label: 'سایه', options: [['none', 'بدون'], ['soft', 'ملایم'], ['strong', 'قوی']] }
    ]
  };
})();
