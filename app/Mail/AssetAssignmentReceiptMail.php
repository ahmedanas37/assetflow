<?php

namespace App\Mail;

use App\Domain\Assets\Models\AssetAssignment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AssetAssignmentReceiptMail extends Mailable
{
    public function __construct(public AssetAssignment $assignment) {}

    public function envelope(): Envelope
    {
        $assetTag = $this->assignment->asset?->asset_tag ?? 'Asset';

        return new Envelope(
            subject: "Asset Issuance Receipt: {$assetTag}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.asset-assignment-receipt',
            with: [
                'assignment' => $this->assignment,
            ],
        );
    }
}
