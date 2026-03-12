<?php

namespace App\Domain\Reports\Models;

use Illuminate\Database\Eloquent\Model;

class ReportMetric extends Model
{
    protected $fillable = ['key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function put(string $key, mixed $value): self
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $metric = static::query()->where('key', $key)->first();

        if (! $metric) {
            return $default;
        }

        $maxAgeMinutes = (int) config('assetflow.metrics_cache_minutes', 0);

        if ($maxAgeMinutes <= 0) {
            return $default;
        }

        if ($metric->updated_at && $metric->updated_at->lt(now()->subMinutes($maxAgeMinutes))) {
            return $default;
        }

        return $metric->value ?? $default;
    }
}
