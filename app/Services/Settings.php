<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Akses terpusat ke pengaturan aplikasi (tabel settings, di-cache).
 * Semua get() punya nilai default sehingga aman walau tabel/seed belum ada.
 */
class Settings
{
    private const CACHE_KEY = 'app-settings';

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'company_name' => 'Gas Cylinder Monitoring System',
            'company_address' => '',
            'company_phone' => '',
            'maintenance_types' => implode("\n", [
                'Pemeriksaan rutin',
                'Perbaikan valve',
                'Pemeriksaan kebocoran',
                'Pemeriksaan kondisi tabung',
                'Pengujian',
                'Lainnya',
            ]),
            'per_page' => '10',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $defaults = self::defaults();

        try {
            if (! Schema::hasTable('settings')) {
                return $defaults;
            }

            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());
        } catch (\Throwable) {
            return $defaults;
        }

        foreach ($stored as $key => $value) {
            if (array_key_exists($key, $defaults) && $value !== null) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();

        return $all[$key] ?? $default ?? self::defaults()[$key] ?? null;
    }

    /**
     * @param  array<string, ?string>  $values
     */
    public static function setMany(array $values, string $group = 'general'): void
    {
        foreach (self::defaults() as $key => $default) {
            if (! array_key_exists($key, $values)) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], [
                'value' => $values[$key],
                'group' => $group,
            ]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    public static function maintenanceTypes(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) self::get('maintenance_types', ''));
        $types = array_values(array_filter(array_map('trim', $lines ?: [])));

        return $types !== [] ? $types : ['Lainnya'];
    }

    public static function perPage(): int
    {
        $allowed = [10, 15, 20, 25, 50];
        $perPage = (int) self::get('per_page', '10');

        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }

    /**
     * @return array{name: string, address: string, phone: string}
     */
    public static function company(): array
    {
        return [
            'name' => (string) self::get('company_name', ''),
            'address' => (string) self::get('company_address', ''),
            'phone' => (string) self::get('company_phone', ''),
        ];
    }
}
