<?php

if (! function_exists('rupiah')) {
    /**
     * Format angka menjadi mata uang Rupiah, mis. 12500 -> "Rp 12.500".
     */
    function rupiah(float|int|string|null $value, bool $prefix = true): string
    {
        $value = (float) $value;
        $formatted = number_format(abs($value), 0, ',', '.');

        return ($value < 0 ? '-' : '').($prefix ? 'Rp '.$formatted : $formatted);
    }
}
