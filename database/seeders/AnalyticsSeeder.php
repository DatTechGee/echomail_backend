<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'demo@echomail.com')->first()
            ?? User::where('email', 'admin@admin.com')->first();

        if (!$admin) {
            $this->command->warn('No admin user found. Run AdminUserSeeder first.');
            return;
        }

        $this->createRichAnalyticsCampaigns($admin);
        $this->command->info('Analytics seed data created successfully.');
    }

    private function createRichAnalyticsCampaigns(User $admin): void
    {
        $campaigns = [
            [
                'name' => 'Product Hunt Launch',
                'subject' => 'We just launched on Product Hunt!',
                'sent_days_ago' => 45,
                'total_sent' => 25,
                'opens' => 20,
                'clicks' => 14,
                'status' => 'sent',
            ],
            [
                'name' => 'Monthly Newsletter #1',
                'subject' => 'Your January newsletter, {{first_name}}',
                'sent_days_ago' => 38,
                'total_sent' => 22,
                'opens' => 15,
                'clicks' => 8,
                'status' => 'sent',
            ],
            [
                'name' => 'Feature Announcement: A/B Testing',
                'subject' => 'New feature: A/B test your emails',
                'sent_days_ago' => 30,
                'total_sent' => 30,
                'opens' => 24,
                'clicks' => 18,
                'status' => 'sent',
            ],
            [
                'name' => 'Valentine\'s Day Promotion',
                'subject' => 'Love your inbox - 30% off all plans',
                'sent_days_ago' => 22,
                'total_sent' => 28,
                'opens' => 18,
                'clicks' => 12,
                'status' => 'sent',
            ],
            [
                'name' => 'Monthly Newsletter #2',
                'subject' => 'Your February digest, {{first_name}}',
                'sent_days_ago' => 15,
                'total_sent' => 26,
                'opens' => 19,
                'clicks' => 10,
                'status' => 'sent',
            ],
            [
                'name' => 'Automation Workflows Launch',
                'subject' => 'Introducing drip campaigns and automations',
                'sent_days_ago' => 10,
                'total_sent' => 32,
                'opens' => 27,
                'clicks' => 20,
                'status' => 'sent',
            ],
            [
                'name' => 'User Feedback Survey',
                'subject' => 'Help us improve EchoMail, {{first_name}}',
                'sent_days_ago' => 7,
                'total_sent' => 20,
                'opens' => 14,
                'clicks' => 9,
                'status' => 'sent',
            ],
            [
                'name' => 'March Newsletter Draft',
                'subject' => 'What\'s coming in March',
                'sent_days_ago' => 0,
                'total_sent' => 0,
                'opens' => 0,
                'clicks' => 0,
                'status' => 'draft',
            ],
            [
                'name' => 'Easter Flash Sale',
                'subject' => 'Easter special: 50% off for 48 hours',
                'sent_days_ago' => 0,
                'total_sent' => 0,
                'opens' => 0,
                'clicks' => 0,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(3),
            ],
            [
                'name' => 'SMTP Configuration Test',
                'subject' => 'Test: SMTP settings verification',
                'sent_days_ago' => 5,
                'total_sent' => 3,
                'opens' => 0,
                'clicks' => 0,
                'status' => 'failed',
                'error_message' => 'SMTP Error: Connection refused on port 587',
            ],
        ];

        $emails = [
            'sarah.johnson@outlook.com', 'michael.chen@gmail.com', 'emma.williams@yahoo.com',
            'james.brown@company.co', 'olivia.davis@proton.me', 'liam.martinez@icloud.com',
            'sophia.garcia@gmail.com', 'noah.rodriguez@hotmail.com', 'ava.anderson@gmail.com',
            'ethan.taylor@outlook.com', 'isabella.thomas@yahoo.com', 'alex.jackson@enterprise.com',
            'charlotte.white@corp.net', 'daniel.harris@beta.io', 'mia.clark@startup.co',
            'william.lewis@oldmail.com', 'grace.walker@ghost.com', 'henry.hall@stale.org',
            'sarah.johnson@outlook.com', 'michael.chen@gmail.com', 'olivia.davis@proton.me',
            'liam.martinez@icloud.com', 'sophia.garcia@gmail.com', 'ava.anderson@gmail.com',
            'ethan.taylor@outlook.com', 'daniel.harris@beta.io', 'alex.jackson@enterprise.com',
            'charlotte.white@corp.net', 'emma.williams@yahoo.com', 'james.brown@company.co',
            'noah.rodriguez@hotmail.com', 'mia.clark@startup.co',
        ];

        foreach ($campaigns as $data) {
            $sentAt = $data['sent_days_ago'] > 0 ? now()->subDays($data['sent_days_ago']) : null;
            $createdAt = $sentAt ? $sentAt->copy()->subHours(rand(1, 48)) : now();

            $campaign = Campaign::updateOrCreate(
                ['user_id' => $admin->id, 'name' => $data['name']],
                [
                    'uuid' => Str::uuid(),
                    'created_by' => $admin->id,
                    'subject' => $data['subject'],
                    'content' => $this->buildContent($data['name']),
                    'html_content' => null,
                    'status' => $data['status'],
                    'recipient_config' => ['type' => 'all'],
                    'total_recipients' => $data['total_sent'],
                    'total_sent' => $data['total_sent'],
                    'total_failed' => $data['status'] === 'failed' ? $data['total_sent'] : 0,
                    'opens' => $data['opens'],
                    'clicks' => $data['clicks'],
                    'open_rate' => $data['total_sent'] > 0 ? round(($data['opens'] / $data['total_sent']) * 100, 2) : 0,
                    'click_rate' => $data['total_sent'] > 0 ? round(($data['clicks'] / $data['total_sent']) * 100, 2) : 0,
                    'sent_at' => $sentAt,
                    'error_message' => $data['error_message'] ?? null,
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'frequency' => null,
                ]
            );

            // Create recipients for sent/failed campaigns
            if ($data['status'] === 'sent' || $data['status'] === 'failed') {
                $recipientEmails = array_slice($emails, 0, $data['total_sent']);
                $openedOffset = $data['total_sent'] - $data['opens'];
                $clickedOffset = $data['total_sent'] - $data['clicks'];

                foreach ($recipientEmails as $i => $email) {
                    $opened = $i >= $openedOffset;
                    $clicked = $opened && $i >= $clickedOffset;

                    CampaignRecipient::updateOrCreate(
                        ['campaign_id' => $campaign->id, 'email' => $email],
                        [
                            'token' => Str::random(32),
                            'status' => $data['status'] === 'failed' ? 'failed' : 'sent',
                            'opened_at' => $opened && $sentAt ? $sentAt->copy()->addMinutes(rand(5, 720)) : null,
                            'clicked_at' => $clicked && $sentAt ? $sentAt->copy()->addMinutes(rand(10, 1440)) : null,
                            'error_message' => $data['status'] === 'failed' ? 'SMTP Error: Connection refused' : null,
                        ]
                    );
                }
            }
        }
    }

    private function buildContent(string $name): string
    {
        return json_encode([
            [
                'id' => (string) Str::uuid(),
                'type' => 'heading',
                'props' => ['level' => 2],
                'content' => [['type' => 'text', 'text' => $name, 'styles' => []]],
                'children' => [],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'paragraph',
                'props' => ['textAlignment' => 'left'],
                'content' => [
                    ['type' => 'text', 'text' => 'Hi {{first_name}}, ', 'styles' => []],
                    ['type' => 'text', 'text' => 'thank you for being part of the EchoMail community.', 'styles' => []],
                ],
                'children' => [],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'paragraph',
                'props' => ['textAlignment' => 'left'],
                'content' => [['type' => 'text', 'text' => 'We have some exciting updates to share with you this month. Stay tuned for more features and improvements.', 'styles' => []]],
                'children' => [],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
