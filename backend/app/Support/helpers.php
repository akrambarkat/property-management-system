<?php

use App\Services\SettingsService;

if (!function_exists('settings')) {
    /**
     * Read a setting value (optionally scoped to a group).
     */
    function settings(string $key, mixed $default = null, ?string $group = null): mixed
    {
        return app(SettingsService::class)->get($key, $default, $group);
    }
}

if (!function_exists('settings_group')) {
    /**
     * Read an entire group of settings as an associative array.
     */
    function settings_group(string $group): array
    {
        return app(SettingsService::class)->group($group);
    }
}
