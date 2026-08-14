<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SystemTickController extends Controller
{
    /**
     * External cron trigger. Dispatches due scheduled campaigns and processes
     * the queue so a free cron service can keep delivery running without SSH.
     */
    public function run(Request $request, string $token)
    {
        $expected = (string) config('app.tick_token');

        if ($expected === '' || !hash_equals($expected, $token)) {
            abort(403, 'Invalid tick token.');
        }

        $lock = Cache::lock('system:tick', 30);

        if (!$lock->get()) {
            return response()->json(['ok' => false, 'reason' => 'busy'], 429);
        }

        try {
            Artisan::call('campaigns:dispatch');
            $dispatchOutput = Artisan::output();

            Artisan::call('queue:work', [
                'connection' => 'database',
                '--stop-when-empty' => true,
                '--max-time' => 25,
                '--tries' => 3,
            ]);
            $workOutput = Artisan::output();
        } finally {
            $lock->release();
        }

        return response()->json([
            'ok' => true,
            'dispatch' => $dispatchOutput,
            'queue' => $workOutput,
            'processed_at' => now()->toIso8601String(),
        ]);
    }
}
