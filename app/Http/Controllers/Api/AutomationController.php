<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\AutomationEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $query = Automation::withCount(['steps', 'activeEnrollments'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $automations = $query->paginate($request->get('per_page', 15));

        return ResponseHelper::success(1, 'Automations retrieved', [
            'automations' => $automations,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:subscriber_joins,subscriber_tag,date_based,manual',
            'trigger_config' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.step_type' => 'required|in:wait,send_email,condition,tag,end',
            'steps.*.step_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        return DB::transaction(function () use ($request) {
            $automation = Automation::create([
                'uuid' => Str::uuid(),
                'user_id' => auth()->id(),
                'name' => $request->name,
                'description' => $request->description,
                'trigger_type' => $request->trigger_type,
                'trigger_config' => $request->trigger_config,
                'status' => 'draft',
            ]);

            foreach ($request->steps as $i => $step) {
                AutomationStep::create([
                    'automation_id' => $automation->id,
                    'step_order' => $i + 1,
                    'step_type' => $step['step_type'],
                    'step_config' => $step['step_config'],
                ]);
            }

            return ResponseHelper::success(1, 'Automation created', [
                'automation' => $automation->load('steps'),
            ], 201);
        });
    }

    public function show(string $uuid)
    {
        $automation = Automation::with(['steps', 'activeEnrollments'])
            ->where('uuid', $uuid)
            ->first();

        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }

        return ResponseHelper::success(1, 'Automation retrieved', [
            'automation' => $automation,
        ]);
    }

    public function update(Request $request, string $uuid)
    {
        $automation = Automation::where('uuid', $uuid)->first();
        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'sometimes|in:subscriber_joins,subscriber_tag,date_based,manual',
            'trigger_config' => 'nullable|array',
            'steps' => 'sometimes|array|min:1',
            'steps.*.step_type' => 'required|in:wait,send_email,condition,tag,end',
            'steps.*.step_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        return DB::transaction(function () use ($request, $automation) {
            $automation->update($request->only(['name', 'description', 'trigger_type', 'trigger_config']));

            if ($request->has('steps')) {
                $automation->steps()->delete();
                foreach ($request->steps as $i => $step) {
                    AutomationStep::create([
                        'automation_id' => $automation->id,
                        'step_order' => $i + 1,
                        'step_type' => $step['step_type'],
                        'step_config' => $step['step_config'],
                    ]);
                }
            }

            return ResponseHelper::success(1, 'Automation updated', [
                'automation' => $automation->fresh()->load('steps'),
            ]);
        });
    }

    public function activate(string $uuid)
    {
        $automation = Automation::where('uuid', $uuid)->first();
        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }
        if ($automation->status === 'active') {
            return ResponseHelper::error(0, 'Automation is already active', [], 400);
        }

        $automation->update(['status' => 'active']);

        return ResponseHelper::success(1, 'Automation activated', [
            'automation' => $automation,
        ]);
    }

    public function pause(string $uuid)
    {
        $automation = Automation::where('uuid', $uuid)->first();
        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }

        $automation->update(['status' => 'paused']);

        return ResponseHelper::success(1, 'Automation paused', [
            'automation' => $automation,
        ]);
    }

    public function enroll(Request $request, string $uuid)
    {
        $automation = Automation::where('uuid', $uuid)->first();
        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }
        if ($automation->status !== 'active') {
            return ResponseHelper::error(0, 'Automation must be active to enroll', [], 400);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        $existing = AutomationEnrollment::where('automation_id', $automation->id)
            ->where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return ResponseHelper::error(0, 'Email is already enrolled in this automation', [], 400);
        }

        $enrollment = AutomationEnrollment::create([
            'automation_id' => $automation->id,
            'email' => $request->email,
            'name' => $request->name,
            'status' => 'active',
            'current_step' => 1,
            'next_action_at' => now(),
        ]);

        $automation->increment('total_enrolled');

        return ResponseHelper::success(1, 'Enrolled in automation', [
            'enrollment' => $enrollment,
        ], 201);
    }

    public function enrollments(string $uuid)
    {
        $automation = Automation::where('uuid', $uuid)->first();
        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }

        $enrollments = AutomationEnrollment::where('automation_id', $automation->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ResponseHelper::success(1, 'Enrollments retrieved', [
            'enrollments' => $enrollments,
        ]);
    }

    public function destroy(string $uuid)
    {
        $automation = Automation::where('uuid', $uuid)->first();
        if (!$automation) {
            return ResponseHelper::error(0, 'Automation not found', [], 404);
        }
        if ($automation->status === 'active') {
            return ResponseHelper::error(0, 'Cannot delete an active automation. Pause it first.', [], 400);
        }

        $automation->delete();

        return ResponseHelper::success(1, 'Automation deleted');
    }

    public function stats()
    {
        $userId = auth()->id();

        $total = Automation::where('user_id', $userId)->count();
        $active = Automation::where('user_id', $userId)->where('status', 'active')->count();
        $totalEnrolled = AutomationEnrollment::whereHas('automation', fn($q) => $q->where('user_id', $userId))->count();
        $totalCompleted = AutomationEnrollment::where('status', 'completed')
            ->whereHas('automation', fn($q) => $q->where('user_id', $userId))
            ->count();

        return ResponseHelper::success(1, 'Automation stats', [
            'stats' => [
                'total_automations' => $total,
                'active_automations' => $active,
                'total_enrolled' => $totalEnrolled,
                'total_completed' => $totalCompleted,
            ],
        ]);
    }
}
