<?php

if (! function_exists('sgJobIcon')) {
    function sgJobIcon(string $title): string
    {
        $t = strtolower($title);
        if (str_contains($t, 'perawat') || str_contains($t, 'nurse')) return 'fa-user-nurse';
        if (str_contains($t, 'bidan'))                                  return 'fa-baby';
        if (str_contains($t, 'apoteker') || str_contains($t, 'farmasi')) return 'fa-prescription-bottle-medical';
        if (str_contains($t, 'admin') || str_contains($t, 'rekam'))    return 'fa-folder';
        if (str_contains($t, 'hr') || str_contains($t, 'rekrut'))      return 'fa-handshake';
        if (str_contains($t, 'marketing'))                              return 'fa-chart-line';
        if (str_contains($t, 'it') || str_contains($t, 'teknologi'))   return 'fa-laptop-code';
        if (str_contains($t, 'akunt') || str_contains($t, 'pajak'))    return 'fa-calculator';
        return 'fa-briefcase';
    }
}
