<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Get all contacts
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'group' => 'nullable|string|max:50',
                'source' => 'nullable|in:manual,csv,newsletter',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|in:email,name,added_at,source',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
            }

            $user = Auth::user();
            $query = Contact::byUser($user->id);

            // Search filter
            if ($request->search) {
                $query->search($request->search);
            }

            // Group filter
            if ($request->group && $request->group !== 'all') {
                $query->inGroup($request->group);
            }

            // Source filter
            if ($request->source) {
                $query->bySource($request->source);
            }

            // Sorting
            $sortBy = $request->sort_by ?: 'added_at';
            $sortOrder = $request->sort_order ?: 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->per_page ?: 25;
            $contacts = $query->paginate($perPage);

            // Statistics
            $stats = [
                'total' => Contact::byUser($user->id)->count(),
                'manual' => Contact::byUser($user->id)->bySource('manual')->count(),
                'csv' => Contact::byUser($user->id)->bySource('csv')->count(),
                'newsletter' => Contact::byUser($user->id)->bySource('newsletter')->count(),
                'today' => Contact::byUser($user->id)->whereDate('added_at', today())->count(),
                'this_week' => Contact::byUser($user->id)->whereBetween('added_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Contact::byUser($user->id)->whereMonth('added_at', now()->month)->count(),
            ];

            return ResponseHelper::success(
                1,
                'Contacts retrieved successfully.',
                [
                    'contacts' => $contacts->items(),
                    'pagination' => [
                        'current_page' => $contacts->currentPage(),
                        'last_page' => $contacts->lastPage(),
                        'per_page' => $contacts->perPage(),
                        'total' => $contacts->total(),
                        'from' => $contacts->firstItem(),
                        'to' => $contacts->lastItem(),
                    ],
                    'stats' => $stats,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact index error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve contacts.', [], 500);
        }
    }

    /**
     * Store a new contact
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'groups' => 'nullable|array',
            'groups.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            // Check if contact already exists for this user
            $existingContact = Contact::byUser($user->id)
                ->where('email', $request->email)
                ->first();

            if ($existingContact) {
                return ResponseHelper::error(0, 'A contact with this email already exists.', [], 409);
            }

            $contact = Contact::create([
                'user_id' => $user->id,
                'email' => $request->email,
                'name' => $request->name,
                'groups' => $request->groups ?: [],
                'source' => 'manual',
            ]);

            return ResponseHelper::success(
                1,
                'Contact created successfully.',
                ['contact' => $this->formatContactData($contact)],
                201
            );

        } catch (Exception $e) {
            Log::error('Contact store error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to create contact.', [], 500);
        }
    }

    /**
     * Show single contact
     */
    public function show($uuid)
    {
        try {
            $user = Auth::user();
            $contact = Contact::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$contact) {
                return ResponseHelper::error(0, 'Contact not found.', [], 404);
            }

            return ResponseHelper::success(
                1,
                'Contact retrieved successfully.',
                ['contact' => $this->formatContactData($contact)],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact show error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve contact.', [], 500);
        }
    }

    /**
     * Update contact
     */
    public function update(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|max:255',
            'name' => 'nullable|string|max:255',
            'groups' => 'nullable|array',
            'groups.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $contact = Contact::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$contact) {
                return ResponseHelper::error(0, 'Contact not found.', [], 404);
            }

            // Check for duplicate email if email is being updated
            if ($request->has('email') && $request->email !== $contact->email) {
                $existingContact = Contact::byUser($user->id)
                    ->where('email', $request->email)
                    ->where('id', '!=', $contact->id)
                    ->first();

                if ($existingContact) {
                    return ResponseHelper::error(0, 'A contact with this email already exists.', [], 409);
                }
            }

            $contact->update($request->only(['email', 'name', 'groups']));

            return ResponseHelper::success(
                1,
                'Contact updated successfully.',
                ['contact' => $this->formatContactData($contact->fresh())],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact update error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to update contact.', [], 500);
        }
    }

    /**
     * Delete contact
     */
    public function destroy($uuid)
    {
        try {
            $user = Auth::user();
            $contact = Contact::byUser($user->id)->where('uuid', $uuid)->first();

            if (!$contact) {
                return ResponseHelper::error(0, 'Contact not found.', [], 404);
            }

            $contact->delete();

            return ResponseHelper::success(1, 'Contact deleted successfully.', [], 200);

        } catch (Exception $e) {
            Log::error('Contact delete error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete contact.', [], 500);
        }
    }

    /**
     * Bulk delete contacts
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'required|string|exists:contacts,uuid',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $deletedCount = Contact::byUser($user->id)
                ->whereIn('uuid', $request->contact_ids)
                ->delete();

            return ResponseHelper::success(
                1,
                "Successfully deleted {$deletedCount} contact(s).",
                ['deleted_count' => $deletedCount],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact bulk delete error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to delete contacts.', [], 500);
        }
    }

    /**
     * Import contacts from CSV
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array|min:1',
            'contacts.*.email' => 'required|email|max:255',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.groups' => 'nullable|array',
            'contacts.*.groups.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();
            $imported = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($request->contacts as $index => $contactData) {
                // Check if contact already exists
                $existingContact = Contact::byUser($user->id)
                    ->where('email', $contactData['email'])
                    ->first();

                if ($existingContact) {
                    $skipped++;
                    $errors[] = [
                        'row' => $index + 1,
                        'email' => $contactData['email'],
                        'error' => 'Contact already exists'
                    ];
                    continue;
                }

                Contact::create([
                    'user_id' => $user->id,
                    'email' => $contactData['email'],
                    'name' => $contactData['name'] ?? null,
                    'groups' => $contactData['groups'] ?? [],
                    'source' => 'csv',
                ]);

                $imported++;
            }

            DB::commit();

            return ResponseHelper::success(
                1,
                "Import completed. {$imported} contacts imported, {$skipped} skipped.",
                [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
                200
            );

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Contact CSV import error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to import contacts.', [], 500);
        }
    }

    /**
     * Export contacts
     */
    public function export(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group' => 'nullable|string|max:50',
                'source' => 'nullable|in:manual,csv,newsletter',
                'format' => 'nullable|in:csv,json',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
            }

            $user = Auth::user();
            $query = Contact::byUser($user->id);

            // Apply filters
            if ($request->group && $request->group !== 'all') {
                $query->inGroup($request->group);
            }

            if ($request->source) {
                $query->bySource($request->source);
            }

            $contacts = $query->orderBy('added_at', 'desc')->get();
            $format = $request->format ?: 'csv';

            if ($format === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="contacts.csv"',
                ];

                $csv = "Email,Name,Groups,Source,Added Date\n";
                foreach ($contacts as $contact) {
                    $csv .= sprintf(
                        '"%s","%s","%s","%s","%s"' . "\n",
                        $contact->email,
                        $contact->name ?: '',
                        implode(';', $contact->groups ?: []),
                        $contact->source,
                        $contact->added_at->format('Y-m-d H:i:s')
                    );
                }

                return response($csv, 200, $headers);
            }

            // JSON format
            return ResponseHelper::success(
                1,
                'Contacts exported successfully.',
                ['contacts' => $contacts->map(fn($c) => $this->formatContactData($c))],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact export error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to export contacts.', [], 500);
        }
    }

    /**
     * Get contact groups
     */
    public function getGroups()
    {
        try {
            $user = Auth::user();

            // Get predefined groups
            $predefinedGroups = ContactGroup::byUser($user->id)
                ->orderBy('name')
                ->get()
                ->map(fn($group) => [
                    'uuid' => $group->uuid,
                    'name' => $group->name,
                    'description' => $group->description,
                    'color' => $group->color,
                    'contacts_count' => $group->contacts_count,
                    'is_predefined' => true,
                ]);

            // Get groups used in contacts
            $usedGroups = Contact::byUser($user->id)
                ->whereNotNull('groups')
                ->get()
                ->flatMap(fn($contact) => $contact->groups ?: [])
                ->unique()
                ->filter()
                ->values()
                ->map(fn($groupName) => [
                    'name' => $groupName,
                    'contacts_count' => Contact::byUser($user->id)->inGroup($groupName)->count(),
                    'is_predefined' => false,
                ]);

            // Merge and remove duplicates
            $allGroupNames = $predefinedGroups->pluck('name')->merge($usedGroups->pluck('name'))->unique();
            $mergedGroups = $allGroupNames->map(function($groupName) use ($predefinedGroups, $usedGroups) {
                $predefined = $predefinedGroups->firstWhere('name', $groupName);
                $used = $usedGroups->firstWhere('name', $groupName);

                return $predefined ?: $used;
            })->filter()->values();

            return ResponseHelper::success(
                1,
                'Contact groups retrieved successfully.',
                ['groups' => $mergedGroups],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact groups error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve contact groups.', [], 500);
        }
    }

    /**
     * Create contact group
     */
    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            // Check if group already exists
            $existingGroup = ContactGroup::byUser($user->id)
                ->where('name', $request->name)
                ->first();

            if ($existingGroup) {
                return ResponseHelper::error(0, 'A group with this name already exists.', [], 409);
            }

            $group = ContactGroup::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'color' => $request->color ?: '#3b82f6',
            ]);

            return ResponseHelper::success(
                1,
                'Contact group created successfully.',
                ['group' => $this->formatGroupData($group)],
                201
            );

        } catch (Exception $e) {
            Log::error('Contact group create error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to create contact group.', [], 500);
        }
    }

    /**
     * Get contact statistics
     */
    public function getStats()
    {
        try {
            $user = Auth::user();

            $stats = [
                'total_contacts' => Contact::byUser($user->id)->count(),
                'contacts_today' => Contact::byUser($user->id)->whereDate('added_at', today())->count(),
                'contacts_this_week' => Contact::byUser($user->id)->whereBetween('added_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'contacts_this_month' => Contact::byUser($user->id)->whereMonth('added_at', now()->month)->count(),
                'contacts_this_year' => Contact::byUser($user->id)->whereYear('added_at', now()->year)->count(),
                'total_groups' => ContactGroup::byUser($user->id)->count(),
            ];

            // Source breakdown
            $sourceStats = Contact::byUser($user->id)
                ->selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            // Monthly growth (last 12 months)
            $monthlyGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyGrowth[] = [
                    'month' => $date->format('M Y'),
                    'contacts' => Contact::byUser($user->id)
                        ->whereYear('added_at', $date->year)
                        ->whereMonth('added_at', $date->month)
                        ->count(),
                ];
            }

            return ResponseHelper::success(
                1,
                'Contact statistics retrieved successfully.',
                [
                    'overview' => $stats,
                    'sources' => $sourceStats,
                    'monthly_growth' => $monthlyGrowth,
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Contact stats error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve statistics.', [], 500);
        }
    }

    /**
     * Format contact data for response
     */
    private function formatContactData(Contact $contact): array
    {
        return [
            'uuid' => $contact->uuid,
            'email' => $contact->email,
            'name' => $contact->name,
            'groups' => $contact->groups ?: [],
            'source' => $contact->source,
            'added_at' => $contact->added_at->toISOString(),
            'initials' => $contact->initials,
            'created_at' => $contact->created_at->toISOString(),
        ];
    }

    /**
     * Format group data for response
     */
    private function formatGroupData(ContactGroup $group): array
    {
        return [
            'uuid' => $group->uuid,
            'name' => $group->name,
            'description' => $group->description,
            'color' => $group->color,
            'contacts_count' => $group->contacts_count,
            'created_at' => $group->created_at->toISOString(),
        ];
    }
}
