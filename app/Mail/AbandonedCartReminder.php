<?php

namespace App\Mail;

use App\Models\AbandonedCheckout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AbandonedCartReminder extends Mailable
{
    public function __construct(public AbandonedCheckout $cart) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('You left something in your cart').' · '.setting('store.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.abandoned-cart', with: ['cart' => $this->cart]);
    }
}
