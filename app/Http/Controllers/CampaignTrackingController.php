<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignTrackingController extends Controller
{
    private const GIF_BYTES = 'R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    public function open(string $campaignUuid, string $token)
    {
        $campaign = Campaign::where('uuid', $campaignUuid)->first();
        $recipient = $campaign?->recipients()->where('token', $token)->first();

        if ($recipient && !$recipient->opened_at) {
            $recipient->update(['opened_at' => now()]);
            $campaign->syncStats();
        }

        return response(
            base64_decode(self::GIF_BYTES),
            200,
            [
                'Content-Type' => 'image/gif',
                'Content-Length' => '43',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]
        );
    }

    public function click(Request $request, string $campaignUuid, string $token)
    {
        $campaign = Campaign::where('uuid', $campaignUuid)->first();
        $recipient = $campaign?->recipients()->where('token', $token)->first();

        if ($recipient && !$recipient->clicked_at) {
            $recipient->update(['clicked_at' => now()]);
            $campaign->syncStats();
        }

        $url = $request->query('url');

        if (!$url || !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = $campaign ? url('/campaigns/' . $campaign->uuid . '/track') : '/';
        }

        return redirect($url);
    }
}
