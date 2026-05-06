<?php

namespace App\Domain\Audits\Services;

use App\Domain\Audits\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogger
{
    public static function log(
        Model $entity,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
    ): ?AuditLog {
        $context = self::context();
        $actor ??= self::actorFromEntity($entity);

        $oldValues = self::filterValues($oldValues);
        $newValues = self::filterValues($newValues);

        return AuditLog::create([
            'actor_user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip' => $context['ip'],
            'user_agent' => $context['user_agent'],
            'created_at' => now(),
        ]);
    }

    public static function logCreated(Model $entity): void
    {
        self::log($entity, 'created', [], $entity->getAttributes());
    }

    public static function logUpdated(Model $entity): void
    {
        $changes = $entity->getChanges();
        $original = Arr::only($entity->getOriginal(), array_keys($changes));

        self::log($entity, 'updated', $original, $changes);
    }

    public static function logDeleted(Model $entity): void
    {
        self::log($entity, 'deleted', $entity->getOriginal(), []);
    }

    public static function logRestored(Model $entity): void
    {
        self::log($entity, 'restored', [], $entity->getAttributes());
    }

    /**
     * @return array{ip: string|null, user_agent: string|null}
     */
    private static function context(): array
    {
        if (! app()->bound('request')) {
            return ['ip' => null, 'user_agent' => null];
        }

        $request = request();

        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function filterValues(array $values): array
    {
        return Arr::except($values, [
            'updated_at',
            'created_at',
            'deleted_at',
            'acceptance_token',
            'acceptance_token_hash',
        ]);
    }

    private static function actorFromEntity(Model $entity): ?User
    {
        if (! method_exists($entity, 'auditActor')) {
            return null;
        }

        $actor = $entity->auditActor();

        return $actor instanceof User ? $actor : null;
    }
}
