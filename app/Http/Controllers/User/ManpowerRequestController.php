<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequest;
use App\Models\ManpowerRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                'quantity' => $row->quantity,
                'approved_quantity' => $row->approved_quantity,
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
                'public_url' => $row->public_status_url,
            ];
        });

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:10000',
            'manpower_role_id' => 'required|exists:manpower_roles,id',
            'purpose' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'office_agency' => 'nullable|string|max:255',
            'start_at' => 'required|date|after_or_equal:today',
            'end_at' => 'required|date|after:start_at',
            'letter' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';

        $role = ManpowerRole::findOrFail($data['manpower_role_id']);
        $data['role'] = $role->name;

        if ($request->hasFile('letter')) {
            $path = $request->file('letter')->store('manpower-letters', 'public');
            $data['letter_path'] = $path;
        }

        $data['public_token'] = (string) \Illuminate\Support\Str::uuid();
        $data['approved_quantity'] = null;

        $model = ManpowerRequest::create($data);

        return response()->json([
            'message' => 'Manpower request submitted.',
            'id' => $model->id,
        ]);
    }
}
