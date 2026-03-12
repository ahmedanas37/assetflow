<?php

namespace App\Mail;

use App\Domain\Accessories\Models\AccessoryAssignment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccessoryReturnConfirmationMail extends Mailable
{
    public function __construct(public AccessoryAssignment $assignment) {}

    public function envelope(): Envelope
    {
        $name = $this->assignment->accessory?->name ?? 'Accessory';

        return new Envelope(
            subject: "Accessory Return Confirmation: {$name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.accessory-return-confirmation',
            with: [
                'assignment' => $this->assignment,
            ],
        );
    }
}
