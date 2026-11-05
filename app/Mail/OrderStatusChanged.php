<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderStatusChanged extends Mailable
{
    public function __construct(public Order $order, public ?string $note = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Order :number: :status', ['number' => $this->order->number, 'status' => $this->order->statusLabel()]).' · '.setting('store.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-status', with: ['order' => $this->order, 'note' => $this->note]);
    }
}
