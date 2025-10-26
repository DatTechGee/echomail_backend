<?php

namespace App\Mail;

use App\Helper\BlockNoteParser;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $htmlContent;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->htmlContent = BlockNoteParser::parse($campaign->content);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            with: [
                'campaign' => $this->campaign,
                'htmlContent' => $this->htmlContent,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
