<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPlaced extends Mailable
{
    public function __construct(public Order $order, public bool $forStore = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->forStore ? __('New order :number', ['number' => $this->order->number]) : __('Your order :number', ['number' => $this->order->number])).' · '.setting('store.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-placed', with: ['order' => $this->order->load('items'), 'forStore' => $this->forStore]);
    }
}
