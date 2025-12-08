<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequest;
use App\Models\ManpowerRole;
use App\Models\User;
use App\Services\ManpowerRequestPdfService;
use App\Services\PhilSmsService;
use App\Support\MisOrLocations;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RequestNotification;
use Illuminate\Support\Collection;

class ManpowerRequestController extends Controller
{
    public function index()
    {
        return view('user.manpower.index');
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $query = ManpowerRequest::with('roles')->where('user_id', $user->id)->latest();

        $rows = $query->get()->map(function(ManpowerRequest $row) {
            $roleBreakdown = $row->role_breakdown;
            $roleSummary = $this->formatRoleListForDisplay($roleBreakdown) ?: $row->buildRoleSummary();
            $totalQuantity = $row->total_requested_quantity;

            return [
                'id' => $row->id,
                'formatted_request_id' => $row->formatted_request_id,
                'quantity' => $totalQuantity,
                'approved_quantity' => $this->computeApprovedQuantity($row),
                'role' => $roleSummary,
                'role_breakdown' => $roleBreakdown,
                'has_multiple_roles' => $row->has_multiple_roles,
                'reduction_reason' => $row->reduction_reason,
                'assigned_personnel_names' => $row->assigned_personnel_names,
                'purpose' => $row->purpose,
                'location' => $row->location,
                'municipality' => $row->municipality,
                'barangay' => $row->barangay,
                'office_agency' => $row->office_agency,
                'start_at' => optional($row->start_at)->toDateTimeString(),
                'end_at' => optional($row->end_at)->toDateTimeString(),
                'letter_url' => $row->letter_url,
                'status' => $row->status,
                'rejection_reason_subject' => $row->rejection_reason_subject,
                'rejection_reason_detail' => $row->rejection_reason_detail,
                'public_url' => $row->public_status_url,
            ];
        });

        return response()->json($rows);
    }

    private function computeApprovedQuantity(ManpowerRequest $request): ?int
    {
        $roles = collect($request->role_breakdown ?? []);
        if ($roles->isEmpty()) {
            return $request->approved_quantity;
        }

        $sum = $roles
            ->map(fn ($entry) => isset($entry['approved_quantity']) ? (int) $entry['approved_quantity'] : null)
            ->filter(fn ($value) => $value !== null && $value >= 0)
            ->sum();

        if ($sum > 0) {
            return $sum;
        }

        return $request->approved_quantity;
    }

