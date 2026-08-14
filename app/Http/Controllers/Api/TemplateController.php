<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    /**
     * Get all templates
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $query = CampaignTemplate::byUser($user->id);

            if ($request->search) {
                $query->search($request->search);
            }

            $perPage = $request->per_page ?: 25;
            $templates = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return ResponseHelper::success(
                1,
                'Templates retrieved successfully.',
                [
                    'templates' => $templates->map(fn($template) => $this->formatTemplateData($template)),
                    'pagination' => [
                        'current_page' => $templates->currentPage(),
                        'last_page' => $templates->lastPage(),
                        'per_page' => $templates->perPage(),
                        'total' => $templates->total(),
                        'from' => $templates->firstItem(),
                        'to' => $templates->lastItem(),
                    ],
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Template index error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve templates.', [], 500);
        }
    }

    /**
     * Create template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            $template = CampaignTemplate::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'subject' => $request->subject,
                'content' => $request->content,
            ]);

            return ResponseHelper::success(
                1,
                'Template created successfully.',
                ['template' => $this->formatTemplateData($template)],
                201
            );

        } catch (Exception $e) {
            Log::error('Template store error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to create template.', [], 500);
        }
    }

    /**
     * Get single template
     */
    public function show($uuid)
    {
        try {
            $user = Auth::user();
            $template = CampaignTemplate::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$template) {
                return ResponseHelper::error(0, 'Template not found.', [], 404);
            }

            return ResponseHelper::success(
                1,
                'Template retrieved successfully.',
                $this->formatTemplateData($template, true),
                200
            );

        } catch (Exception $e) {
            Log::error('Template show error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve template.', [], 500);
        }
    }

    /**
     * Update template
     */
    public function update(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $template = CampaignTemplate::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$template) {
                return ResponseHelper::error(0, 'Template not found.', [], 404);
            }

            $template->update([
                'name' => $request->name,
                'subject' => $request->subject,
                'content' => $request->content,
            ]);

            return ResponseHelper::success(
                1,
                'Template updated successfully.',
                ['template' => $this->formatTemplateData($template->fresh())],
                200
            );

        } catch (Exception $e) {
            Log::error('Template update error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to update template.', [], 500);
        }
    }

    /**
     * Delete template
     */
    public function destroy($uuid)
    {
        try {
            $user = Auth::user();
            $template = CampaignTemplate::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$template) {
                return ResponseHelper::error(0, 'Template not found.', [], 404);
            }

            $template->delete();

            return ResponseHelper::success(1, 'Template deleted successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Template delete error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete template.', [], 500);
        }
    }

    private function formatTemplateData(CampaignTemplate $template, bool $includeContent = false): array
    {
        $data = [
            'uuid' => $template->uuid,
            'name' => $template->name,
            'subject' => $template->subject,
            'created_at' => $template->created_at,
            'updated_at' => $template->updated_at,
        ];

        if ($includeContent) {
            $data['content'] = $template->content;
            $data['html_content'] = $template->html_content;
        }

        return $data;
    }
}
