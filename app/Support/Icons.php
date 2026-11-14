<?php

namespace App\Support;

/** Set ikon SVG (garis, 24x24) — dipakai lewat <x-icon name="..."/>. Tanpa emoji. */
class Icons
{
    private const SET = [
        'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10Z"/><path d="M2 21c0-3 1.9-5.4 5.1-6.4C9.5 13.9 12 13 14 11"/>',
        'sprout' => '<path d="M7 20h10"/><path d="M12 20v-9"/><path d="M12 11C12 7.5 9.5 5 6 5c0 3.5 2.5 6 6 6Z"/><path d="M12 13c0-3 2-5 5-5 0 3-2 5-5 5Z"/>',
        'copy' => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'cactus' => '<path d="M12 21V6a2 2 0 0 1 4 0v3"/><path d="M12 21V8a2 2 0 0 0-4 0v1"/><path d="M8 13H6a2 2 0 0 1-2-2V9"/><path d="M16 15h2a2 2 0 0 0 2-2v-2"/><path d="M8 21h8"/>',
        'flower' => '<circle cx="12" cy="12" r="2.5"/><path d="M12 9.5C10.5 7 11 4 12 3c1 1 1.500 4 0 6.500ZM14.500 12c2.500-1.500 5.500-1 6.500 0-1 1-4 1.500-6.500 0ZM12 14.500c1.500 2.500 1 5.500 0 6.500-1-1-1.500-4 0-6.500ZM9.500 12C7 13.500 4 13 3 12c1-1 4-1.500 6.500 0Z"/>',
        'desk' => '<rect x="3" y="9" width="18" height="3" rx="1"/><path d="M5 12v8M19 12v8"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'pot' => '<path d="M5 9h14l-1.500 10a2 2 0 0 1-2 1.700h-7A2 2 0 0 1 6.500 19L5 9Z"/><path d="M4 9h16"/><path d="M12 9V5"/><path d="M12 6c0-2 1.500-3 3.500-3 0 2-1.500 3-3.500 3Z"/>',
        'droplet' => '<path d="M12 3s6 6.200 6 10.500A6 6 0 0 1 6 13.500C6 9.200 12 3 12 3Z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 4.500-3.200 8-8 9-4.800-1-8-4.500-8-9V6l8-3Z"/><path d="M9 12l2 2 4-4"/>',
        'truck' => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'package' => '<path d="M21 8l-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'message' => '<path d="M21 12a8 8 0 0 1-11.600 7.100L4 20l1-4.600A8 8 0 1 1 21 12Z"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4V5Z"/>',
        'heart' => '<path d="M12 20s-8-4.800-8-11a4.500 4.500 0 0 1 8-2.800A4.500 4.500 0 0 1 20 9c0 6.200-8 11-8 11Z"/>',
        'cart' => '<circle cx="9" cy="20" r="1.500"/><circle cx="18" cy="20" r="1.500"/><path d="M2 3h3l2.600 12.400a1 1 0 0 0 1 .800h8.800a1 1 0 0 0 1-.800L20 7H6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.600-7 8-7s8 3 8 7"/>',
        'users' => '<circle cx="9" cy="8" r="3.500"/><path d="M2 20c0-3.500 3-6 7-6s7 2.500 7 6"/><path d="M16 4.500a3.500 3.500 0 0 1 0 7M18 14c2.500.7 4 2.700 4 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.500-3.500"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'check' => '<path d="M5 12.500l4.500 4.500L19 7.500"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8.500 12.500l2.500 2.500 4.500-5"/>',
        'star' => '<path d="M12 3l2.800 5.800 6.200.9-4.500 4.400 1.100 6.200L12 17.400 6.400 20.300l1.100-6.200L3 9.700l6.200-.9L12 3Z"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'arrow-up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'map-pin' => '<path d="M12 21s7-6.200 7-11.500a7 7 0 0 0-14 0C5 14.800 12 21 12 21Z"/><circle cx="12" cy="9.500" r="2.500"/>',
        'phone' => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.500" cy="6.500" r=".6"/>',
        'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3V9Z"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.500A.500.500 0 0 1 14 8Z"/>',
        'tiktok' => '<path d="M14 3v11.500a3.500 3.500 0 1 1-3.500-3.500"/><path d="M14 3c.5 2.500 2.200 4 5 4.200"/>',
        'sparkle' => '<path d="M12 3l1.800 5.200L19 10l-5.200 1.800L12 17l-1.800-5.200L5 10l5.200-1.800L12 3Z"/><path d="M19 16v4M17 18h4"/>',
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.500 8L20 19M8.500 16L20 5"/>',
        'shovel' => '<path d="M14 4l6 6"/><path d="M17 3l4 4-3 1-2-2 1-3Z"/><path d="M14 8L5 17a3 3 0 0 0 2 5h0a3 3 0 0 0 0-3"/>',
        'tool' => '<path d="M14.500 6.500a4 4 0 0 0 5 5L10 21a2.800 2.800 0 0 1-4-4l9.500-9.500a4 4 0 0 1-1-1Z"/>',
        'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4l2-2Z"/><path d="M10 21h4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>',
        'edit' => '<path d="M4 20h4L19 9a2.800 2.800 0 0 0-4-4L4 16v4Z"/><path d="M13.500 6.500l4 4"/>',
        'chart' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-8"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.400 15a1.700 1.700 0 0 0 .3 1.800l.1.100a2 2 0 1 1-2.800 2.800l-.1-.1a1.700 1.700 0 0 0-1.800-.3 1.700 1.700 0 0 0-1 1.500V21a2 2 0 1 1-4 0v-.1a1.700 1.700 0 0 0-1.100-1.500 1.700 1.700 0 0 0-1.800.3l-.1.1a2 2 0 1 1-2.800-2.800l.1-.1a1.700 1.700 0 0 0 .3-1.800 1.700 1.700 0 0 0-1.500-1H3a2 2 0 1 1 0-4h.1a1.700 1.700 0 0 0 1.500-1.100 1.700 1.700 0 0 0-.3-1.800l-.1-.1a2 2 0 1 1 2.800-2.800l.1.100a1.700 1.700 0 0 0 1.800.3H9a1.700 1.700 0 0 0 1-1.500V3a2 2 0 1 1 4 0v.1a1.700 1.700 0 0 0 1 1.500 1.700 1.700 0 0 0 1.800-.3l.1-.1a2 2 0 1 1 2.800 2.800l-.1.100a1.700 1.700 0 0 0-.3 1.800V9a1.700 1.700 0 0 0 1.500 1H21a2 2 0 1 1 0 4h-.1a1.700 1.700 0 0 0-1.500 1Z"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.500"/><path d="M21 16l-5-5-8 8"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.500" cy="8.500" r="1"/>',
        'logout' => '<path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5"/><path d="M16 8l4 4-4 4M20 12H9"/>',
        'eye' => '<path d="M2 12s3.500-7 10-7 10 7 10 7-3.500 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'filter' => '<path d="M3 5h18l-7 8v6l-4-2v-4L3 5Z"/>',
        'moon' => '<path d="M20 14.500A8 8 0 0 1 9.500 4 8 8 0 1 0 20 14.500Z"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5H5V6h5"/>',
        'download' => '<path d="M12 4v11M7 11l5 5 5-5M4 20h16"/>',
        'upload' => '<path d="M12 16V5M7 9l5-5 5 5M4 20h16"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'book' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Z"/><path d="M19 19v2H6"/>',
        'lightbulb' => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.500 10.900c.700.600 1 1.300 1 2.100h5c0-.800.300-1.500 1-2.100A6 6 0 0 0 12 3Z"/>',
        'alert' => '<path d="M12 3l10 17H2L12 3Z"/><path d="M12 10v5M12 18h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4V8Z"/><circle cx="12" cy="13" r="3.500"/>',
        'list' => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'wind' => '<path d="M3 8h11a3 3 0 1 0-3-3M3 12h16a3 3 0 1 1-3 3M3 16h8"/>',
        'thermometer' => '<path d="M10 14V5a2 2 0 0 1 4 0v9a4 4 0 1 1-4 0Z"/>',
        'paw' => '<circle cx="6" cy="10" r="2"/><circle cx="18" cy="10" r="2"/><circle cx="10" cy="5.500" r="2"/><circle cx="14" cy="5.500" r="2"/><path d="M12 12c-3 0-5.500 3-5.500 5.500A2.500 2.500 0 0 0 9 20c1 0 1.800-.5 3-.5s2 .5 3 .5a2.500 2.500 0 0 0 2.500-2.500C17.500 15 15 12 12 12Z"/>',
    ];

    public static function get(string $name): string
    {
        return self::SET[$name] ?? self::SET['leaf'];
    }

    public static function has(string $name): bool
    {
        return isset(self::SET[$name]);
    }

    public static function names(): array
    {
        return array_keys(self::SET);
    }
}
