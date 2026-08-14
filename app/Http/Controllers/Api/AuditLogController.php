<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuditLogController extends Controller
{
    /**
     * Get audit logs (Admin only)
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'nullable|string|max:255',
            'entity_type' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $query = AuditLog::query();

            if ($request->action) {
                $query->where('action', 'like', "%{$request->action}%");
            }

            if ($request->entity_type) {
                $query->where('entity_type', $request->entity_type);
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?: 20);

            return ResponseHelper::success(
                1,
                'Audit logs retrieved successfully.',
                [
                    'logs' => $logs->items(),
                    'pagination' => [
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'per_page' => $logs->perPage(),
                        'total' => $logs->total(),
                        'from' => $logs->firstItem(),
                        'to' => $logs->lastItem(),
                    ],
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Audit log index error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve audit logs.', [], 500);
        }
    }

    /**
     * Clear audit logs (Admin only)
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'older_than_days' => 'nullable|integer|min:1|max:3650',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $query = AuditLog::query();

            if ($request->older_than_days) {
                $query->where('created_at', '<', now()->subDays($request->older_than_days));
            }

            $deleted = $query->delete();

            return ResponseHelper::success(
                1,
                'Audit logs cleared.',
                ['deleted' => $deleted],
                200
            );

        } catch (Exception $e) {
            Log::error('Audit log clear error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to clear audit logs.', [], 500);
        }
    }
}
