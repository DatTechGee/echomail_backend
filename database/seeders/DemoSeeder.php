<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignTemplate;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private User $admin;
    private array $contactEmails = [];

    public function run(): void
    {
        $this->createAdmin();
        $this->createContactGroups();
        $this->createContacts();
        $this->createNewsletterSubscribers();
        $this->createCampaigns();
        $this->createWebhook();
        $this->createAuditLogs();
        $this->createTemplatesForDemoUser();

        $this->command->info('Demo data seeded successfully for demo@echomail.com');
    }

    private function createAdmin(): void
    {
        $this->admin = User::updateOrCreate(
            ['email' => 'demo@echomail.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'Demo',
                'last_name' => 'Account',
                'phone' => '+234 800 111 2222',
                'password' => Hash::make('Demo@12345'),
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );
    }

    private function createContactGroups(): void
    {
        $groups = [
            ['name' => 'VIP Customers', 'description' => 'High-value customers with premium subscriptions', 'color' => '#F59E0B'],
            ['name' => 'Newsletter Only', 'description' => 'Subscribers who only receive newsletters', 'color' => '#3B82F6'],
            ['name' => 'New Signups', 'description' => 'Recently registered users', 'color' => '#10B981'],
            ['name' => 'Enterprise', 'description' => 'Business and enterprise clients', 'color' => '#8B5CF6'],
            ['name' => 'Beta Testers', 'description' => 'Users opted in to beta features', 'color' => '#EC4899'],
            ['name' => 'Inactive', 'description' => 'Users who have not engaged in 30+ days', 'color' => '#6B7280'],
        ];

        foreach ($groups as $group) {
            ContactGroup::updateOrCreate(
                ['user_id' => $this->admin->id, 'name' => $group['name']],
                $group
            );
        }
    }

    private function createContacts(): void
    {
        $contacts = [
            // VIP Customers
            ['email' => 'sarah.johnson@outlook.com', 'name' => 'Sarah Johnson', 'groups' => ['VIP Customers', 'Enterprise'], 'source' => 'manual'],
            ['email' => 'michael.chen@gmail.com', 'name' => 'Michael Chen', 'groups' => ['VIP Customers'], 'source' => 'csv'],
            ['email' => 'emma.williams@yahoo.com', 'name' => 'Emma Williams', 'groups' => ['VIP Customers', 'Beta Testers'], 'source' => 'manual'],
            ['email' => 'james.brown@company.co', 'name' => 'James Brown', 'groups' => ['VIP Customers', 'Enterprise'], 'source' => 'csv'],

            // Newsletter Only
            ['email' => 'olivia.davis@proton.me', 'name' => 'Olivia Davis', 'groups' => ['Newsletter Only'], 'source' => 'newsletter'],
            ['email' => 'liam.martinez@icloud.com', 'name' => 'Liam Martinez', 'groups' => ['Newsletter Only'], 'source' => 'newsletter'],
            ['email' => 'sophia.garcia@gmail.com', 'name' => 'Sophia Garcia', 'groups' => ['Newsletter Only'], 'source' => 'newsletter'],
            ['email' => 'noah.rodriguez@hotmail.com', 'name' => 'Noah Rodriguez', 'groups' => ['Newsletter Only'], 'source' => 'newsletter'],

            // New Signups
            ['email' => 'ava.anderson@gmail.com', 'name' => 'Ava Anderson', 'groups' => ['New Signups'], 'source' => 'csv'],
            ['email' => 'ethan.taylor@outlook.com', 'name' => 'Ethan Taylor', 'groups' => ['New Signups'], 'source' => 'manual'],
            ['email' => 'isabella.thomas@yahoo.com', 'name' => 'Isabella Thomas', 'groups' => ['New Signups'], 'source' => 'csv'],

            // Enterprise
            ['email' => 'alex.jackson@enterprise.com', 'name' => 'Alex Jackson', 'groups' => ['Enterprise', 'VIP Customers'], 'source' => 'manual'],
            ['email' => 'charlotte.white@corp.net', 'name' => 'Charlotte White', 'groups' => ['Enterprise'], 'source' => 'csv'],

            // Beta Testers
            ['email' => 'daniel.harris@beta.io', 'name' => 'Daniel Harris', 'groups' => ['Beta Testers', 'New Signups'], 'source' => 'manual'],
            ['email' => 'mia.clark@startup.co', 'name' => 'Mia Clark', 'groups' => ['Beta Testers'], 'source' => 'manual'],

            // Inactive
            ['email' => 'william.lewis@oldmail.com', 'name' => 'William Lewis', 'groups' => ['Inactive'], 'source' => 'csv'],
            ['email' => 'grace.walker@ghost.com', 'name' => 'Grace Walker', 'groups' => ['Inactive'], 'source' => 'csv'],
            ['email' => 'henry.hall@stale.org', 'name' => 'Henry Hall', 'groups' => ['Inactive', 'Newsletter Only'], 'source' => 'newsletter'],
        ];

        $now = now();
        foreach ($contacts as $i => $contact) {
            $addedAt = $now->copy()->subDays(rand(5, 90))->subHours(rand(0, 23));

            Contact::updateOrCreate(
                ['email' => $contact['email'], 'user_id' => $this->admin->id],
                [
                    'uuid' => Str::uuid(),
                    'name' => $contact['name'],
                    'groups' => $contact['groups'],
                    'source' => $contact['source'],
                    'added_at' => $addedAt,
                ]
            );

            $this->contactEmails[] = $contact['email'];
        }
    }

    private function createNewsletterSubscribers(): void
    {
        $subscribers = [
            // Active subscribers
            ['email' => 'sarah.johnson@outlook.com', 'name' => 'Sarah Johnson', 'status' => 'active', 'source' => 'website'],
            ['email' => 'michael.chen@gmail.com', 'name' => 'Michael Chen', 'status' => 'active', 'source' => 'blog'],
            ['email' => 'olivia.davis@proton.me', 'name' => 'Olivia Davis', 'status' => 'active', 'source' => 'social'],
            ['email' => 'liam.martinez@icloud.com', 'name' => 'Liam Martinez', 'status' => 'active', 'source' => 'referral'],
            ['email' => 'sophia.garcia@gmail.com', 'name' => 'Sophia Garcia', 'status' => 'active', 'source' => 'website'],
            ['email' => 'ava.anderson@gmail.com', 'name' => 'Ava Anderson', 'status' => 'active', 'source' => 'advertising'],
            ['email' => 'ethan.taylor@outlook.com', 'name' => 'Ethan Taylor', 'status' => 'active', 'source' => 'website'],
            ['email' => 'daniel.harris@beta.io', 'name' => 'Daniel Harris', 'status' => 'active', 'source' => 'blog'],

            // Pending (double opt-in)
            ['email' => 'isabella.thomas@yahoo.com', 'name' => 'Isabella Thomas', 'status' => 'pending', 'source' => 'website'],
            ['email' => 'alex.jackson@enterprise.com', 'name' => 'Alex Jackson', 'status' => 'pending', 'source' => 'referral'],

            // Unsubscribed
            ['email' => 'william.lewis@oldmail.com', 'name' => 'William Lewis', 'status' => 'unsubscribed', 'source' => 'website'],
            ['email' => 'grace.walker@ghost.com', 'name' => 'Grace Walker', 'status' => 'unsubscribed', 'source' => 'social'],
        ];

        foreach ($subscribers as $sub) {
            $subscribedAt = now()->subDays(rand(10, 120))->subHours(rand(0, 23));
            $unsubscribedAt = $sub['status'] === 'unsubscribed' ? $subscribedAt->copy()->addDays(rand(1, 30)) : null;

            NewsletterSubscriber::updateOrCreate(
                ['email' => $sub['email']],
                [
                    'uuid' => Str::uuid(),
                    'name' => $sub['name'],
                    'phone' => $this->randomPhone(),
                    'source' => $sub['source'],
                    'status' => $sub['status'],
                    'subscribed_at' => $subscribedAt,
                    'unsubscribed_at' => $unsubscribedAt,
                    'unsubscribe_token' => Str::random(64),
                    'verify_token' => Str::random(64),
                    'verified_at' => $sub['status'] === 'active' ? $subscribedAt : null,
                ]
            );
        }
    }

    private function createCampaigns(): void
    {
        $this->createSentCampaigns();
        $this->createDraftCampaigns();
        $this->createScheduledCampaign();
        $this->createFailedCampaign();
    }

    private function createSentCampaigns(): void
    {
        $campaigns = [
            [
                'name' => 'Welcome Series - August',
                'subject' => 'Welcome to EchoMail, {{first_name}}!',
                'recipient_type' => 'newsletter',
                'sent_days_ago' => 14,
                'total_sent' => 8,
                'opens' => 6,
                'clicks' => 3,
            ],
            [
                'name' => 'Product Launch Announcement',
                'subject' => 'Introducing our latest feature - Campaign Scheduling!',
                'recipient_type' => 'all',
                'sent_days_ago' => 7,
                'total_sent' => 16,
                'opens' => 12,
                'clicks' => 7,
            ],
            [
                'name' => 'Weekly Digest - W32',
                'subject' => 'Your weekly digest, {{first_name}}',
                'recipient_type' => 'groups',
                'groups' => ['VIP Customers', 'Enterprise'],
                'sent_days_ago' => 3,
                'total_sent' => 6,
                'opens' => 5,
                'clicks' => 2,
            ],
            [
                'name' => 'Beta Tester Feedback Request',
                'subject' => 'We need your feedback, {{first_name}}!',
                'recipient_type' => 'groups',
                'groups' => ['Beta Testers'],
                'sent_days_ago' => 1,
                'total_sent' => 2,
                'opens' => 2,
                'clicks' => 1,
            ],
            [
                'name' => 'Summer Promotion',
                'subject' => 'Exclusive offer just for you, {{first_name}}',
                'recipient_type' => 'manual',
                'manual_emails' => ['sarah.johnson@outlook.com', 'michael.chen@gmail.com', 'emma.williams@yahoo.com', 'james.brown@company.co'],
                'sent_days_ago' => 10,
                'total_sent' => 4,
                'opens' => 3,
                'clicks' => 2,
            ],
        ];

        foreach ($campaigns as $data) {
            $content = $this->buildCampaignContent($data['name']);
            $sentAt = now()->subDays($data['sent_days_ago']);
            $createdAt = $sentAt->copy()->subHours(rand(1, 48));

            $campaign = Campaign::updateOrCreate(
                ['user_id' => $this->admin->id, 'name' => $data['name']],
                [
                    'uuid' => Str::uuid(),
                    'created_by' => $this->admin->id,
                    'subject' => $data['subject'],
                    'content' => $content,
                    'status' => 'sent',
                    'recipient_config' => $this->buildRecipientConfig($data),
                    'total_recipients' => $data['total_sent'],
                    'total_sent' => $data['total_sent'],
                    'total_failed' => 0,
                    'opens' => $data['opens'],
                    'clicks' => $data['clicks'],
                    'open_rate' => round(($data['opens'] / $data['total_sent']) * 100, 2),
                    'click_rate' => round(($data['clicks'] / $data['total_sent']) * 100, 2),
                    'sent_at' => $sentAt,
                    'created_at' => $createdAt,
                ]
            );

            $this->createRecipients($campaign, $data);
        }
    }

    private function createDraftCampaigns(): void
    {
        $drafts = [
            [
                'name' => 'Holiday Special Draft',
                'subject' => 'Holiday deals are here!',
                'recipient_type' => 'all',
            ],
            [
                'name' => 'Monthly Report Template',
                'subject' => 'Your monthly summary, {{first_name}}',
                'recipient_type' => 'newsletter',
            ],
        ];

        foreach ($drafts as $data) {
            Campaign::updateOrCreate(
                ['user_id' => $this->admin->id, 'name' => $data['name']],
                [
                    'uuid' => Str::uuid(),
                    'created_by' => $this->admin->id,
                    'subject' => $data['subject'],
                    'content' => $this->buildCampaignContent($data['name']),
                    'status' => 'draft',
                    'recipient_config' => $this->buildRecipientConfig($data),
                    'total_recipients' => 0,
                ]
            );
        }
    }

    private function createScheduledCampaign(): void
    {
        $campaign = Campaign::updateOrCreate(
            ['user_id' => $this->admin->id, 'name' => 'Scheduled Weekly Newsletter'],
            [
                'uuid' => Str::uuid(),
                'created_by' => $this->admin->id,
                'subject' => 'Your weekly digest, {{first_name}}',
                'content' => $this->buildCampaignContent('Scheduled Weekly Newsletter'),
                'status' => 'scheduled',
                'recipient_config' => ['type' => 'newsletter'],
                'scheduled_at' => now()->addDays(2),
                'frequency' => 'weekly',
                'next_run_at' => now()->addDays(2),
            ]
        );
    }

    private function createFailedCampaign(): void
    {
        $sentAt = now()->subDays(5);

        $campaign = Campaign::updateOrCreate(
            ['user_id' => $this->admin->id, 'name' => 'Failed SMTP Test'],
            [
                'uuid' => Str::uuid(),
                'created_by' => $this->admin->id,
                'subject' => 'Test email - SMTP configuration',
                'content' => $this->buildCampaignContent('Failed SMTP Test'),
                'status' => 'failed',
                'recipient_config' => ['type' => 'manual', 'manual_emails' => ['invalid@bounced.test', 'another@invalid.test']],
                'total_recipients' => 2,
                'total_sent' => 0,
                'total_failed' => 2,
                'error_message' => 'SMTP connection timeout: could not connect to mail server',
                'sent_at' => $sentAt,
            ]
        );

        // Add failed recipients
        foreach (['invalid@bounced.test', 'another@invalid.test'] as $email) {
            CampaignRecipient::updateOrCreate(
                ['campaign_id' => $campaign->id, 'email' => $email],
                [
                    'token' => Str::random(32),
                    'status' => 'failed',
                    'error_message' => 'SMTP Error: Mailbox not found',
                ]
            );
        }
    }

    private function createRecipients(Campaign $campaign, array $data): void
    {
        $emails = match ($data['recipient_type'] ?? 'all') {
            'manual' => $data['manual_emails'] ?? [],
            'newsletter' => array_slice([
                'sarah.johnson@outlook.com', 'michael.chen@gmail.com', 'olivia.davis@proton.me',
                'liam.martinez@icloud.com', 'sophia.garcia@gmail.com', 'ava.anderson@gmail.com',
                'ethan.taylor@outlook.com', 'daniel.harris@beta.io',
            ], 0, $data['total_sent']),
            default => array_slice($this->contactEmails, 0, $data['total_sent']),
        };

        $openedOffset = $data['total_sent'] - $data['opens'];
        $clickedOffset = $data['total_sent'] - $data['clicks'];

        foreach ($emails as $i => $email) {
            $sentAt = $campaign->sent_at ?? now();
            $opened = $i >= $openedOffset;
            $clicked = $opened && $i >= $clickedOffset;

            CampaignRecipient::updateOrCreate(
                ['campaign_id' => $campaign->id, 'email' => $email],
                [
                    'token' => Str::random(32),
                    'status' => 'sent',
                    'opened_at' => $opened ? $sentAt->copy()->addMinutes(rand(5, 720)) : null,
                    'clicked_at' => $clicked ? $sentAt->copy()->addMinutes(rand(10, 1440)) : null,
                ]
            );
        }
    }

    private function buildRecipientConfig(array $data): array
    {
        return match ($data['recipient_type'] ?? 'all') {
            'newsletter' => ['type' => 'newsletter'],
            'groups' => ['type' => 'groups', 'groups' => $data['groups'] ?? []],
            'manual' => ['type' => 'manual', 'manual_emails' => $data['manual_emails'] ?? []],
            default => ['type' => 'all'],
        };
    }

    private function buildCampaignContent(string $name): string
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
                    ['type' => 'text', 'text' => 'Hello ', 'styles' => []],
                    ['type' => 'text', 'text' => '{{first_name}}', 'styles' => ['bold' => true]],
                    ['type' => 'text' , 'text' => ', thank you for being part of EchoMail. We hope you enjoy our latest updates and features!', 'styles' => []],
                ],
                'children' => [],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'bulletListItem',
                'props' => ['checked' => false],
                'content' => [['type' => 'text', 'text' => 'Track your campaign performance in real time', 'styles' => []]],
                'children' => [],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'bulletListItem',
                'props' => ['checked' => false],
                'content' => [['type' => 'text', 'text' => 'Schedule emails for optimal delivery times', 'styles' => []]],
                'children' => [],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'bulletListItem',
                'props' => ['checked' => false],
                'content' => [['type' => 'text', 'text' => 'Use personalization codes to make every email unique', 'styles' => []]],
                'children' => [],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'paragraph',
                'props' => ['textAlignment' => 'left'],
                'content' => [['type' => 'text', 'text' => 'Best regards, The EchoMail Team', 'styles' => []]],
                'children' => [],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function createWebhook(): void
    {
        Webhook::updateOrCreate(
            ['user_id' => $this->admin->id, 'url' => 'https://hooks.example.com/echomail'],
            [
                'events' => ['campaign.sent', 'campaign.failed', 'subscriber.new'],
                'secret' => Str::random(32),
                'active' => true,
            ]
        );
    }

    private function createAuditLogs(): void
    {
        $actions = [
            ['action' => 'campaign.created', 'entity_type' => 'campaign', 'details' => ['name' => 'Welcome Series - August']],
            ['action' => 'campaign.sent', 'entity_type' => 'campaign', 'details' => ['total_sent' => 8, 'total_failed' => 0]],
            ['action' => 'contacts.imported', 'entity_type' => 'contact', 'details' => ['count' => 12, 'source' => 'csv']],
            ['action' => 'campaign.created', 'entity_type' => 'campaign', 'details' => ['name' => 'Product Launch Announcement']],
            ['action' => 'campaign.sent', 'entity_type' => 'campaign', 'details' => ['total_sent' => 16, 'total_failed' => 0]],
            ['action' => 'newsletter.subscriber', 'entity_type' => 'newsletter', 'details' => ['email' => 'ava.anderson@gmail.com']],
            ['action' => 'campaign.sent', 'entity_type' => 'campaign', 'details' => ['total_sent' => 6, 'total_failed' => 0]],
            ['action' => 'settings.updated', 'entity_type' => 'user', 'details' => ['field' => 'profile']],
            ['action' => 'webhook.created', 'entity_type' => 'webhook', 'details' => ['url' => 'https://hooks.example.com/echomail']],
            ['action' => 'campaign.failed', 'entity_type' => 'campaign', 'details' => ['name' => 'Failed SMTP Test', 'error' => 'SMTP timeout']],
        ];

        foreach ($actions as $i => $log) {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => $log['action'],
                'entity_type' => $log['entity_type'],
                'entity_id' => (string) $this->admin->id,
                'details' => $log['details'],
                'created_at' => now()->subDays(rand(0, 14))->subHours(rand(0, 23)),
            ]);
        }
    }

    private function randomPhone(): string
    {
        $codes = ['+1', '+44', '+61', '+234', '+91', '+49', '+33', '+81'];
        $code = $codes[array_rand($codes)];
        return $code . ' ' . rand(100, 999) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999);
    }

    private function createTemplatesForDemoUser(): void
    {
        $templates = [
            [
                'name' => 'Welcome Email',
                'subject' => 'Welcome to EchoMail, {{first_name}}!',
                'content' => json_encode([
                    ['id' => Str::uuid(), 'type' => 'heading', 'props' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Welcome aboard!', 'styles' => []]], 'children' => []],
                    ['id' => Str::uuid(), 'type' => 'paragraph', 'props' => ['textAlignment' => 'left'], 'content' => [['type' => 'text', 'text' => 'Hi {{first_name}}, we are thrilled to have you with us. Your account is ready.', 'styles' => []]], 'children' => []],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => 'Weekly Newsletter',
                'subject' => 'Your weekly digest, {{first_name}}',
                'content' => json_encode([
                    ['id' => Str::uuid(), 'type' => 'heading', 'props' => ['level' => 2], 'content' => [['type' => 'text', 'text' => "This Week's Highlights", 'styles' => []]], 'children' => []],
                    ['id' => Str::uuid(), 'type' => 'paragraph', 'props' => ['textAlignment' => 'left'], 'content' => [['type' => 'text', 'text' => 'Hello {{first_name}}, here is what happened this week.', 'styles' => []]], 'children' => []],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => 'Promotional Offer',
                'subject' => 'Exclusive offer just for you, {{first_name}}',
                'content' => json_encode([
                    ['id' => Str::uuid(), 'type' => 'heading', 'props' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Limited Time Offer', 'styles' => []]], 'children' => []],
                    ['id' => Str::uuid(), 'type' => 'paragraph', 'props' => ['textAlignment' => 'left'], 'content' => [['type' => 'text', 'text' => 'Hey {{first_name}}, use code ', 'styles' => []], ['type' => 'text', 'text' => 'ECHOMAIL20', 'styles' => ['bold' => true, 'code' => true]], ['type' => 'text', 'text' => ' to save 20%.', 'styles' => []]], 'children' => []],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => 'Product Update',
                'subject' => "What's new for {{first_name}}",
                'content' => json_encode([
                    ['id' => Str::uuid(), 'type' => 'heading', 'props' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Product Update', 'styles' => []]], 'children' => []],
                    ['id' => Str::uuid(), 'type' => 'paragraph', 'props' => ['textAlignment' => 'left'], 'content' => [['type' => 'text', 'text' => 'Hi {{first_name}}, here is what we shipped this month.', 'styles' => []]], 'children' => []],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => 'Re-engagement',
                'subject' => 'We miss you, {{first_name}}!',
                'content' => json_encode([
                    ['id' => Str::uuid(), 'type' => 'heading', 'props' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'We Miss You!', 'styles' => []]], 'children' => []],
                    ['id' => Str::uuid(), 'type' => 'paragraph', 'props' => ['textAlignment' => 'left'], 'content' => [['type' => 'text', 'text' => "It's been a while, {{first_name}}. We would love to have you back.", 'styles' => []]], 'children' => []],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ],
        ];

        foreach ($templates as $template) {
            CampaignTemplate::updateOrCreate(
                ['user_id' => $this->admin->id, 'name' => $template['name']],
                [
                    'subject' => $template['subject'],
                    'content' => $template['content'],
                ]
            );
        }
    }
}
