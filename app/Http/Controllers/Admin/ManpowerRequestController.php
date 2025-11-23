<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequest;
use App\Services\ManpowerRequestPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
            $numericSearch = preg_replace('/\D+/', '', $search);

            $query->where(function($q) use ($search, $numericSearch) {
                $q->where('purpose', 'like', "%$search%")
                  ->orWhere('role', 'like', "%$search%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('first_name', 'like', "%$search%")
                         ->orWhere('last_name', 'like', "%$search%")
                         ->orWhere('email', 'like', "%$search%");
                  });

                if ($numericSearch !== '') {
                    $q->orWhere('id', (int) $numericSearch);
                }
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
                'formatted_request_id' => $row->formatted_request_id,
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
                'qr_verified_form_url' => $row->qr_verified_form_url,
            ];
        });

        return response()->json($rows);
    }

    public function scan(Request $request, ManpowerRequest $manpowerRequest, ManpowerRequestPdfService $pdfService)
    {
        $wasUpdated = false;
        $scanTimestamp = now();

        if ($manpowerRequest->status !== 'approved') {
            if (! $manpowerRequest->approved_quantity) {
                $manpowerRequest->approved_quantity = $manpowerRequest->quantity;
            }

            $manpowerRequest->status = 'approved';
            $manpowerRequest->save();
            $wasUpdated = true;
        }

        $manpowerRequest = $manpowerRequest->fresh(['user', 'roleType']);

        $message = $wasUpdated
            ? 'Manpower request marked as Approved via QR scan.'
            : 'Manpower request was already marked as Approved.';

        $downloadUrl = null;

        try {
            $result = $pdfService->render($manpowerRequest);

            if (($result['success'] ?? false) && ! empty($result['content'])) {
                $timestamp = $scanTimestamp->format('YmdHis');
                $path = "qr-verified-forms/manpower-request-{$manpowerRequest->id}/manpower-request-{$manpowerRequest->id}-{$timestamp}.pdf";
                $disk = Storage::disk('public');

                if ($manpowerRequest->qr_verified_form_path && $disk->exists($manpowerRequest->qr_verified_form_path)) {
                    $disk->delete($manpowerRequest->qr_verified_form_path);
                }

                $disk->put($path, $result['content']);

                $manpowerRequest->qr_verified_form_path = $path;
                $manpowerRequest->save();

                $downloadUrl = $this->makeStorageUrl($path);
            }
        } catch (Throwable $e) {
            report($e);
        }

        $manpowerRequest = $manpowerRequest->fresh(['user', 'roleType']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $manpowerRequest->status,
                'updated' => $wasUpdated,
                'download_url' => $downloadUrl,
                'qr_verified_form_url' => $downloadUrl,
                'scan_timestamp' => $scanTimestamp->toIso8601String(),
            ]);
        }

        return view('admin.manpower.scan-result', [
            'manpowerRequest' => $manpowerRequest,
            'message' => $message,
            'updated' => $wasUpdated,
            'downloadUrl' => $downloadUrl,
            'scanTimestamp' => $scanTimestamp,
        ]);
    }

    public function updateStatus(Request $request, ManpowerRequest $manpowerRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:validated,approved,rejected',
            'approved_quantity' => 'nullable|integer|min:1',
        ]);

        if ($data['status'] === 'validated' || $data['status'] === 'approved') {
            $approved = $data['approved_quantity'] ?? $manpowerRequest->quantity;
            if ($approved > $manpowerRequest->quantity) {
                return response()->json([
                    'message' => 'Approved quantity cannot exceed requested quantity.',
                ], 422);
            }
            $manpowerRequest->approved_quantity = $approved;
            $manpowerRequest->status = $data['status'] === 'validated' ? 'validated' : 'approved';
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

    private function makeStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('public');
        } catch (Throwable) {
            $disk = null;
        }

        if ($disk && $disk->exists($path)) {
            $diskUrl = $disk->url($path);
            $currentRequest = null;

            try {
                $currentRequest = request();
            } catch (Throwable) {
                $currentRequest = null;
            }

            if ($diskUrl && filter_var($diskUrl, FILTER_VALIDATE_URL)) {
                if ($currentRequest) {
                    $parsed = parse_url($diskUrl) ?: [];
                    $port = $currentRequest->getPort();
                    $isDefaultPort = in_array($port, [null, 80, 443], true);
                    $missingPort = empty($parsed['port']);

                    if ($missingPort && ! $isDefaultPort) {
                        $scheme = $currentRequest->getScheme();
                        $host = $currentRequest->getHost();
                        $pathPart = $parsed['path'] ?? '';
                        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

                        return sprintf('%s://%s:%d%s%s', $scheme, $host, $port, $pathPart, $query);
                    }
                }

                return $diskUrl;
            }

            $relative = $diskUrl ?: ('/storage/' . ltrim($path, '/'));
            if ($relative && $relative[0] !== '/') {
                $relative = '/' . ltrim($relative, '/');
            }

            if ($currentRequest) {
                return rtrim($currentRequest->getSchemeAndHttpHost(), '/') . $relative;
            }

            return $relative;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return null;
    }
}
