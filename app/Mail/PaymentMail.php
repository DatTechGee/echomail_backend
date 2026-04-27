<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $paymentDescription,
        public string $amount,
        public string $subDescription,
        public string $datetime,
        public string $status,
        public string $statusColor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment Notification');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment',
            with: [
                'senderName'         => $this->senderName,
                'paymentDescription' => $this->paymentDescription,
                'amount'             => $this->amount,
                'subDescription'     => $this->subDescription,
                'datetime'           => $this->datetime,
                'status'             => $this->status,
                'statusColor'        => $this->statusColor,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
