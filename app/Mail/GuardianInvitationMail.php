<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuardianInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $invitationToken,
        public string $participantName,
        public string $tenantName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Has sido invitado como guardian en Lighthouse',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guardian-invitation',
        );
    }
}
