<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    public function __construct(public User $user, public string $token) {}

    public function url(): string
    {
        return route('password.reset', ['token' => $this->token, 'email' => $this->user->email]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Reset your password').' · '.setting('store.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.reset-password', with: ['user' => $this->user, 'url' => $this->url(), 'minutes' => (int) config('auth.passwords.users.expire', 60)]);
    }
}
