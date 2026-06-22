<?php

// ═══════════════════════════════════════════════════════════════════════════
// app/helpers.php
//
// Autoloaded by Composer on every request — always available in Blade,
// controllers, Artisan commands, and middleware.
//
// Registered in composer.json under:
//   "autoload": { "files": ["app/helpers.php"] }
//
// After adding to composer.json, run:  composer dump-autoload
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('fmt_amount')) {
    /**
     * Format a monetary amount for display, applying scale if enabled.
     *
     * When scale_amounts = OFF:  145000 → 145000      (plain integer, no formatting)
     * When scale_amounts = ON:   145000 → ₹1,450.00   (÷ 100, 2 decimal places)
     *
     * The stored database value is NEVER changed — display only.
     *
     * Usage in Blade:
     *   {{ fmt_amount($value) }}         → 145000  or  ₹1,450.00
     *   {{ fmt_amount($value, false) }}  → 145000  or  1,450.00  (no ₹ symbol)
     */
    function fmt_amount($amount, bool $symbol = true): string
    {
        $val = (float) ($amount ?? 0);

        if (config('app.scale_amounts', false)) {
            // Scale ON: divide by 100, show with ₹ and 2 decimal places
            $formatted = number_format($val / 100, 2);
            return $symbol ? '₹' . $formatted : $formatted;
        }

        // Scale OFF: ₹ symbol + comma thousands separator, no decimals
        // e.g. 145000 → ₹145,000   |   6152092 → ₹6,152,092
        $formatted = number_format($val, 0);
        return $symbol ? '₹' . $formatted : $formatted;
    }
}

if (! function_exists('raw_amount')) {
    /**
     * Always show the raw stored value — never scaled.
     * Use in entry forms and edit pages where user sees/types real values.
     *
     * Usage:  {{ raw_amount($value) }}        → 145000
     *         {{ raw_amount($value, true) }}   → ₹145000
     */
    function raw_amount($amount, bool $symbol = false): string
    {
        $formatted = number_format((float) ($amount ?? 0), 0);
        return $symbol ? '₹' . $formatted : $formatted;
    }
}

if (! function_exists('scale_divisor')) {
    /**
     * Returns the active divisor as an integer for use in JavaScript.
     *
     * Usage in Blade inside <script>:
     *   const DIVISOR = {{ scale_divisor() }};
     *   const display = rawValue / DIVISOR;
     *
     * Returns 100 when scale is ON, 1 when OFF.
     */
    function scale_divisor(): int
    {
        return config('app.scale_amounts', false) ? 100 : 1;
    }
}
