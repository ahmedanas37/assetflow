<?php

namespace App\Services;

use App\Domain\Settings\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PortalSettings
{
    private const CACHE_PREFIX = 'assetflow.portal_settings';

    /** @return array<string, string|null> */
    public function all(): array
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return [];
            }

            return Cache::remember($this->cacheKey(), 300, function (): array {
                return AppSetting::query()->pluck('value', 'key')->toArray();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, null);

        if ($value === null) {
            return $default;
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $bool ?? $default;
    }

    /** @return array<int, string> */
    public function getList(string $key, array $default = []): array
    {
        $value = $this->get($key, null);

        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($item) => $item !== ''));
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        try {
            Cache::forget($this->cacheKey());
        } catch (Throwable) {
            // Ignore cache driver errors; settings were already persisted.
        }
    }

    public function productName(): string
    {
        return (string) config('assetflow.product_name', 'AssetFlow');
    }

    public function companyName(): string
    {
        return (string) $this->get('branding.company_name', config('assetflow.company_name', 'Your Company'));
    }

    public function brandColor(): string
    {
        $color = (string) $this->get('branding.brand_color', config('assetflow.brand_color', '#1459D9'));

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
            return strtoupper($color);
        }

        return '#1459D9';
    }

    public function logoPath(): ?string
    {
        $path = (string) $this->get('branding.logo_path', '');
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        try {
            return Storage::disk('public')->exists($path) ? $path : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        if (! $path) {
            return null;
        }

        try {
            return Storage::disk('public')->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    private function cacheKey(): string
    {
        return self::CACHE_PREFIX;
    }
}
