<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $invitationUrl,
        public readonly int $expiresInMinutes,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('Defina sua senha inicial')
            ->view('emails.user-invitation');
    }
}
