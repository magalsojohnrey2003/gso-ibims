<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RejectionReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RejectionReasonController extends Controller
{
    public function index(): JsonResponse
    {
        $reasons = RejectionReason::query()
            ->select(['id', 'subject', 'detail', 'usage_count', 'created_at'])
            ->latest('updated_at')
            ->get()
            ->map(function (RejectionReason $reason) {
                return [
                    'id' => $reason->id,
                    'subject' => $reason->subject,
                    'detail' => $reason->detail,
                    'usage_count' => $reason->usage_count,
                    'created_at' => $reason->created_at?->toIso8601String(),
                ];
            });

        return response()->json($reasons);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string'],
        ]);

        $subject = $this->cleanText($data['subject']);
        $detail = $this->cleanText($data['detail']);

        $existing = RejectionReason::query()
            ->whereRaw('LOWER(TRIM(subject)) = ?', [$this->normalize($subject)])
            ->whereRaw('LOWER(TRIM(detail)) = ?', [$this->normalize($detail)])
            ->first();

        $duplicate = false;
        if ($existing) {
            $reason = $existing;
            $duplicate = true;
        } else {
            $reason = RejectionReason::create([
                'subject' => $subject,
                'detail' => $detail,
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => $duplicate
                ? 'Reason already exists; using the saved version.'
                : 'Rejection reason saved successfully.',
            'reason' => [
                'id' => $reason->id,
                'subject' => $reason->subject,
                'detail' => $reason->detail,
                'usage_count' => $reason->usage_count,
            ],
            'duplicate' => $duplicate,
        ], $duplicate ? 200 : 201);
    }

    public function show(RejectionReason $rejectionReason): JsonResponse
    {
        return response()->json([
            'id' => $rejectionReason->id,
            'subject' => $rejectionReason->subject,
            'detail' => $rejectionReason->detail,
            'usage_count' => $rejectionReason->usage_count,
            'created_at' => $rejectionReason->created_at?->toIso8601String(),
        ]);
    }

    public function destroy(RejectionReason $rejectionReason): JsonResponse
    {
        $rejectionReason->delete();

        return response()->json([
            'message' => 'Rejection reason removed successfully.',
        ]);
    }

    private function cleanText(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function normalize(string $value): string
    {
        $normalized = Str::of($value)->trim()->lower();
        return $normalized->__toString();
    }
}

