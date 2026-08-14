<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    /**
     * Get all campaigns
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:draft,scheduled,sending,sent,failed',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|in:name,subject,sent_at,total_recipients',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
            }

            $user = Auth::user();
            $query = Campaign::byUser($user->id)->with('creator:id,first_name,last_name');

            // Search filter
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->status) {
                $query->byStatus($request->status);
            }

            // Sorting
            $sortBy = $request->sort_by ?: 'created_at';
            $sortOrder = $request->sort_order ?: 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->per_page ?: 25;
            $campaigns = $query->paginate($perPage);

            // Statistics
            $stats = Campaign::getStats();

            return ResponseHelper::success(
                1,
                'Campaigns retrieved successfully.',
                [
                    'campaigns' => $campaigns->map(fn($campaign) => $this->formatCampaignData($campaign)),
                    'pagination' => [
                        'current_page' => $campaigns->currentPage(),
                        'last_page' => $campaigns->lastPage(),
                        'per_page' => $campaigns->perPage(),
                        'total' => $campaigns->total(),
                        'from' => $campaigns->firstItem(),
                        'to' => $campaigns->lastItem(),
                    ],
                    'stats' => $stats,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign index error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve campaigns.', [], 500);
        }
    }

    /**
     * Create and send campaign
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'recipient_config' => 'required|array',
            'recipient_config.type' => 'required|in:all,newsletter,groups,manual',
            'recipient_config.groups' => 'nullable|array',
            'recipient_config.groups.*' => 'string|max:50',
            'recipient_config.manual_emails' => 'nullable|array',
            'recipient_config.manual_emails.*' => 'email',
            'send_immediately' => 'boolean',
            'scheduled_at' => 'nullable|date',
            'frequency' => 'nullable|in:once,daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            $isScheduled = $request->filled('frequency') || $request->filled('scheduled_at');

            $frequency = $request->frequency === 'once' ? null : $request->frequency;

            DB::beginTransaction();

            // Create campaign
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'subject' => $request->subject,
                'content' => $request->content,
                'recipient_config' => $request->recipient_config,
                'created_by' => $user->id,
                'status' => $isScheduled ? 'scheduled' : 'draft',
                'scheduled_at' => $request->scheduled_at,
                'frequency' => $frequency,
            ]);

            if ($isScheduled && $request->frequency && $request->frequency !== 'once') {
                $campaign->update(['next_run_at' => $campaign->computeNextRunAt($request->scheduled_at)]);
            }

            // Get recipient list
            $recipients = $campaign->getRecipientList();

            if (empty($recipients)) {
                DB::rollback();
                return ResponseHelper::error(0, 'No recipients found for the selected criteria.', [], 400);
            }

            // Update campaign with recipient info
            $campaign->update([
                'recipient_emails' => $recipients,
                'total_recipients' => count($recipients),
            ]);

            DB::commit();

            // Send immediately if requested (default behavior)
            if ($request->get('send_immediately', true) && !$isScheduled) {
                $campaign->dispatchTo($recipients);

                // Process the queue synchronously so emails go out right away
                $this->processQueueNow();
            }

            if ($isScheduled) {
                $message = 'Campaign scheduled successfully!';
            } elseif ($request->get('send_immediately', true)) {
                $message = 'Campaign sent successfully!';
            } else {
                $message = 'Campaign created as draft successfully!';
            }

            return ResponseHelper::success(
                1,
                $message,
                $this->formatCampaignData($campaign->fresh('creator'), true),
                201
            );

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Campaign store error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to create campaign: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get single campaign
     */
    public function show($uuid)
    {
        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)
                ->with('creator:id,first_name,last_name')
                ->where('uuid', $uuid)
                ->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            return ResponseHelper::success(
                1,
                'Campaign retrieved successfully.',
                $this->formatCampaignData($campaign, true),
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign show error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve campaign.', [], 500);
        }
    }

    /**
     * Send a draft campaign
     */
    public function send($uuid)
    {
        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            if ($campaign->status !== 'draft') {
                return ResponseHelper::error(0, 'Only draft campaigns can be sent.', [], 400);
            }

            $recipients = $campaign->getRecipientList();

            if (empty($recipients)) {
                return ResponseHelper::error(0, 'No recipients found for this campaign.', [], 400);
            }

            $campaign->update([
                'recipient_emails' => $recipients,
                'total_recipients' => count($recipients),
            ]);

            $campaign->dispatchTo($recipients);

            // Process the queue synchronously so emails go out right away
            $this->processQueueNow();

            \App\Models\AuditLog::log(
                $user->id,
                'campaign.sent',
                'Campaign',
                $campaign->uuid,
                ['recipient_count' => count($recipients)]
            );

            return ResponseHelper::success(
                1,
                'Campaign is being sent!',
                $this->formatCampaignData($campaign->fresh('creator'), true),
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign send error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to send campaign.', [], 500);
        }
    }

    /**
     * Delete campaign
     */
    public function destroy($uuid)
    {
        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            // Only allow deletion of drafts or failed campaigns
            if ($campaign->status === 'sent') {
                return ResponseHelper::error(0, 'Cannot delete a sent campaign.', [], 400);
            }

            $campaign->delete();

            \App\Models\AuditLog::log(
                $user->id,
                'campaign.deleted',
                'Campaign',
                $campaign->uuid,
                ['name' => $campaign->name]
            );

            return ResponseHelper::success(1, 'Campaign deleted successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Campaign delete error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete campaign.', [], 500);
        }
    }

    /**
     * Duplicate campaign
     */
    public function duplicate($uuid)
    {
        try {
            $user = Auth::user();
            $originalCampaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$originalCampaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            $duplicatedCampaign = Campaign::create([
                'user_id' => $user->id,
                'name' => $originalCampaign->name . ' (Copy)',
                'subject' => $originalCampaign->subject,
                'content' => $originalCampaign->content,
                'recipient_config' => $originalCampaign->recipient_config,
                'created_by' => $user->id,
                'status' => 'draft',
            ]);

            // Update recipient list for the duplicate
            $recipients = $duplicatedCampaign->getRecipientList();
            $duplicatedCampaign->update([
                'recipient_emails' => $recipients,
                'total_recipients' => count($recipients),
            ]);

            \App\Models\AuditLog::log(
                $user->id,
                'campaign.duplicated',
                'Campaign',
                $originalCampaign->uuid,
                ['new_campaign' => $duplicatedCampaign->uuid]
            );

            return ResponseHelper::success(
                1,
                'Campaign duplicated successfully.',
                $this->formatCampaignData($duplicatedCampaign),
                201
            );

        } catch (Exception $e) {
            Log::error('Campaign duplicate error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to duplicate campaign.', [], 500);
        }
    }

    /**
     * Retry failed recipients of a campaign
     */
    public function retry($uuid)
    {
        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            if ($campaign->recipients()->failed()->count() === 0) {
                return ResponseHelper::error(0, 'No failed recipients to retry.', [], 400);
            }

            $failedCount = $campaign->recipients()->failed()->count();
            $campaign->retryFailed();

            \App\Models\AuditLog::log(
                $user->id,
                'campaign.retried',
                'Campaign',
                $campaign->uuid,
                ['retry_count' => $failedCount]
            );

            return ResponseHelper::success(
                1,
                'Failed recipients are being retried!',
                $this->formatCampaignData($campaign->fresh('creator'), true),
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign retry error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retry campaign.', [], 500);
        }
    }

    /**
     * Get recipient preview
     */
    public function getRecipientPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_config' => 'required|array',
            'recipient_config.type' => 'required|in:all,newsletter,groups,manual',
            'recipient_config.groups' => 'nullable|array',
            'recipient_config.groups.*' => 'string|max:50',
            'recipient_config.manual_emails' => 'nullable|array',
            'recipient_config.manual_emails.*' => 'email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            // Create temporary campaign to get recipient list
            $tempCampaign = new Campaign();
            $tempCampaign->user_id = $user->id;
            $tempCampaign->recipient_config = $request->recipient_config;

            $recipients = $tempCampaign->getRecipientList();

            return ResponseHelper::success(
                1,
                'Recipient preview retrieved successfully.',
                [
                    'recipients' => array_slice($recipients, 0, 50), // Limit to 50 for preview
                    'total_count' => count($recipients),
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Recipient preview error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to generate recipient preview.', [], 500);
        }
    }

    /**
     * Send a test email for a campaign
     */
    public function testSend(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            $personalization = [
                'first_name' => 'Test',
                'last_name' => 'User',
                'full_name' => 'Test User',
                'email' => $request->email,
            ];

            $token = \Illuminate\Support\Str::random(32);
            Mail::to($request->email)->send(new \App\Mail\CampaignMail($campaign, $request->email, $token, $personalization));

            \App\Models\AuditLog::log(
                $user->id,
                'campaign.test_sent',
                'Campaign',
                $campaign->uuid,
                ['to' => $request->email]
            );

            return ResponseHelper::success(1, 'Test email sent successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Campaign test send error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to send test email.', [], 500);
        }
    }

    /**
     * Get rendered preview of a campaign
     */
    public function preview($uuid)
    {
        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            $personalization = [
                'first_name' => 'Test',
                'last_name' => 'User',
                'full_name' => 'Test User',
                'email' => 'preview@example.com',
            ];

            $token = \Illuminate\Support\Str::random(32);
            $mail = new \App\Mail\CampaignMail($campaign, 'preview@example.com', $token, $personalization);

            $rendered = $mail->render();
            $subject = $mail->envelope()->subject;

            return ResponseHelper::success(
                1,
                'Campaign preview generated successfully.',
                [
                    'subject' => $subject,
                    'html' => $rendered,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign preview error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to generate campaign preview.', [], 500);
        }
    }

    /**
     * Get recipients of a campaign with drill-down
     */
    public function recipients(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,sent,failed,bounced',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            $query = $campaign->recipients()->orderBy('created_at', 'desc');

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where('email', 'like', "%{$search}%");
            }

            $recipients = $query->paginate($request->per_page ?: 15);

            return ResponseHelper::success(
                1,
                'Recipients retrieved successfully.',
                [
                    'recipients' => $recipients->items(),
                    'pagination' => [
                        'current_page' => $recipients->currentPage(),
                        'last_page' => $recipients->lastPage(),
                        'per_page' => $recipients->perPage(),
                        'total' => $recipients->total(),
                        'from' => $recipients->firstItem(),
                        'to' => $recipients->lastItem(),
                    ],
                    'summary' => [
                        'pending' => $campaign->recipients()->pending()->count(),
                        'sent' => $campaign->recipients()->sent()->count(),
                        'failed' => $campaign->recipients()->failed()->count(),
                        'bounced' => $campaign->recipients()->bounced()->count(),
                        'opened' => $campaign->recipients()->opened()->count(),
                        'clicked' => $campaign->recipients()->clicked()->count(),
                    ],
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign recipients error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve recipients.', [], 500);
        }
    }

    /**
     * Export recipients of a campaign as CSV
     */
    public function exportRecipients($uuid)
    {
        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            $recipients = $campaign->recipients()->orderBy('created_at', 'asc')->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="campaign-' . $campaign->uuid . '-recipients.csv"',
            ];

            $csv = "Email,Status,Opened At,Clicked At,Bounced At,Error\n";
            foreach ($recipients as $recipient) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s","%s"' . "\n",
                    $recipient->email,
                    $recipient->status,
                    $recipient->opened_at ? $recipient->opened_at->format('Y-m-d H:i:s') : '',
                    $recipient->clicked_at ? $recipient->clicked_at->format('Y-m-d H:i:s') : '',
                    $recipient->bounced_at ? $recipient->bounced_at->format('Y-m-d H:i:s') : '',
                    $recipient->error_message ?: ''
                );
            }

            \App\Models\AuditLog::log(
                $user->id,
                'campaign.exported',
                'Campaign',
                $campaign->uuid,
                ['recipient_count' => $recipients->count()]
            );

            return response($csv, 200, $headers);

        } catch (Exception $e) {
            Log::error('Campaign export error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to export recipients.', [], 500);
        }
    }

    /**
     * Mark recipients of a campaign as bounced
     */
    public function markBounced(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $campaign = Campaign::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$campaign) {
                return ResponseHelper::error(0, 'Campaign not found.', [], 404);
            }

            $updated = $campaign->recipients()
                ->whereIn('email', $request->emails)
                ->whereNull('bounced_at')
                ->update([
                    'status' => 'bounced',
                    'bounced_at' => now(),
                ]);

            if ($updated > 0) {
                $campaign->syncStats();
            }

            return ResponseHelper::success(
                1,
                'Recipients marked as bounced.',
                ['updated' => $updated],
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign mark bounced error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to mark recipients as bounced.', [], 500);
        }
    }

    /**
     * Get campaign statistics
     */
    public function getStats()
    {
        try {
            $user = Auth::user();
            $stats = Campaign::where('user_id', $user->id)->first() ? Campaign::getStats() : [];

            // Recent campaigns
            $recentCampaigns = Campaign::byUser($user->id)
                ->with('creator:id,first_name,last_name')
                ->recent()
                ->limit(5)
                ->get()
                ->map(fn($campaign) => $this->formatCampaignData($campaign));

            // Monthly activity (last 6 months)
            $monthlyActivity = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthlyActivity[] = [
                    'month' => $month->format('M Y'),
                    'sent' => (int) Campaign::byUser($user->id)
                        ->whereYear('sent_at', $month->year)
                        ->whereMonth('sent_at', $month->month)
                        ->sum('total_sent'),
                    'opens' => (int) Campaign::byUser($user->id)
                        ->whereYear('sent_at', $month->year)
                        ->whereMonth('sent_at', $month->month)
                        ->sum('opens'),
                    'clicks' => (int) Campaign::byUser($user->id)
                        ->whereYear('sent_at', $month->year)
                        ->whereMonth('sent_at', $month->month)
                        ->sum('clicks'),
                ];
            }

            // Engagement funnel
            $totalSent = Campaign::byUser($user->id)->sum('total_sent');
            $totalOpens = Campaign::byUser($user->id)->sum('opens');
            $totalClicks = Campaign::byUser($user->id)->sum('clicks');

            return ResponseHelper::success(
                1,
                'Campaign statistics retrieved successfully.',
                [
                    'stats' => $stats,
                    'recent_campaigns' => $recentCampaigns,
                    'monthly_activity' => $monthlyActivity,
                    'engagement' => [
                        'sent' => (int) $totalSent,
                        'opened' => (int) $totalOpens,
                        'clicked' => (int) $totalClicks,
                        'open_rate' => $totalSent > 0 ? round(($totalOpens / $totalSent) * 100, 2) : 0,
                        'click_rate' => $totalSent > 0 ? round(($totalClicks / $totalSent) * 100, 2) : 0,
                    ],
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign stats error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve statistics.', [], 500);
        }
    }

    /**
     * Format campaign data for response
     */
    private function formatCampaignData(Campaign $campaign, bool $includeContent = false): array
    {
        $data = [
            'uuid' => $campaign->uuid,
            'name' => $campaign->name,
            'subject' => $campaign->subject,
            'status' => $campaign->status,
            'recipient_config' => $campaign->recipient_config,
            'total_recipients' => $campaign->total_recipients,
            'total_sent' => $campaign->total_sent,
            'total_failed' => $campaign->total_failed,
            'opens' => $campaign->opens,
            'clicks' => $campaign->clicks,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'sent_at' => $campaign->sent_at,
            'scheduled_at' => $campaign->scheduled_at,
            'frequency' => $campaign->frequency,
            'next_run_at' => $campaign->next_run_at,
            'created_by' => $campaign->creator ? [
                'id' => $campaign->creator->id,
                'name' => $campaign->creator->first_name . ' ' . $campaign->creator->last_name,
            ] : null,
            'created_at' => $campaign->created_at,
            'updated_at' => $campaign->updated_at,
        ];

        if ($includeContent) {
            $data['content'] = $campaign->content;
            $data['html_content'] = $campaign->html_content;
            $data['recipient_emails'] = $campaign->recipient_emails;
        }

        return $data;
    }

    /**
     * Process the database queue synchronously so "send immediately"
     * campaigns deliver right away instead of waiting for the next cron tick.
     */
    private function processQueueNow(): void
    {
        try {
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--stop-when-empty' => true,
                '--max-time' => 25,
                '--tries' => 3,
            ]);
        } catch (Exception $e) {
            Log::error('Immediate queue processing error: ' . $e->getMessage());
        }
    }
}
