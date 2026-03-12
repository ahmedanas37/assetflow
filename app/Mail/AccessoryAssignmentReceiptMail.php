<?php

namespace App\Mail;

use App\Domain\Accessories\Models\AccessoryAssignment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccessoryAssignmentReceiptMail extends Mailable
{
    public function __construct(public AccessoryAssignment $assignment) {}

    public function envelope(): Envelope
    {
        $name = $this->assignment->accessory?->name ?? 'Accessory';

        return new Envelope(
            subject: "Accessory Issuance Receipt: {$name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.accessory-assignment-receipt',
            with: [
                'assignment' => $this->assignment,
            ],
        );
    }
}
