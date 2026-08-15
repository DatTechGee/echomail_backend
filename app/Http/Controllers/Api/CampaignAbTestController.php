<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignAbTest;
use App\Models\CampaignAbVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CampaignAbTestController extends Controller
{
    public function store(Request $request, string $campaignUuid)
    {
        $campaign = Campaign::where('uuid', $campaignUuid)->first();
        if (!$campaign) {
            return ResponseHelper::error(0, 'Campaign not found', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'test_type' => 'required|in:subject,content',
            'test_percentage' => 'required|integer|min:10|max:50',
            'variants' => 'required|array|min:2|max:4',
            'variants.*.subject' => 'required_if:test_type,subject|string|max:255',
            'variants.*.content' => 'required_if:test_type,content|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        return DB::transaction(function () use ($request, $campaign) {
            $abTest = CampaignAbTest::create([
                'campaign_id' => $campaign->id,
                'name' => $request->name ?? "A/B Test for {$campaign->name}",
                'test_type' => $request->test_type,
                'status' => 'draft',
                'test_percentage' => $request->test_percentage,
            ]);

            $variantKeys = ['A', 'B', 'C', 'D'];
            foreach ($request->variants as $i => $variant) {
                CampaignAbVariant::create([
                    'ab_test_id' => $abTest->id,
                    'variant_key' => $variantKeys[$i],
                    'subject' => $variant['subject'] ?? null,
                    'content' => $variant['content'] ?? null,
                ]);
            }

            return ResponseHelper::success(1, 'A/B test created', [
                'ab_test' => $abTest->load('variants'),
            ]);
        });
    }

    public function show(string $campaignUuid, string $abTestId)
    {
        $abTest = CampaignAbTest::with('variants')
            ->where('id', $abTestId)
            ->first();

        if (!$abTest) {
            return ResponseHelper::error(0, 'A/B test not found', [], 404);
        }

        return ResponseHelper::success(1, 'A/B test retrieved', [
            'ab_test' => $abTest,
        ]);
    }

    public function start(Request $request, string $campaignUuid, string $abTestId)
    {
        $abTest = CampaignAbTest::with(['variants', 'campaign'])->find($abTestId);
        if (!$abTest) {
            return ResponseHelper::error(0, 'A/B test not found', [], 404);
        }
        if ($abTest->status !== 'draft') {
            return ResponseHelper::error(0, 'A/B test has already been started', [], 400);
        }

        $abTest->update(['status' => 'running']);

        return ResponseHelper::success(1, 'A/B test started', [
            'ab_test' => $abTest->fresh('variants'),
        ]);
    }

    public function selectWinner(Request $request, string $campaignUuid, string $abTestId)
    {
        $validator = Validator::make($request->all(), [
            'variant_id' => 'required|exists:campaign_ab_variants,id',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        $abTest = CampaignAbTest::find($abTestId);
        if (!$abTest) {
            return ResponseHelper::error(0, 'A/B test not found', [], 404);
        }

        return DB::transaction(function () use ($request, $abTest) {
            $abTest->variants()->update(['is_winner' => false]);
            $winner = CampaignAbVariant::find($request->variant_id);
            $winner->update(['is_winner' => true]);
            $abTest->update([
                'status' => 'completed',
                'winner_selected_at' => now(),
            ]);

            if ($abTest->test_type === 'subject') {
                $abTest->campaign->update(['subject' => $winner->subject]);
            } elseif ($abTest->test_type === 'content') {
                $abTest->campaign->update([
                    'content' => $winner->content,
                    'html_content' => $winner->content,
                ]);
            }

            return ResponseHelper::success(1, 'Winner selected and campaign updated', [
                'ab_test' => $abTest->fresh('variants'),
                'winner' => $winner,
            ]);
        });
    }

    public function listForCampaign(string $campaignUuid)
    {
        $campaign = Campaign::where('uuid', $campaignUuid)->first();
        if (!$campaign) {
            return ResponseHelper::error(0, 'Campaign not found', [], 404);
        }

        $abTests = CampaignAbTest::with('variants')
            ->where('campaign_id', $campaign->id)
            ->orderByDesc('created_at')
            ->get();

        return ResponseHelper::success(1, 'A/B tests retrieved', [
            'ab_tests' => $abTests,
        ]);
    }

    public function destroy(string $campaignUuid, string $abTestId)
    {
        $abTest = CampaignAbTest::find($abTestId);
        if (!$abTest) {
            return ResponseHelper::error(0, 'A/B test not found', [], 404);
        }
        if ($abTest->status === 'running') {
            return ResponseHelper::error(0, 'Cannot delete a running A/B test', [], 400);
        }

        $abTest->delete();

        return ResponseHelper::success(1, 'A/B test deleted');
    }
}
