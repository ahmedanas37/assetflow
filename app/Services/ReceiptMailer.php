<?php

namespace App\Services;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\AssetAssignment;
use App\Mail\AccessoryAssignmentReceiptMail;
use App\Mail\AccessoryReturnConfirmationMail;
use App\Mail\AssetAssignmentReceiptMail;
use App\Mail\AssetReturnConfirmationMail;
use Illuminate\Support\Facades\Mail;

class ReceiptMailer
{
    public function sendAssetAssignmentReceipt(AssetAssignment $assignment): void
    {
        if (! $this->isEnabled() || ! $this->isReceiptEnabled()) {
            return;
        }

        $assignment->loadMissing([
            'asset.assetModel',
            'asset.category',
            'asset.statusLabel',
            'assignedBy',
            'assignedToUser',
            'assignedToEmployee',
            'assignedToLocation',
        ]);

        $to = $this->resolveAssetRecipientEmail($assignment);
        $actorEmail = $assignment->assignedBy?->email;

        if (! $to && $this->shouldFallbackToActor() && $actorEmail) {
            $to = $actorEmail;
        }

        if (! $to) {
            return;
        }

        $mailer = Mail::to($to);
        $cc = $this->buildCcList($to, $actorEmail);
        if (! empty($cc)) {
            $mailer->cc($cc);
        }

        $bcc = $this->bccList();
        if (! empty($bcc)) {
            $mailer->bcc($bcc);
        }

        try {
            $mailer->send(new AssetAssignmentReceiptMail($assignment));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function sendAccessoryAssignmentReceipt(AccessoryAssignment $assignment): void
    {
        if (! $this->isEnabled() || ! $this->isReceiptEnabled()) {
            return;
        }

        $assignment->loadMissing([
            'accessory.category',
            'accessory.manufacturer',
            'accessory.location',
            'assignedBy',
            'assignedToUser',
            'assignedToEmployee',
            'assignedToLocation',
        ]);

        $to = $this->resolveAccessoryRecipientEmail($assignment);
        $actorEmail = $assignment->assignedBy?->email;

        if (! $to && $this->shouldFallbackToActor() && $actorEmail) {
            $to = $actorEmail;
        }

        if (! $to) {
            return;
        }

        $mailer = Mail::to($to);
        $cc = $this->buildCcList($to, $actorEmail);
        if (! empty($cc)) {
            $mailer->cc($cc);
        }

        $bcc = $this->bccList();
        if (! empty($bcc)) {
            $mailer->bcc($bcc);
        }

        try {
            $mailer->send(new AccessoryAssignmentReceiptMail($assignment));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function sendAssetReturnConfirmation(AssetAssignment $assignment): void
    {
        if (! $this->isEnabled() || ! $this->isReturnEnabled()) {
            return;
        }

        $assignment->loadMissing([
            'asset.assetModel',
            'asset.category',
            'asset.statusLabel',
            'assignedBy',
            'assignedToUser',
            'assignedToEmployee',
            'assignedToLocation',
        ]);

        $to = $this->resolveAssetRecipientEmail($assignment);
        $actorEmail = $assignment->assignedBy?->email;

        if (! $to && $this->shouldFallbackToActor() && $actorEmail) {
            $to = $actorEmail;
        }

        if (! $to) {
            return;
        }

        $mailer = Mail::to($to);
        $cc = $this->buildCcList($to, $actorEmail);
        if (! empty($cc)) {
            $mailer->cc($cc);
        }

        $bcc = $this->bccList();
        if (! empty($bcc)) {
            $mailer->bcc($bcc);
        }

        try {
            $mailer->send(new AssetReturnConfirmationMail($assignment));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function sendAccessoryReturnConfirmation(AccessoryAssignment $assignment): void
    {
        if (! $this->isEnabled() || ! $this->isReturnEnabled()) {
            return;
        }

        $assignment->loadMissing([
            'accessory.category',
            'accessory.manufacturer',
            'accessory.location',
            'assignedBy',
            'assignedToUser',
            'assignedToEmployee',
            'assignedToLocation',
        ]);

        $to = $this->resolveAccessoryRecipientEmail($assignment);
        $actorEmail = $assignment->assignedBy?->email;

        if (! $to && $this->shouldFallbackToActor() && $actorEmail) {
            $to = $actorEmail;
        }

        if (! $to) {
            return;
        }

        $mailer = Mail::to($to);
        $cc = $this->buildCcList($to, $actorEmail);
        if (! empty($cc)) {
            $mailer->cc($cc);
        }

        $bcc = $this->bccList();
        if (! empty($bcc)) {
            $mailer->bcc($bcc);
        }

        try {
            $mailer->send(new AccessoryReturnConfirmationMail($assignment));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function resolveAssetRecipientEmail(AssetAssignment $assignment): ?string
    {
        return match ($assignment->assigned_to_type) {
            AssignmentType::User => $assignment->assignedToUser?->email,
            AssignmentType::Employee => $assignment->assignedToEmployee?->email,
            default => null,
        };
    }

    private function resolveAccessoryRecipientEmail(AccessoryAssignment $assignment): ?string
    {
        return match ($assignment->assigned_to_type) {
            AssignmentType::User => $assignment->assignedToUser?->email,
            AssignmentType::Employee => $assignment->assignedToEmployee?->email,
            default => null,
        };
    }

    private function isEnabled(): bool
    {
        return app(PortalSettings::class)->getBool('emails.enabled', (bool) config('assetflow.receipts.email_enabled'));
    }

    private function shouldCcActor(): bool
    {
        return app(PortalSettings::class)->getBool('emails.cc_actor', (bool) config('assetflow.receipts.cc_actor'));
    }

    private function shouldFallbackToActor(): bool
    {
        return app(PortalSettings::class)->getBool('emails.fallback_to_actor', (bool) config('assetflow.receipts.fallback_to_actor'));
    }

    private function isReturnEnabled(): bool
    {
        return app(PortalSettings::class)->getBool('emails.returns.enabled', (bool) config('assetflow.receipts.return_email_enabled'));
    }

    private function isReceiptEnabled(): bool
    {
        return app(PortalSettings::class)->getBool('emails.receipts.enabled', true);
    }

    /** @return array<int, string> */
    private function ccList(): array
    {
        $settings = app(PortalSettings::class);
        $cc = $settings->getList('emails.cc', []);

        return array_values(array_filter($cc, fn ($value) => is_string($value) && trim($value) !== ''));
    }

    /** @return array<int, string> */
    private function bccList(): array
    {
        $settings = app(PortalSettings::class);
        $bcc = $settings->getList('emails.bcc', []);

        if (! empty($bcc)) {
            return $bcc;
        }

        $bcc = config('assetflow.receipts.bcc');

        if (is_array($bcc)) {
            return array_values(array_filter($bcc, fn ($value) => is_string($value) && trim($value) !== ''));
        }

        return [];
    }

    /** @return array<int, string> */
    private function buildCcList(string $to, ?string $actorEmail): array
    {
        $cc = $this->ccList();

        if ($this->shouldCcActor() && $actorEmail) {
            $cc[] = $actorEmail;
        }

        $cc = array_filter($cc, fn ($email) => is_string($email) && trim($email) !== '' && $email !== $to);

        return array_values(array_unique($cc));
    }
}
