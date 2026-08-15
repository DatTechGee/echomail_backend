<?php

namespace Database\Seeders;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\AutomationEnrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AutomationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'demo@echomail.com')->first()
            ?? User::where('email', 'admin@admin.com')->first();

        if (!$admin) {
            $this->command->warn('No admin user found. Run AdminUserSeeder first.');
            return;
        }

        $this->createWelcomeSequence($admin);
        $this->createReEngagement($admin);
        $this->createOnboarding($admin);

        $this->command->info('Automation workflows seeded successfully.');
    }

    private function createWelcomeSequence(User $admin): void
    {
        $automation = Automation::updateOrCreate(
            ['user_id' => $admin->id, 'name' => 'Welcome Series'],
            [
                'uuid' => Str::uuid(),
                'description' => 'Automated 3-step welcome sequence for new newsletter subscribers',
                'trigger_type' => 'subscriber_joins',
                'trigger_config' => ['source' => 'all'],
                'status' => 'active',
                'total_enrolled' => 5,
                'total_completed' => 2,
            ]
        );

        $steps = [
            [
                'step_order' => 1,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'Welcome to EchoMail, {{first_name}}!',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 2,
                'step_type' => 'wait',
                'step_config' => [
                    'duration' => 2,
                    'unit' => 'days',
                ],
            ],
            [
                'step_order' => 3,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'Getting the most out of EchoMail',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 4,
                'step_type' => 'wait',
                'step_config' => [
                    'duration' => 5,
                    'unit' => 'days',
                ],
            ],
            [
                'step_order' => 5,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'Your first week with EchoMail - What next?',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 6,
                'step_type' => 'end',
                'step_config' => [],
            ],
        ];

        foreach ($steps as $step) {
            AutomationStep::updateOrCreate(
                ['automation_id' => $automation->id, 'step_order' => $step['step_order']],
                $step
            );
        }

        // Enrollments
        $enrolled = [
            ['email' => 'sarah.johnson@outlook.com', 'name' => 'Sarah Johnson', 'status' => 'completed', 'current_step' => 6],
            ['email' => 'michael.chen@gmail.com', 'name' => 'Michael Chen', 'status' => 'completed', 'current_step' => 6],
            ['email' => 'olivia.davis@proton.me', 'name' => 'Olivia Davis', 'status' => 'active', 'current_step' => 3, 'next_action_at' => now()->addDay()],
            ['email' => 'liam.martinez@icloud.com', 'name' => 'Liam Martinez', 'status' => 'active', 'current_step' => 2, 'next_action_at' => now()->addHours(6)],
            ['email' => 'sophia.garcia@gmail.com', 'name' => 'Sophia Garcia', 'status' => 'active', 'current_step' => 1, 'next_action_at' => now()->addMinutes(30)],
        ];

        foreach ($enrolled as $e) {
            AutomationEnrollment::updateOrCreate(
                ['automation_id' => $automation->id, 'email' => $e['email']],
                [
                    'name' => $e['name'],
                    'status' => $e['status'],
                    'current_step' => $e['current_step'],
                    'next_action_at' => $e['next_action_at'] ?? null,
                    'completed_at' => $e['status'] === 'completed' ? now()->subDays(rand(1, 5)) : null,
                ]
            );
        }
    }

    private function createReEngagement(User $admin): void
    {
        $automation = Automation::updateOrCreate(
            ['user_id' => $admin->id, 'name' => 'Re-engagement Campaign'],
            [
                'uuid' => Str::uuid(),
                'description' => 'Win back inactive subscribers who haven\'t opened in 30 days',
                'trigger_type' => 'manual',
                'trigger_config' => [],
                'status' => 'paused',
                'total_enrolled' => 3,
                'total_completed' => 0,
            ]
        );

        $steps = [
            [
                'step_order' => 1,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'We miss you, {{first_name}}!',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 2,
                'step_type' => 'wait',
                'step_config' => [
                    'duration' => 3,
                    'unit' => 'days',
                ],
            ],
            [
                'step_order' => 3,
                'step_type' => 'condition',
                'step_config' => [
                    'type' => 'opened_previous',
                    'true_action' => 'tag',
                    'false_action' => 'send_email',
                ],
            ],
            [
                'step_order' => 4,
                'step_type' => 'tag',
                'step_config' => [
                    'action' => 'add',
                    'tag' => 're-engaged',
                ],
            ],
            [
                'step_order' => 5,
                'step_type' => 'end',
                'step_config' => [],
            ],
        ];

        foreach ($steps as $step) {
            AutomationStep::updateOrCreate(
                ['automation_id' => $automation->id, 'step_order' => $step['step_order']],
                $step
            );
        }

        $enrolled = [
            ['email' => 'william.lewis@oldmail.com', 'name' => 'William Lewis', 'status' => 'active', 'current_step' => 1],
            ['email' => 'grace.walker@ghost.com', 'name' => 'Grace Walker', 'status' => 'active', 'current_step' => 2, 'next_action_at' => now()->addDays(2)],
            ['email' => 'henry.hall@stale.org', 'name' => 'Henry Hall', 'status' => 'active', 'current_step' => 1],
        ];

        foreach ($enrolled as $e) {
            AutomationEnrollment::updateOrCreate(
                ['automation_id' => $automation->id, 'email' => $e['email']],
                [
                    'name' => $e['name'],
                    'status' => $e['status'],
                    'current_step' => $e['current_step'],
                    'next_action_at' => $e['next_action_at'] ?? null,
                ]
            );
        }
    }

    private function createOnboarding(User $admin): void
    {
        $automation = Automation::updateOrCreate(
            ['user_id' => $admin->id, 'name' => 'Beta Tester Onboarding'],
            [
                'uuid' => Str::uuid(),
                'description' => 'Onboarding sequence for beta testers with feature tutorials',
                'trigger_type' => 'subscriber_tag',
                'trigger_config' => ['tag' => 'beta_tester'],
                'status' => 'draft',
                'total_enrolled' => 0,
                'total_completed' => 0,
            ]
        );

        $steps = [
            [
                'step_order' => 1,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'Welcome to the Beta, {{first_name}}!',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 2,
                'step_type' => 'wait',
                'step_config' => [
                    'duration' => 1,
                    'unit' => 'days',
                ],
            ],
            [
                'step_order' => 3,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'Tutorial: Setting up your first campaign',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 4,
                'step_type' => 'wait',
                'step_config' => [
                    'duration' => 3,
                    'unit' => 'days',
                ],
            ],
            [
                'step_order' => 5,
                'step_type' => 'send_email',
                'step_config' => [
                    'subject' => 'Tutorial: Analytics and A/B Testing',
                    'delay' => 0,
                    'delay_unit' => 'minutes',
                ],
            ],
            [
                'step_order' => 6,
                'step_type' => 'tag',
                'step_config' => [
                    'action' => 'add',
                    'tag' => 'onboarded',
                ],
            ],
            [
                'step_order' => 7,
                'step_type' => 'end',
                'step_config' => [],
            ],
        ];

        foreach ($steps as $step) {
            AutomationStep::updateOrCreate(
                ['automation_id' => $automation->id, 'step_order' => $step['step_order']],
                $step
            );
        }
    }
}
