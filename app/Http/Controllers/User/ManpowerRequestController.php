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

class ManpowerRequestController extends Controller
{
    public function index()
    {
        return view('user.manpower.index');
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $query = ManpowerRequest::where('user_id', $user->id)->latest();

        $rows = $query->get()->map(function(ManpowerRequest $row) {
            return [
                'id' => $row->id,
                'formatted_request_id' => $row->formatted_request_id,
                'quantity' => $row->quantity,
                'approved_quantity' => $row->approved_quantity,
                'role' => $row->role,
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

    public function store(Request $request, PhilSmsService $philSms)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:999',
            'manpower_role_id' => 'required|exists:manpower_roles,id',
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

        $role = ManpowerRole::findOrFail($data['manpower_role_id']);
        $data['role'] = $role->name;

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

        unset($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time'], $data['municipality_id'], $data['barangay_id'], $data['letter_file']);

        $model = ManpowerRequest::create($data);
        $model->load('user');

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
