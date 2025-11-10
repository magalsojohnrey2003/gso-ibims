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
        $query = ManpowerRequest::with('user')->latest();

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
                'role' => $row->role,
                'purpose' => $row->purpose,
                'location' => $row->location,
                'office_agency' => $row->office_agency,
                'start_at' => optional($row->start_at)->toDateTimeString(),
                'end_at' => optional($row->end_at)->toDateTimeString(),
                'letter_url' => $row->letter_url,
                'status' => $row->status,
                'rejection_reason_subject' => $row->rejection_reason_subject,
                'rejection_reason_detail' => $row->rejection_reason_detail,
            ];
        });

        return response()->json($rows);
    }

    public function updateStatus(Request $request, ManpowerRequest $requestModel)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason_subject' => 'nullable|string|max:255',
            'rejection_reason_detail' => 'nullable|string',
        ]);

        if ($data['status'] === 'rejected') {
            if (empty($data['rejection_reason_subject']) || empty($data['rejection_reason_detail'])) {
                return response()->json(['message' => 'Rejection subject and detail are required.'], 422);
            }
            $requestModel->rejection_reason_subject = $data['rejection_reason_subject'];
            $requestModel->rejection_reason_detail = $data['rejection_reason_detail'];
        } else {
            $requestModel->rejection_reason_subject = null;
            $requestModel->rejection_reason_detail = null;
        }

        $requestModel->status = $data['status'];
        $requestModel->save();

        return response()->json(['message' => 'Status updated.', 'status' => $requestModel->status]);
    }
}
