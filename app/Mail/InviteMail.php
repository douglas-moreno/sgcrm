<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invite $invite) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to '.($this->invite->company?->name ?? 'sgCrm'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invite',
            with: [
                'invite' => $this->invite,
                'url' => route('invites.accept', ['token' => $this->invite->token]),
            ],
        );
    }
}
