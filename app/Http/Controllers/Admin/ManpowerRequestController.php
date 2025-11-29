<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequest;
use App\Models\RejectionReason;
use App\Notifications\RequestNotification;
use App\Services\ManpowerRequestPdfService;
use App\Services\PhilSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $query = ManpowerRequest::with(['user', 'roleType', 'roles'])->latest();

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
            $roleBreakdown = $row->role_breakdown;
            $roleSummary = $row->buildRoleSummary();
            $totalQuantity = $row->total_requested_quantity;

            return [
                'id' => $row->id,
                'user' => $row->user ? [
                    'id' => $row->user->id,
                    'name' => $row->user->full_name ?? trim(($row->user->first_name ?? '').' '.($row->user->last_name ?? '')),
                    'email' => $row->user->email,
                ] : null,
                'formatted_request_id' => $row->formatted_request_id,
                'quantity' => $totalQuantity,
                'approved_quantity' => $this->computeApprovedQuantity($row),
                'role' => $roleSummary,
                'role_type' => $row->roleType ? $row->roleType->name : null,
                'role_breakdown' => $roleBreakdown,
                'has_multiple_roles' => $row->has_multiple_roles,
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

    public function updateStatus(Request $request, ManpowerRequest $manpowerRequest, PhilSmsService $philSms)
    {
        $data = $request->validate([
            'status' => 'required|in:validated,approved,rejected',
            'approved_quantity' => 'nullable|integer|min:1',
            'rejection_reason_id' => 'nullable|integer',
            'rejection_reason_subject' => 'nullable|string|max:255',
            'rejection_reason_detail' => 'nullable|string|max:2000',
        ]);

        $status = $data['status'];

        DB::beginTransaction();

        try {
            if ($status === 'validated' || $status === 'approved') {
                $approved = $data['approved_quantity'] ?? $manpowerRequest->quantity;
                if ($approved > $manpowerRequest->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Approved quantity cannot exceed requested quantity.',
                    ], 422);
                }

                $manpowerRequest->approved_quantity = $approved;
                $manpowerRequest->status = $status === 'validated' ? 'validated' : 'approved';
                $manpowerRequest->rejection_reason_subject = null;
                $manpowerRequest->rejection_reason_detail = null;
            } else {
                $templateId = $data['rejection_reason_id'] ?? null;
                $resolvedTemplate = null;

                if ($templateId) {
                    $resolvedTemplate = RejectionReason::query()
                        ->lockForUpdate()
                        ->find($templateId);

                    if (! $resolvedTemplate) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'The selected rejection reason is no longer available.',
                        ], 422);
                    }
                }

                $subject = trim((string) ($data['rejection_reason_subject'] ?? ''));
                $detail = trim((string) ($data['rejection_reason_detail'] ?? ''));

                if ($resolvedTemplate) {
                    $subject = $resolvedTemplate->subject;
                    $detail = $resolvedTemplate->detail;
                }

                if ($subject === '' || $detail === '') {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Please provide both a rejection subject and detailed explanation.',
                    ], 422);
                }

                $manpowerRequest->status = 'rejected';
                $manpowerRequest->approved_quantity = null;
                $manpowerRequest->rejection_reason_subject = $subject;
                $manpowerRequest->rejection_reason_detail = $detail;

                if ($resolvedTemplate) {
                    $resolvedTemplate->usage_count = ($resolvedTemplate->usage_count ?? 0) + 1;
                    $resolvedTemplate->save();
                }
            }

            $manpowerRequest->save();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $philSms->notifyRequesterManpowerStatus($manpowerRequest);

        $manpowerRequest->refresh(['user']);

        $actor = $request->user();
        $actorName = $this->resolveActorName($actor);
        $requester = $manpowerRequest->user;
        $requesterName = $this->resolveActorName($requester) ?? 'Requester';
        $statusLabel = $this->formatManpowerStatusLabel($manpowerRequest->status);

        if ($requester) {
            $payload = [
                'type' => 'manpower_status_changed',
                'message' => $actorName
                    ? sprintf('%s set %s to %s.', $actorName, $manpowerRequest->formatted_request_id ?? ('Request #' . $manpowerRequest->id), $statusLabel ?? $manpowerRequest->status)
                    : sprintf('%s is now %s.', $manpowerRequest->formatted_request_id ?? ('Request #' . $manpowerRequest->id), $statusLabel ?? $manpowerRequest->status),
                'manpower_request_id' => $manpowerRequest->id,
                'formatted_request_id' => $manpowerRequest->formatted_request_id,
                'status' => $manpowerRequest->status,
                'status_label' => $statusLabel,
                'approved_quantity' => $manpowerRequest->approved_quantity,
                'quantity' => $manpowerRequest->quantity,
                'role' => $manpowerRequest->role,
                'location' => $manpowerRequest->location,
                'municipality' => $manpowerRequest->municipality,
                'barangay' => $manpowerRequest->barangay,
                'rejection_reason_subject' => $manpowerRequest->rejection_reason_subject,
                'rejection_reason_detail' => $manpowerRequest->rejection_reason_detail,
                'start_at' => optional($manpowerRequest->start_at)->toDateTimeString(),
                'end_at' => optional($manpowerRequest->end_at)->toDateTimeString(),
                'actor_id' => $actor?->id,
                'actor_role' => $actor?->role,
                'actor_name' => $actorName,
                'user_id' => $manpowerRequest->user_id,
                'user_name' => $requesterName,
                'updated_at' => optional($manpowerRequest->updated_at)->toDateTimeString(),
            ];

            $requester->notify(new RequestNotification($payload));
        }

        $message = match ($manpowerRequest->status) {
            'validated' => 'Request marked as validated. The requester will prepare for deployment.',
            'approved' => 'Request approved. Personnel can now be dispatched.',
            'rejected' => $this->buildRejectionToastMessage(
                (string) $manpowerRequest->rejection_reason_subject
            ),
            default => 'Status updated.',
        };

        return response()->json([
            'message' => $message,
            'status' => $manpowerRequest->status,
            'approved_quantity' => $manpowerRequest->approved_quantity,
            'rejection_reason_subject' => $manpowerRequest->rejection_reason_subject,
            'rejection_reason_detail' => $manpowerRequest->rejection_reason_detail,
        ]);
    }

    private function buildRejectionToastMessage(string $subject): string
    {
        $cleanSubject = trim($subject);
        if ($cleanSubject === '') {
            return 'Request rejected. The requester has been notified.';
        }

        $trimmed = rtrim($cleanSubject, ".!?\- ");

        return sprintf('Request rejected - %s.', $trimmed);
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

    private function resolveActorName(?\App\Models\User $actor): ?string
    {
        if (! $actor) {
            return null;
        }

        $preferred = trim((string) ($actor->full_name ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        $fallback = trim((string) (($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')));
        if ($fallback !== '') {
            return $fallback;
        }

        return $actor->email ?? null;
    }

    private function formatManpowerStatusLabel(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        return match ($status) {
            'pending' => 'Pending Review',
            'validated' => 'Validated',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
