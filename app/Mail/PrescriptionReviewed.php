<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PrescriptionReviewed extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->order->prescription_status === 'approved' ? __('Prescription approved') : __('Prescription needs attention')).' · '.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.prescription-reviewed', with: ['order' => $this->order]);
    }
}
