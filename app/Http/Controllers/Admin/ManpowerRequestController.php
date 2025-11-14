<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequest;
use Illuminate\Http\Request;

class ManpowerRequestController extends Controller
{
    public function index()
    {
        return view('admin.manpower.index');
    }

    public function list(Request $request)
    {
        $query = ManpowerRequest::with(['user', 'roleType'])->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function($q) use ($search) {
                $q->where('purpose', 'like', "%$search%")
                  ->orWhere('role', 'like', "%$search%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('first_name', 'like', "%$search%")
                         ->orWhere('last_name', 'like', "%$search%")
                         ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get()->map(function(ManpowerRequest $row) {
            return [
                'id' => $row->id,
                'user' => $row->user ? [
                    'id' => $row->user->id,
                    'name' => $row->user->full_name ?? trim(($row->user->first_name ?? '').' '.($row->user->last_name ?? '')),
                    'email' => $row->user->email,
                ] : null,
                'quantity' => $row->quantity,
                'approved_quantity' => $row->approved_quantity,
                'role' => $row->role,
                'role_type' => $row->roleType ? $row->roleType->name : null,
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
                'public_token' => $row->public_token,
                'public_url' => $row->public_status_url,
            ];
        });

        return response()->json($rows);
    }

    public function updateStatus(Request $request, ManpowerRequest $manpowerRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'approved_quantity' => 'nullable|integer|min:1',
        ]);

        if ($data['status'] === 'approved') {
            $approved = $data['approved_quantity'] ?? $manpowerRequest->quantity;
            if ($approved > $manpowerRequest->quantity) {
                return response()->json([
                    'message' => 'Approved quantity cannot exceed requested quantity.',
                ], 422);
            }
            $manpowerRequest->approved_quantity = $approved;
            $manpowerRequest->status = 'approved';
        } else {
            $manpowerRequest->status = 'rejected';
            $manpowerRequest->approved_quantity = null;
        }

        $manpowerRequest->rejection_reason_subject = null;
        $manpowerRequest->rejection_reason_detail = null;

        $manpowerRequest->save();

        return response()->json([
            'message' => 'Status updated.',
            'status' => $manpowerRequest->status,
            'approved_quantity' => $manpowerRequest->approved_quantity,
        ]);
    }
}