    public function store(Request $request, PhilSmsService $philSms)
    {
        $data = $request->validate([
            'manpower_roles' => 'required',
            'purpose' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'municipality_id' => 'required|string',
            'barangay_id' => 'required|string',
            'office_agency' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_time' => 'nullable|date_format:H:i',
            'letter_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';

        $municipality = MisOrLocations::findMunicipality($data['municipality_id']);
        $barangay = MisOrLocations::findBarangay($data['municipality_id'], $data['barangay_id']);

        if (! $municipality || ! $barangay) {
            return response()->json([
                'message' => 'Please select a valid municipality and barangay.',
            ], 422);
        }

        $startDate = Carbon::parse($data['start_date'].' '.($data['start_time'] ?: '00:00'));
        $endDate = Carbon::parse($data['end_date'].' '.($data['end_time'] ?: '23:59'));

        if ($endDate->lt($startDate)) {
            return response()->json([
                'message' => 'End schedule must be the same day or after the start schedule.',
            ], 422);
        }

            $rolesResult = $this->normalizeRolesPayload($request->input('manpower_roles'));
            if (! $rolesResult['ok']) {
                return response()->json(['message' => $rolesResult['message']], 422);
            }

            $roleEntries = $rolesResult['roles'];
            $totalQuantity = $rolesResult['total_quantity'];

            $data['quantity'] = $totalQuantity;
            $data['role'] = $this->formatRoleListForDisplay($roleEntries);
            $data['manpower_role_id'] = $rolesResult['primary_role_id'];

        $data['start_at'] = $startDate;
        $data['end_at'] = $endDate;
        $data['municipality'] = $municipality['name'];
        $data['barangay'] = $barangay['name'];

        $letterUpload = $request->file('letter_file') ?: $request->file('letter');
        if ($letterUpload) {
            $path = $letterUpload->store('manpower-letters', 'public');
            $data['letter_path'] = $path;
        }

        $data['public_token'] = (string) \Illuminate\Support\Str::uuid();
        $data['approved_quantity'] = null;

        unset(
            $data['start_date'],
            $data['start_time'],
            $data['end_date'],
            $data['end_time'],
            $data['municipality_id'],
            $data['barangay_id'],
            $data['letter_file'],
            $data['manpower_roles'],
        );

        $model = ManpowerRequest::create($data);
        $model->load('user');

        $model->roles()->createMany($roleEntries);

        $model->refresh();
        $model->role = $this->formatRoleListForDisplay($model->role_breakdown);
        $model->quantity = $model->total_requested_quantity;
        $model->save();

        // Notify administrators via SMS when enabled.
        $philSms->notifyNewManpowerRequest($model);

        $admins = User::where('role', 'admin')->get();
        if ($admins->isNotEmpty()) {
            $requester = $model->user;
            $requesterName = optional($requester)->full_name;
            if (! $requesterName) {
                $requesterName = $requester
                    ? trim((string) (($requester->first_name ?? '') . ' ' . ($requester->last_name ?? '')))
                    : '';
            }
            if ($requesterName === '') {
                $requesterName = $requester?->email ?? 'Borrower';
            }

            $schedule = [
                'start_at' => optional($model->start_at)->toDateTimeString(),
                'end_at' => optional($model->end_at)->toDateTimeString(),
            ];

            $payload = [
                'type' => 'manpower_submitted',
                'message' => sprintf('New manpower request %s submitted by %s.', $model->formatted_request_id ?? ('Request #' . $model->id), $requesterName),
                'manpower_request_id' => $model->id,
                'formatted_request_id' => $model->formatted_request_id,
                'user_id' => $model->user_id,
                'user_name' => $requesterName,
                'actor_id' => $model->user_id,
                'actor_name' => $requesterName,
                'quantity' => $model->quantity,
                'role' => $model->role,
                'location' => $model->location,
                'municipality' => $model->municipality,
                'barangay' => $model->barangay,
                'purpose' => $model->purpose,
                'office_agency' => $model->office_agency,
                'schedule' => $schedule,
                'start_at' => $schedule['start_at'],
                'end_at' => $schedule['end_at'],
                'submitted_at' => optional($model->created_at)->toDateTimeString(),
            ];

            Notification::send($admins, new RequestNotification($payload));
        }

        return response()->json([
            'message' => 'Your manpower request has been submitted. We will notify you once it is reviewed.',
            'id' => $model->id,
        ]);
    }

    private function normalizeRolesPayload($raw): array
    {
        $decoded = $raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        }

        if (! is_array($decoded)) {
            return [
                'ok' => false,
                'message' => 'Invalid manpower roles payload. Please try again.',
            ];
        }

        $normalized = collect($decoded)
            ->map(function ($entry) {
                $roleId = isset($entry['manpower_role_id']) ? (int) $entry['manpower_role_id'] : (isset($entry['role_id']) ? (int) $entry['role_id'] : null);
                $quantity = isset($entry['quantity']) ? (int) $entry['quantity'] : null;
                if (! $roleId || ! $quantity || $quantity < 1) {
                    return null;
                }

                if ($quantity > 99) {
                    $quantity = 99;
                }

                return [
                    'manpower_role_id' => $roleId,
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'Please add at least one manpower role.',
            ];
        }

        $grouped = $normalized
            ->groupBy('manpower_role_id')
            ->map(fn (Collection $items, $roleId) => [
                'manpower_role_id' => (int) $roleId,
                'quantity' => (int) $items->sum('quantity'),
            ])
            ->values();

        $roleIds = $grouped->pluck('manpower_role_id')->unique()->all();
        $roles = ManpowerRole::whereIn('id', $roleIds)->get()->keyBy('id');
        if ($roles->count() !== count($roleIds)) {
            return [
                'ok' => false,
                'message' => 'One or more selected manpower roles are no longer available.',
            ];
        }

        $payload = $grouped->map(function ($entry) use ($roles) {
            $role = $roles->get($entry['manpower_role_id']);
            return [
                'manpower_role_id' => $entry['manpower_role_id'],
                'role_name' => $role?->name ?? 'Role',
                'quantity' => $entry['quantity'],
            ];
        })->values();

        $totalQuantity = (int) $payload->sum('quantity');
        if ($totalQuantity < 1) {
            return [
                'ok' => false,
                'message' => 'Total quantity must be at least 1.',
            ];
        }

        if ($totalQuantity > 99) {
            return [
                'ok' => false,
                'message' => 'Maximum of 99 personnel only.',
            ];
        }

        return [
            'ok' => true,
            'roles' => $payload->all(),
            'total_quantity' => $totalQuantity,
            'primary_role_id' => $payload->count() === 1 ? $payload->first()['manpower_role_id'] : null,
        ];
    }

    private function formatRoleListForDisplay(array $entries): string
    {
        $roles = collect($entries)
            ->map(function ($entry) {
                $qty = isset($entry['approved_quantity']) && $entry['approved_quantity'] > 0
                    ? (int) $entry['approved_quantity']
                    : (int) ($entry['quantity'] ?? 0);
                $label = trim((string) ($entry['role_name'] ?? $entry['role'] ?? ''));
                if ($qty < 1 || $label === '') {
                    return null;
                }
                return sprintf('Manpower-%s (x%d)', $label, $qty);
            })
            ->filter()
            ->values();

        return $roles->isEmpty() ? '' : $roles->implode(', ');
    }

    public function print(int $id, ManpowerRequestPdfService $pdfService)
    {
        $manpowerRequest = ManpowerRequest::with('user')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $result = $pdfService->render($manpowerRequest);
        if (! ($result['success'] ?? false) || empty($result['content'])) {
            abort(500, $result['message'] ?? 'Unable to generate the manpower request form.');
        }

        $filename = $result['filename'] ?? 'manpower-request.pdf';
        $mime = $result['mime'] ?? 'application/pdf';

        return response($result['content'], 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
