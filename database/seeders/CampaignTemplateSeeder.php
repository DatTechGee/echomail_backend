<?php

namespace Database\Seeders;

use App\Models\CampaignTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampaignTemplateSeeder extends Seeder
{
    /**
     * Seed the application with starter email templates that showcase
     * the available personalization codes.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('No user found. Skipping template seeding.');
            return;
        }

        $templates = [
            [
                'name' => 'Welcome Email',
                'subject' => 'Welcome to EchoMail, {{first_name}}!',
                'content' => $this->blocks([
                    $this->heading("Welcome aboard, {{first_name}}!"),
                    $this->paragraph("Hi {{full_name}}, we're thrilled to have you with us. Your account is ready and we can't wait to see what you'll create."),
                    $this->bullets([
                        'Set up your profile and preferences',
                        'Create your first campaign in minutes',
                        'Track opens and clicks in real time',
                    ]),
                    $this->paragraph("If you ever need a hand, just reply to this email at {{email}} and our team will jump in."),
                ]),
            ],
            [
                'name' => 'Weekly Newsletter',
                'subject' => 'Your weekly digest, {{first_name}}',
                'content' => $this->blocks([
                    $this->heading("This Week's Highlights"),
                    $this->paragraph("Hello {{first_name}}, here is what happened this week that you shouldn't miss."),
                    $this->bullets([
                        'New feature: scheduled campaigns are now live',
                        'Reusable templates make sending faster',
                        'Open and click tracking is built in',
                    ]),
                    $this->paragraph("Thanks for reading, {{full_name}}. See you next week!"),
                ]),
            ],
            [
                'name' => 'Promotional / Sale',
                'subject' => 'Exclusive offer just for you, {{first_name}} 🎉',
                'content' => $this->blocks([
                    $this->heading("Limited Time Offer", 1),
                    $this->paragraph("Hey {{first_name}}, as one of our most valued members we want to give you something special."),
                    $this->paragraph("Use code ", $this->styled('ECHOMAIL20', ['bold' => true, 'code' => true]), " at checkout to save 20% on your next order."),
                    $this->paragraph("This offer is one-time only — grab it before it's gone, {{full_name}}!"),
                ]),
            ],
            [
                'name' => 'Event Invitation',
                'subject' => "You're invited, {{first_name}}!",
                'content' => $this->blocks([
                    $this->heading("You're Invited!"),
                    $this->paragraph("Dear {{full_name}}, we would love to see you at our upcoming event."),
                    $this->bullets([
                        'Date: Friday, 10:00 AM',
                        'Where: Online — link sent after RSVP',
                        'Duration: 45 minutes',
                    ]),
                    $this->paragraph("RSVP by replying to {{email}} and we'll save you a spot."),
                ]),
            ],
            [
                'name' => 'Product Update',
                'subject' => 'What\u2019s new for {{first_name}}',
                'content' => $this->blocks([
                    $this->heading("Product Update"),
                    $this->paragraph("Hi {{first_name}}, here's what we shipped this month."),
                    $this->bullets([
                        'Faster sending with the new queue system',
                        'Personalization tags in every email',
                        'A brand new template library',
                    ]),
                    $this->paragraph("We'd love your feedback, {{full_name}}. Just hit reply!"),
                ]),
            ],
            [
                'name' => 'Re-engagement',
                'subject' => 'We miss you, {{first_name}}!',
                'content' => $this->blocks([
                    $this->heading("We Miss You!"),
                    $this->paragraph("It's been a while since we last heard from {{full_name}}. We'd love to have you back."),
                    $this->paragraph("Here's a quick recap of everything new since your last visit."),
                    $this->bullets([
                        'Campaign scheduling and recurring sends',
                        'Drag-and-drop editor with templates',
                        'Detailed open and click analytics',
                    ]),
                    $this->paragraph("Questions? Reach us anytime at {{email}}."),
                ]),
            ],
            [
                'name' => 'Personalization Guide',
                'subject' => 'How to personalize your emails',
                'content' => $this->blocks([
                    $this->heading("Personalization Codes"),
                    $this->paragraph("Use these codes anywhere in your email content and they will be replaced with each recipient's data when the email is sent."),
                    $this->bullets([
                        '{{first_name}} — the recipient\u2019s first name, e.g. Sarah',
                        '{{last_name}} — the recipient\u2019s last name, e.g. Johnson',
                        '{{full_name}} — the recipient\u2019s full name, e.g. Sarah Johnson',
                        '{{email}} — the recipient\u2019s email address, e.g. sarah@example.com',
                    ]),
                    $this->paragraph("Example: ", $this->styled('"Hi {{first_name}}, thanks for subscribing!"', ['code' => true]), " becomes \"Hi Sarah, thanks for subscribing!\""),
                ]),
            ],
        ];

        foreach ($templates as $template) {
            CampaignTemplate::updateOrCreate(
                ['user_id' => $user->id, 'name' => $template['name']],
                [
                    'subject' => $template['subject'],
                    'content' => $template['content'],
                ]
            );
        }

        $this->command->info('Campaign templates seeded successfully.');
    }

    /**
     * Wrap a set of blocks into a valid BlockNote document array.
     */
    private function blocks(array $blocks): string
    {
        $flat = [];

        foreach ($blocks as $block) {
            if (is_array($block) && isset($block['type'])) {
                $flat[] = $block;
            } elseif (is_array($block)) {
                $flat = array_merge($flat, $block);
            }
        }

        return json_encode($flat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function heading(string $text, int $level = 2): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'heading',
            'props' => ['level' => $level],
            'content' => $this->inline($text),
            'children' => [],
        ];
    }

    private function paragraph(mixed ...$parts): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'paragraph',
            'props' => ['textAlignment' => 'left'],
            'content' => $this->inline(...$parts),
            'children' => [],
        ];
    }

    private function bullets(array $items): array
    {
        return array_map(fn (string $item) => [
            'id' => (string) Str::uuid(),
            'type' => 'bulletListItem',
            'props' => ['checked' => false],
            'content' => $this->inline($item),
            'children' => [],
        ], $items);
    }

    private function inline(mixed ...$parts): array
    {
        if (count($parts) === 1) {
            return [[
                'type' => 'text',
                'text' => $parts[0],
                'styles' => [],
            ]];
        }

        return array_map(fn ($part) => is_array($part) ? $part : [
            'type' => 'text',
            'text' => $part,
            'styles' => [],
        ], $parts);
    }

    private function styled(string $text, array $styles): array
    {
        return [
            'type' => 'text',
            'text' => $text,
            'styles' => $styles,
        ];
    }
}
