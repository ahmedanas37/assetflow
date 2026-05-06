<?php

namespace App\Services;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Audits\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceiptAcceptanceService
{
    public function assetUrl(AssetAssignment $assignment): string
    {
        $token = $this->ensureToken($assignment);

        return route('assetflow.acceptance.asset.show', [
            'assignment' => $assignment,
            'token' => $token,
        ]);
    }

    public function accessoryUrl(AccessoryAssignment $assignment): string
    {
        $token = $this->ensureToken($assignment);

        return route('assetflow.acceptance.accessory.show', [
            'assignment' => $assignment,
            'token' => $token,
        ]);
    }

    public function isValidToken(AssetAssignment|AccessoryAssignment $assignment, string $token): bool
    {
        if (! $assignment->acceptance_token_hash || $token === '') {
            return false;
        }

        return hash_equals($assignment->acceptance_token_hash, $this->hashToken($token));
    }

    public function accept(AssetAssignment|AccessoryAssignment $assignment, string $acceptedByName, Request $request): void
    {
        if ($assignment->accepted_at) {
            return;
        }

        $oldValues = [
            'accepted_at' => null,
            'accepted_by_name' => null,
        ];

        $assignment->forceFill([
            'accepted_at' => now(),
            'accepted_by_name' => $acceptedByName,
            'accepted_ip' => $request->ip(),
            'accepted_user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ])->save();

        AuditLogger::log($this->auditEntity($assignment), 'receipt_accepted', $oldValues, [
            'assignment_type' => class_basename($assignment),
            'assignment_id' => $assignment->id,
            'accepted_at' => $assignment->accepted_at?->toDateTimeString(),
            'accepted_by_name' => $assignment->accepted_by_name,
        ]);
    }

    private function ensureToken(AssetAssignment|AccessoryAssignment $assignment): string
    {
        if ($assignment->acceptance_token && $assignment->acceptance_token_hash) {
            return (string) $assignment->acceptance_token;
        }

        return $this->issueToken($assignment);
    }

    private function issueToken(AssetAssignment|AccessoryAssignment $assignment): string
    {
        $token = Str::random(48);

        $assignment->forceFill([
            'acceptance_token' => $token,
            'acceptance_token_hash' => $this->hashToken($token),
        ])->saveQuietly();

        return $token;
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function auditEntity(AssetAssignment|AccessoryAssignment $assignment): Model
    {
        if ($assignment instanceof AssetAssignment) {
            return $assignment->asset ?? $assignment;
        }

        return $assignment->accessory ?? $assignment;
    }
}
