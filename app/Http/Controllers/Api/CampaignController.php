<?php

namespace App\Http\Controllers\Api;

use App\Helper\BlockNoteParser;
use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\NewsletterSubscriber;
use Exception;
use Illuminate\Http\Request;
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
                'status' => 'nullable|in:draft,sent,failed',
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
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            DB::beginTransaction();

            // Create campaign
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'subject' => $request->subject,
                'content' => $request->content,
                'recipient_config' => $request->recipient_config,
                'created_by' => $user->id,
                'status' => 'draft',
            ]);

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

            // Send immediately if requested (default behavior)
            if ($request->get('send_immediately', true)) {
                $this->sendCampaign($campaign, $recipients);
            }

            DB::commit();

            return ResponseHelper::success(
                1,
                $request->get('send_immediately', true)
                    ? 'Campaign created and sent successfully!'
                    : 'Campaign created as draft successfully!',
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

            $this->sendCampaign($campaign, $recipients);

            return ResponseHelper::success(
                1,
                'Campaign sent successfully!',
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

            return ResponseHelper::success(
                1,
                'Campaign statistics retrieved successfully.',
                [
                    'stats' => $stats,
                    'recent_campaigns' => $recentCampaigns,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Campaign stats error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve statistics.', [], 500);
        }
    }

    /**
     * Send campaign to recipients (direct sending, no queue)
     */
    private function sendCampaign(Campaign $campaign, array $recipients)
    {
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new CampaignMail($campaign));
                $successCount++;
                Log::info("Successfully sent campaign {$campaign->uuid} to: {$email}");
            } catch (Exception $emailException) {
                $failureCount++;
                Log::error("Failed to send campaign {$campaign->uuid} to {$email}: " . $emailException->getMessage());
            }
        }

        $campaign->markAsSent($successCount, $failureCount);

        Log::info("Campaign {$campaign->uuid} sending completed. Success: {$successCount}, Failed: {$failureCount}");
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
}
