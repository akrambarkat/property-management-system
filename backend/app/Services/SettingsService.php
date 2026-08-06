<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'app_settings';

    /**
     * Read all settings as a flat map, cast according to type.
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $map = [];
            Setting::all(['key', 'value', 'type'])->each(function (Setting $setting) use (&$map) {
                $map[$setting->key] = $this->cast($setting->value, $setting->type);
            });
            return $map;
        });
    }

    /**
     * Read a single setting, optionally filtered by group.
     */
    public function get(string $key, mixed $default = null, ?string $group = null): mixed
    {
        if ($group !== null) {
            $values = $this->group($group);
            return array_key_exists($key, $values) ? $values[$key] : $default;
        }

        $all = $this->all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Read a whole group as an associative array.
     */
    public function group(string $group): array
    {
        $rows = Cache::rememberForever(self::CACHE_KEY . "_group_{$group}", function () use ($group) {
            return Setting::where('group', $group)->get(['key', 'value', 'type']);
        });

        $result = [];
        foreach ($rows as $row) {
            $result[$row->key] = $this->cast($row->value, $row->type);
        }
        return $result;
    }

    /**
     * Set (or update) a single setting, storing JSON values as strings.
     */
    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $this->validateType($type);
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $this->serialize($value), 'group' => $group, 'type' => $type]
        );
        $this->flush();
    }

    /**
     * Persist a batch of settings belonging to one group. Returns the keys changed.
     */
    public function setGroup(string $group, array $values, string $type = 'string'): array
    {
        $changed = [];
        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '' || str_starts_with($key, '_')) {
                continue;
            }
            $existing = Setting::where('key', $key)->where('group', $group)->first();
            // Preserve the declared column type for existing rows (e.g. JSON
            // arrays) instead of overwriting it with the default string type.
            $rowType = $existing ? $existing->type : $this->inferType($value, $type);
            $serialized = $this->serialize($value);
            if ($existing && (string) $existing->value === (string) $serialized) {
                continue;
            }
            $changed[$key] = $value;
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $serialized, 'group' => $group, 'type' => $rowType]
            );
        }
        if ($changed) {
            $this->flush();
        }
        return $changed;
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        foreach (Setting::supportedGroups() as $group) {
            Cache::forget(self::CACHE_KEY . "_group_{$group}");
        }
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode((string) $value, true),
            default => (string) $value,
        };
    }

    private function inferType(mixed $value, string $default): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value), is_object($value) => 'json',
            default => $default,
        };
    }

    private function serialize(mixed $value): string
    {
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($value === null) return '';
        return (string) $value;
    }

    private function validateType(string $type): void
    {
        if (!in_array($type, Setting::supportedTypes(), true)) {
            throw new \InvalidArgumentException("Unsupported setting type: {$type}");
        }
    }
}
