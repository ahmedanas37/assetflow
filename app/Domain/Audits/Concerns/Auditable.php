<?php

namespace App\Domain\Audits\Concerns;

use App\Domain\Audits\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLogger::logCreated($model);
        });

        static::updated(function (Model $model): void {
            AuditLogger::logUpdated($model);
        });

        static::deleted(function (Model $model): void {
            AuditLogger::logDeleted($model);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                AuditLogger::logRestored($model);
            });
        }
    }
}
