<?php
declare(strict_types=1);

namespace Melkino\UI\Icons;

/**
 * Melkino V2 — THE icon registry (spec §45, §46, §128).
 * All UI icons are local SVG (currentColor), no emoji icons in the main UI.
 * Canonical names; legacy field-icons.php aliases delegate here.
 */
final class IconRegistry
{
    /** @var array<string,string> name => inner SVG (24x24, stroke=currentColor) */
    private const ICONS = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'heart' => '<path d="M12 20.5C7 16.5 3 13.3 3 9.3 3 6.4 5.2 4.5 7.7 4.5c1.7 0 3.3.9 4.3 2.4 1-1.5 2.6-2.4 4.3-2.4 2.5 0 4.7 1.9 4.7 4.8 0 4-4 7.2-9 11.2Z"/>',
        'heart-fill' => '<path fill="currentColor" stroke="none" d="M12 20.5C7 16.5 3 13.3 3 9.3 3 6.4 5.2 4.5 7.7 4.5c1.7 0 3.3.9 4.3 2.4 1-1.5 2.6-2.4 4.3-2.4 2.5 0 4.7 1.9 4.7 4.8 0 4-4 7.2-9 11.2Z"/>',
        'dashboard' => '<rect x="3" y="3" width="8" height="9" rx="1.5"/><rect x="13" y="3" width="8" height="5.5" rx="1.5"/><rect x="13" y="10.5" width="8" height="10.5" rx="1.5"/><rect x="3" y="14" width="8" height="7" rx="1.5"/>',
        'bot' => '<rect x="4" y="8" width="16" height="11" rx="3"/><circle cx="9.5" cy="13" r="1" fill="currentColor"/><circle cx="14.5" cy="13" r="1" fill="currentColor"/><path d="M12 8V3.5"/><circle cx="12" cy="3" r="1"/>',
        'db' => '<ellipse cx="12" cy="5.5" rx="7.5" ry="2.8"/><path d="M4.5 5.5v13c0 1.5 3.4 2.8 7.5 2.8s7.5-1.3 7.5-2.8v-13"/><path d="M4.5 12c0 1.5 3.4 2.8 7.5 2.8s7.5-1.3 7.5-2.8"/>',
        'palette' => '<circle cx="12" cy="12" r="8.5"/><circle cx="9" cy="10" r="1.2" fill="currentColor"/><circle cx="13.5" cy="9" r="1.2" fill="currentColor"/><circle cx="15.5" cy="13" r="1.2" fill="currentColor"/><path d="M12 20.5c-1.5-2-1-4.5.5-6"/>',
        'logo' => '<path d="M4 11 12 4l8 7"/><path d="M6.5 9.5V19h11V9.5"/><path d="M12 19v-5"/>',
        'compare' => '<path d="M8 3H4v18h4"/><path d="M20 3h-4v18h4"/><path d="M12 7v10"/>',
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 5-6 8-6s6.5 2 8 6"/>',
        'pin' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a12 12 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-8 8H4l2-3a8 8 0 1 1 15-5Z"/>',
        'area' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 12h16M12 4v16"/>',
        'bed' => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><path d="M3 18h18M5 10V6h14v4"/>',
        'floor' => '<path d="M4 20h16M6 20V9l6-5 6 5v11"/><path d="M10 20v-5h4v5"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M8 3v4M16 3v4"/>',
        'check' => '<path d="m4 12.5 5 5L20 6.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'warn' => '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17.5v.5"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5V8"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m4 18 5-5 3 3 4-4 4 4"/>',
        'filter' => '<path d="M4 5h16l-6 7v6l-4 2v-8L4 5Z"/>',
        'sort' => '<path d="M7 4v16m0 0-3-3m3 3 3-3M17 20V4m0 0-3 3m3-3 3 3"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'arrow-left' => '<path d="M19 12H5m0 0 6-6m-6 6 6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4L20 8l-4-4L4 16v4Z"/><path d="m13 7 4 4"/>',
        'trash' => '<path d="M4 7h16M10 4h4M7 7l1 13h8l1-13"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'send' => '<path d="M21 3 10 14M21 3l-7 18-4-7-7-4 18-7Z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 2h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6A7 7 0 0 0 5 12c0 .4 0 .8.1 1.2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 22h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z"/>',
        'chart' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-5m4 5V8m4 8v-3"/>',
        'activity' => '<path d="M3 12h4l3 8 4-16 3 8h4"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9m-4 4 3 3"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/>',
        'building' => '<rect x="5" y="3" width="14" height="18"/><path d="M9 7h2m2 0h2M9 11h2m2 0h2M9 15h2m2 0h2M10 21v-3h4v3"/>',
        'land' => '<path d="M3 18 9 8l4 5 3-3 5 8H3Z"/><circle cx="17" cy="5" r="2"/>',
        'villa' => '<path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'store' => '<path d="M4 9l1-4h14l1 4"/><path d="M4 9h16v11H4V9Z"/><path d="M9 20v-6h6v6"/>',
        'office' => '<rect x="4" y="3" width="12" height="18"/><path d="M16 8h4v13M8 7h4m-4 4h4m-4 4h4"/>',
        'doc' => '<path d="M6 2h9l5 5v15H6V2Z"/><path d="M14 2v6h6M9 13h6m-6 4h6"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.3-5.6M20 4v4h-4"/>',
        'share' => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6m-7.6 6 7.6 3.6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="5" cy="6" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="18" r="1"/>',
        'map' => '<path d="m9 4-5 2v14l5-2 6 2 5-2V4l-5 2-6-2ZM9 4v14m6 4V6"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4V8Z"/><circle cx="12" cy="13" r="3.5"/>',
        'gift' => '<rect x="4" y="9" width="16" height="11"/><path d="M3 5h18v4H3zM12 5v15"/>',
        'megaphone' => '<path d="m3 11 18-5v12L3 14v-3Z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'headset' => '<path d="M4 13a8 8 0 0 1 16 0"/><rect x="3" y="13" width="4" height="7" rx="1.5"/><rect x="17" y="13" width="4" height="7" rx="1.5"/><path d="M19 20a4 4 0 0 1-4 2h-2"/>',
        'spark' => '<path d="M12 2v6m0 8v6M2 12h6m8 0h6"/><circle cx="12" cy="12" r="2"/>',
    ];

    /** Legacy alias map (field-icons.php names => canonical). */
    private const ALIASES = [
        'location' => 'pin', 'bedroom' => 'bed', 'bathroom' => 'bath', 'ruler' => 'area',
        'money' => 'wallet', 'price' => 'wallet', 'favorite' => 'heart', 'notification' => 'bell',
        'profile' => 'user', 'message' => 'chat', 'call' => 'phone', 'mail' => 'send',
        'delete' => 'trash', 'close' => 'x', 'success' => 'check', 'error' => 'x',
        'warning' => 'warn', 'apartment' => 'building', 'house' => 'villa', 'garden' => 'land',
        'commercial' => 'store', 'request' => 'doc', 'support' => 'headset',
    ];

    public static function has(string $name): bool
    {
        $name = self::ALIASES[$name] ?? $name;
        return isset(self::ICONS[$name]);
    }

    /**
     * Render an icon. Decorative by default (aria-hidden); pass $label for meaningful icons.
     */
    public static function svg(string $name, int $size = 20, ?string $label = null, string $class = ''): string
    {
        $name = self::ALIASES[$name] ?? $name;
        $inner = self::ICONS[$name] ?? self::ICONS['info'];
        $attrs = $label !== null
            ? ' role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"'
            : ' aria-hidden="true"';
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none"'
            . ' stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"'
            . $attrs . $cls . '>' . $inner . '</svg>';
    }

    /** @return string[] */
    public static function names(): array
    {
        return array_keys(self::ICONS);
    }
}

if (!function_exists('mk_icon')) {
    function mk_icon(string $name, int $size = 20, ?string $label = null, string $class = ''): string
    {
        return IconRegistry::svg($name, $size, $label, $class);
    }
}
