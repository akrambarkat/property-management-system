<?php

namespace App\Support;

class ReportLabels
{
    private const CATEGORY_LABELS = [
        'maintenance' => 'صيانة وتصليحات',
        'electricity' => 'كهرباء خدمات',
        'water' => 'مياه خدمات',
        'cleaning' => 'نظافة وتدبير',
        'security' => 'حراسة وأمن',
        'admin' => 'إدارية وعمومية',
        'other' => 'أخرى',
    ];

    public static function category(string $key): string
    {
        return self::CATEGORY_LABELS[$key] ?? $key;
    }
}
