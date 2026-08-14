<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WeeklySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $start;
    public $end;
    public $stats;

    public function __construct(User $user, Carbon $start, Carbon $end, array $stats)
    {
        $this->user = $user;
        $this->start = $start;
        $this->end = $end;
        $this->stats = $stats;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Weekly EchoMail Summary',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-summary',
            with: [
                'user' => $this->user,
                'start' => $this->start,
                'end' => $this->end,
                'stats' => $this->stats,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
