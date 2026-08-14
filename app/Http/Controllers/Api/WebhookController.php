<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * List webhooks for the current user
     */
    public function index()
    {
        try {
            $webhooks = Webhook::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();

            return ResponseHelper::success(
                1,
                'Webhooks retrieved successfully.',
                ['webhooks' => $webhooks],
                200
            );

        } catch (Exception $e) {
            Log::error('Webhook index error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve webhooks.', [], 500);
        }
    }

    /**
     * Create a webhook
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:255',
            'events' => 'required|array|min:1',
            'events.*' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $webhook = Webhook::create([
                'user_id' => Auth::id(),
                'url' => $request->url,
                'events' => $request->events,
                'active' => $request->has('active') ? $request->boolean('active') : true,
            ]);

            return ResponseHelper::success(
                1,
                'Webhook created successfully.',
                ['webhook' => $webhook->fresh()],
                201
            );

        } catch (Exception $e) {
            Log::error('Webhook store error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to create webhook.', [], 500);
        }
    }

    /**
     * Show a single webhook
     */
    public function show($id)
    {
        try {
            $webhook = Webhook::where('user_id', Auth::id())->find($id);

            if (!$webhook) {
                return ResponseHelper::error(0, 'Webhook not found.', [], 404);
            }

            return ResponseHelper::success(
                1,
                'Webhook retrieved successfully.',
                ['webhook' => $webhook],
                200
            );

        } catch (Exception $e) {
            Log::error('Webhook show error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve webhook.', [], 500);
        }
    }

    /**
     * Update a webhook
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'nullable|url|max:255',
            'events' => 'nullable|array|min:1',
            'events.*' => 'required|string|max:50',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $webhook = Webhook::where('user_id', Auth::id())->find($id);

            if (!$webhook) {
                return ResponseHelper::error(0, 'Webhook not found.', [], 404);
            }

            $webhook->update($request->only(['url', 'events', 'active']));

            return ResponseHelper::success(
                1,
                'Webhook updated successfully.',
                ['webhook' => $webhook->fresh()],
                200
            );

        } catch (Exception $e) {
            Log::error('Webhook update error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to update webhook.', [], 500);
        }
    }

    /**
     * Delete a webhook
     */
    public function destroy($id)
    {
        try {
            $webhook = Webhook::where('user_id', Auth::id())->find($id);

            if (!$webhook) {
                return ResponseHelper::error(0, 'Webhook not found.', [], 404);
            }

            $webhook->delete();

            return ResponseHelper::success(1, 'Webhook deleted successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Webhook destroy error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete webhook.', [], 500);
        }
    }

    /**
     * Get delivery attempts for a webhook
     */
    public function deliveries($id)
    {
        try {
            $webhook = Webhook::where('user_id', Auth::id())->find($id);

            if (!$webhook) {
                return ResponseHelper::error(0, 'Webhook not found.', [], 404);
            }

            $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return ResponseHelper::success(
                1,
                'Webhook deliveries retrieved successfully.',
                ['deliveries' => $deliveries],
                200
            );

        } catch (Exception $e) {
            Log::error('Webhook deliveries error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve deliveries.', [], 500);
        }
    }

    /**
     * Send a test ping to a webhook
     */
    public function test($id)
    {
        try {
            $webhook = Webhook::where('user_id', Auth::id())->find($id);

            if (!$webhook) {
                return ResponseHelper::error(0, 'Webhook not found.', [], 404);
            }

            $webhook->dispatch('webhook.test', [
                'event' => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
            ]);

            return ResponseHelper::success(1, 'Test ping sent successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Webhook test error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to send test ping.', [], 500);
        }
    }
}
