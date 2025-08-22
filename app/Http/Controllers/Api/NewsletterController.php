<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Public subscription endpoint
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'source' => 'required|in:website,social,search,referral,advertising,blog,other',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            // Check if email already exists
            $existingSubscriber = NewsletterSubscriber::where('email', $request->email)->first();

            if ($existingSubscriber) {
                if ($existingSubscriber->status === 'active') {
                    return ResponseHelper::error(0, 'This email is already subscribed to our newsletter.', [], 409);
                } else {
                    // Reactivate unsubscribed user
                    $existingSubscriber->resubscribe();
                    $existingSubscriber->update([
                        'name' => $request->name,
                        'source' => $request->source,
                    ]);

                    Mail::to($existingSubscriber->email)->send(new NewsletterWelcomeMail($existingSubscriber));

                    return ResponseHelper::success(
                        1,
                        'Welcome back! You have been resubscribed to our newsletter.',
                        [
                            'subscriber' => $this->formatSubscriberData($existingSubscriber),
                        ],
                        200
                    );
                }
            }

            // Create new subscriber
            $subscriber = NewsletterSubscriber::create([
                'email' => $request->email,
                'name' => $request->name,
                'source' => $request->source,
            ]);

            // Send welcome email
            Mail::to($subscriber->email)->send(new NewsletterWelcomeMail($subscriber));

            return ResponseHelper::success(
                1,
                'Successfully subscribed to newsletter! Check your email for a welcome message.',
                [
                    'subscriber' => $this->formatSubscriberData($subscriber),
                ],
                201
            );

        } catch (Exception $e) {
            Log::error('Newsletter subscription error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to subscribe to newsletter. Please try again.', [], 500);
        }
    }

    /**
     * Get all subscribers (Admin only)
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:all,active,unsubscribed',
                'source' => 'nullable|in:website,social,search,referral,advertising,blog,other',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|in:email,name,subscribed_at,status,source',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
            }

            $query = NewsletterSubscriber::query();

            // Search filter
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Source filter
            if ($request->source) {
                $query->where('source', $request->source);
            }

            // Sorting
            $sortBy = $request->sort_by ?: 'subscribed_at';
            $sortOrder = $request->sort_order ?: 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->per_page ?: 15;
            $subscribers = $query->paginate($perPage);

            // Statistics
            $stats = [
                'total' => NewsletterSubscriber::count(),
                'active' => NewsletterSubscriber::active()->count(),
                'unsubscribed' => NewsletterSubscriber::unsubscribed()->count(),
                'today' => NewsletterSubscriber::whereDate('subscribed_at', today())->count(),
                'this_week' => NewsletterSubscriber::whereBetween('subscribed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => NewsletterSubscriber::whereMonth('subscribed_at', now()->month)->count(),
            ];

            return ResponseHelper::success(
                1,
                'Newsletter subscribers retrieved successfully.',
                [
                    'subscribers' => $subscribers->items(),
                    'pagination' => [
                        'current_page' => $subscribers->currentPage(),
                        'last_page' => $subscribers->lastPage(),
                        'per_page' => $subscribers->perPage(),
                        'total' => $subscribers->total(),
                        'from' => $subscribers->firstItem(),
                        'to' => $subscribers->lastItem(),
                    ],
                    'stats' => $stats,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Newsletter index error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve subscribers.', [], 500);
        }
    }

    /**
     * Get single subscriber (Admin only)
     */
    public function show($uuid)
    {
        try {
            $subscriber = NewsletterSubscriber::where('uuid', $uuid)->first();

            if (!$subscriber) {
                return ResponseHelper::error(0, 'Subscriber not found.', [], 404);
            }

            return ResponseHelper::success(
                1,
                'Subscriber retrieved successfully.',
                ['subscriber' => $this->formatSubscriberData($subscriber)],
                200
            );

        } catch (Exception $e) {
            Log::error('Newsletter show error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve subscriber.', [], 500);
        }
    }

    /**
     * Delete single subscriber (Admin only)
     */
    public function destroy($uuid)
    {
        try {
            $subscriber = NewsletterSubscriber::where('uuid', $uuid)->first();

            if (!$subscriber) {
                return ResponseHelper::error(0, 'Subscriber not found.', [], 404);
            }

            $subscriber->delete();

            return ResponseHelper::success(1, 'Subscriber deleted successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Newsletter delete error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete subscriber.', [], 500);
        }
    }

    /**
     * Bulk delete subscribers (Admin only)
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscriber_ids' => 'required|array|min:1',
            'subscriber_ids.*' => 'required|string|exists:newsletter_subscribers,uuid',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $deletedCount = NewsletterSubscriber::whereIn('uuid', $request->subscriber_ids)->delete();

            return ResponseHelper::success(
                1,
                "Successfully deleted {$deletedCount} subscriber(s).",
                ['deleted_count' => $deletedCount],
                200
            );

        } catch (Exception $e) {
            Log::error('Newsletter bulk delete error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete subscribers.', [], 500);
        }
    }

    /**
     * Export subscribers (Admin only)
     */
    public function export(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:all,active,unsubscribed',
                'source' => 'nullable|in:website,social,search,referral,advertising,blog,other',
                'format' => 'nullable|in:csv,json',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
            }

            $query = NewsletterSubscriber::query();

            // Apply filters
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->source) {
                $query->where('source', $request->source);
            }

            $subscribers = $query->orderBy('subscribed_at', 'desc')->get();
            $format = $request->format ?: 'csv';

            if ($format === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="newsletter-subscribers.csv"',
                ];

                $csv = "Email,Name,Source,Status,Subscribed Date,Unsubscribed Date\n";
                foreach ($subscribers as $subscriber) {
                    $csv .= sprintf(
                        '"%s","%s","%s","%s","%s","%s"' . "\n",
                        $subscriber->email,
                        $subscriber->name ?: '',
                        $subscriber->source,
                        $subscriber->status,
                        $subscriber->subscribed_at->format('Y-m-d H:i:s'),
                        $subscriber->unsubscribed_at ? $subscriber->unsubscribed_at->format('Y-m-d H:i:s') : ''
                    );
                }

                return response($csv, 200, $headers);
            }

            // JSON format
            return ResponseHelper::success(
                1,
                'Subscribers exported successfully.',
                ['subscribers' => $subscribers->map(fn($s) => $this->formatSubscriberData($s))],
                200
            );

        } catch (Exception $e) {
            Log::error('Newsletter export error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to export subscribers.', [], 500);
        }
    }

    /**
     * Public unsubscribe endpoint
     */
    public function unsubscribe($token)
    {
        try {
            $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

            if (!$subscriber) {
                return ResponseHelper::error(0, 'Invalid unsubscribe link.', [], 404);
            }

            if ($subscriber->status === 'unsubscribed') {
                return ResponseHelper::success(1, 'You are already unsubscribed from our newsletter.', [], 200);
            }

            $subscriber->unsubscribe();

            return ResponseHelper::success(1, 'You have been successfully unsubscribed from our newsletter.', [], 200);

        } catch (Exception $e) {
            Log::error('Newsletter unsubscribe error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to unsubscribe. Please try again.', [], 500);
        }
    }

    /**
     * Get newsletter statistics (Admin only)
     */
    public function stats()
    {
        try {
            $stats = [
                'total_subscribers' => NewsletterSubscriber::count(),
                'active_subscribers' => NewsletterSubscriber::active()->count(),
                'unsubscribed' => NewsletterSubscriber::unsubscribed()->count(),
                'subscribers_today' => NewsletterSubscriber::whereDate('subscribed_at', today())->count(),
                'subscribers_this_week' => NewsletterSubscriber::whereBetween('subscribed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'subscribers_this_month' => NewsletterSubscriber::whereMonth('subscribed_at', now()->month)->count(),
                'subscribers_this_year' => NewsletterSubscriber::whereYear('subscribed_at', now()->year)->count(),
                'unsubscribes_this_month' => NewsletterSubscriber::whereMonth('unsubscribed_at', now()->month)->count(),
            ];

            // Source breakdown
            $sourceStats = NewsletterSubscriber::selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            // Monthly growth (last 12 months)
            $monthlyGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyGrowth[] = [
                    'month' => $date->format('M Y'),
                    'subscribers' => NewsletterSubscriber::whereYear('subscribed_at', $date->year)
                        ->whereMonth('subscribed_at', $date->month)
                        ->count(),
                ];
            }

            return ResponseHelper::success(
                1,
                'Newsletter statistics retrieved successfully.',
                [
                    'overview' => $stats,
                    'sources' => $sourceStats,
                    'monthly_growth' => $monthlyGrowth,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Newsletter stats error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve statistics.', [], 500);
        }
    }

    /**
     * Format subscriber data for response
     */
    private function formatSubscriberData(NewsletterSubscriber $subscriber): array
    {
        return [
            'uuid' => $subscriber->uuid,
            'email' => $subscriber->email,
            'name' => $subscriber->name,
            'source' => $subscriber->source,
            'status' => $subscriber->status,
            'subscribed_at' => $subscriber->subscribed_at->toISOString(),
            'unsubscribed_at' => $subscriber->unsubscribed_at?->toISOString(),
            'created_at' => $subscriber->created_at->toISOString(),
        ];
    }
}
