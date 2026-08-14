<?php

namespace App\Mail;

use App\Helper\BlockNoteParser;
use App\Models\Campaign;
use App\Models\NewsletterSubscriber;
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
    public $trackingPixelUrl;
    public $unsubscribeUrl;
    public $recipientEmail;
    public $personalization = [];

    public function __construct(Campaign $campaign, string $email, string $token, array $personalization = [])
    {
        $this->campaign = $campaign;
        $this->recipientEmail = $email;
        $this->personalization = $personalization;

        $html = BlockNoteParser::parse($campaign->content);
        $html = $this->applyPersonalization($html, $personalization);
        $html = $this->wrapLinks($html, $token);

        $this->htmlContent = $html;
        $this->trackingPixelUrl = url("/campaigns/{$campaign->uuid}/open/{$token}");

        $subscriber = NewsletterSubscriber::where('email', $email)->first();
        $this->unsubscribeUrl = $subscriber && $subscriber->unsubscribe_token
            ? url('/unsubscribe/' . $subscriber->unsubscribe_token)
            : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->applyPersonalization($this->campaign->subject, $this->personalization ?? []),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            with: [
                'campaign' => $this->campaign,
                'htmlContent' => $this->htmlContent,
                'trackingPixelUrl' => $this->trackingPixelUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function applyPersonalization(string $text, array $personalization): string
    {
        $placeholders = [
            '{{first_name}}' => $personalization['first_name'] ?? '',
            '{{last_name}}' => $personalization['last_name'] ?? '',
            '{{full_name}}' => $personalization['full_name'] ?? '',
            '{{email}}' => $personalization['email'] ?? $this->recipientEmail,
        ];

        return strtr($text, $placeholders);
    }

    private function wrapLinks(string $html, string $token): string
    {
        return preg_replace_callback(
            '/<a\s+href=(["\'])(.*?)\1/i',
            function ($matches) use ($token) {
                $quote = $matches[1];
                $url = $matches[2];

                if (str_starts_with($url, '#') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
                    return $matches[0];
                }

                $trackingUrl = url("/campaigns/{$this->campaign->uuid}/click/{$token}?url=" . urlencode($url));

                return "<a href={$quote}{$trackingUrl}{$quote}";
            },
            $html
        );
    }
}
