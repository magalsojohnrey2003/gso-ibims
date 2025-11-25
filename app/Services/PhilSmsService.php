<?php

namespace App\Services;

use App\Models\BorrowRequest;
use App\Models\ManpowerRequest;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilSmsService
{
    /**
     * Send a notification SMS to all configured admin recipients for a new manpower request.
     */
    public function notifyNewManpowerRequest(ManpowerRequest $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $recipients = $this->adminNumbers();
        if (empty($recipients)) {
            Log::info('PhilSMS: no admin numbers configured; skipping manpower request SMS.');
            return;
        }

        $userName = $this->resolveRequesterName($request);
        $role = $request->role ?: 'manpower';
        $quantity = max((int) $request->quantity, 1);
        $schedule = $this->formatSchedule($request);

        $message = sprintf(
            '%s requested "%d %s" personnel for %s. Please review the "Manpower Requests" section for further details.',
            $userName,
            $quantity,
            $role,
            $schedule
        );

        foreach ($recipients as $recipient) {
            $this->sendSms($recipient, $message);
        }
    }

    /**
     * Notify all configured administrators when a borrow request is submitted.
     */
    public function notifyAdminsBorrowRequest(BorrowRequest $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $recipients = $this->adminNumbers();
        if (! $recipients) {
            Log::info('PhilSMS: no admin numbers configured; skipping borrow request SMS.');
            return;
        }

        $request->loadMissing('user', 'items');

        $code = $this->formatBorrowRequestCode($request);
        $userName = $this->resolveBorrowRequesterName($request);
        $itemCount = (int) $request->items->sum('quantity');
        if ($itemCount <= 0) {
            $itemCount = $request->items->count();
        }
        $itemCount = max(1, $itemCount);
        $itemLabel = $itemCount === 1 ? 'item' : 'items';

        $schedule = $this->formatBorrowWindow($request);

        $message = sprintf(
            "GSO IBIMS: %s requested (%d %s) for %s. Please review the 'Borrow Requests' section for further details.",
            $userName,
            $itemCount,
            $itemLabel,
            $schedule
        );

        $message = $this->limitText($message, 155);

        foreach ($recipients as $recipient) {
            $this->sendSms($recipient, $message);
        }
    }

    /**
     * Notify the borrower when the request is approved or rejected.
     */
    public function notifyBorrowerStatus(BorrowRequest $request, string $status, ?string $reason = null): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $normalizedStatus = strtolower(trim($status));
        if (! in_array($normalizedStatus, ['validated', 'approved', 'rejected'], true)) {
            return;
        }

        $request->loadMissing('user');
        $user = $request->user;

        if (! $user) {
            Log::info('PhilSMS: borrow status SMS skipped - borrower missing.', [
                'borrow_request_id' => $request->id,
                'status' => $normalizedStatus,
            ]);
            return;
        }

        $numbers = $this->normalizeNumbers([(string) ($user->phone ?? '')]);
        if (! $numbers) {
            Log::info('PhilSMS: borrow status SMS skipped - borrower has no valid phone.', [
                'borrow_request_id' => $request->id,
                'status' => $normalizedStatus,
                'raw_phone' => $user->phone,
            ]);
            return;
        }

        $code = $this->formatBorrowRequestCode($request);
        $schedule = $this->formatBorrowWindow($request);

        if ($normalizedStatus === 'validated') {
            $message = sprintf(
                'GSO IBIMS: Request %s has been Validated. Please print the Request Form, Ensure the form is signed before submission.',
                $code
            );
        } elseif ($normalizedStatus === 'approved') {
            $deliveryDate = $this->formatBorrowStartDate($request) ?? $this->formatBorrowWindow($request);
            $message = sprintf(
                'GSO IBIMS: Request %s has been Approved. The items are scheduled for delivery on %s. Please ensure you are available to receive them. Thank you',
                $code,
                $deliveryDate
            );
        } else {
            $cleanReason = $reason ? $this->limitText($reason, 80) : 'No reason provided';
            $message = sprintf(
                'GSO IBIMS: Request %s has been Rejected. Reason: %s.',
                $code,
                $cleanReason
            );
        }

        foreach ($numbers as $recipient) {
            $this->sendSms($recipient, $message);
        }
    }

    /**
     * Notify the borrower when the items are marked as delivered.
     */
    public function notifyBorrowerDelivery(BorrowRequest $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $request->loadMissing('user');
        $user = $request->user;

        if (! $user) {
            Log::info('PhilSMS: borrow delivery SMS skipped - borrower missing.', [
                'borrow_request_id' => $request->id,
            ]);
            return;
        }

        $numbers = $this->normalizeNumbers([(string) ($user->phone ?? '')]);
        if (! $numbers) {
            Log::info('PhilSMS: borrow delivery SMS skipped - borrower has no valid phone.', [
                'borrow_request_id' => $request->id,
                'raw_phone' => $user->phone,
            ]);
            return;
        }

        $code = $this->formatBorrowRequestCode($request);

        $message = sprintf(
            'GSO IBIMS: Request %s is being Delivered. Please await further updates. Thank you',
            $code
        );

        foreach ($numbers as $recipient) {
            $this->sendSms($recipient, $message);
        }
    }

    /**
     * Notify the requester once an administrator updates the manpower request status.
     */
    public function notifyRequesterManpowerStatus(ManpowerRequest $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $request->loadMissing('user');
        $user = $request->user;
        if (! $user) {
            Log::info('PhilSMS: manpower status SMS skipped - requester missing.', [
                'request_id' => $request->id,
            ]);
            return;
        }

        $phone = (string) ($user->phone ?? '');
        $numbers = $this->normalizeNumbers([$phone]);
        if (! $numbers) {
            Log::info('PhilSMS: manpower status SMS skipped - requester has no valid phone.', [
                'request_id' => $request->id,
                'raw_phone' => $phone,
            ]);
            return;
        }

        $status = strtolower((string) $request->status);
        $code = $this->formatRequestCode($request);
        $schedule = $this->formatSchedule($request);

        $message = null;

        if ($status === 'validated') {
            $message = sprintf(
                'GSO IBIMS: Request %s Validated. Please print the Request Form, Ensure the form is signed before submission.',
                $code
            );
        } elseif ($status === 'approved') {
            $approvedQty = (int) ($request->approved_quantity ?? $request->quantity ?? 0);
            if ($approvedQty < 1) {
                $approvedQty = 1;
            }
                $message = sprintf(
                    'GSO IBIMS: Request %s Approved. Approved quantity: %d. Schedule: %s.',
                $code,
                $approvedQty,
                $schedule
            );
        } elseif ($status === 'rejected') {
            $subject = trim((string) $request->rejection_reason_subject);
            if ($subject === '') {
                $subject = 'No reason provided';
            }

            $message = sprintf(
                'GSO IBIMS: Request %s Rejected. Reason: %s.',
                $code,
                $subject
            );
        }

        if (! $message) {
            return;
        }

        foreach ($numbers as $recipient) {
            $this->sendSms($recipient, $message);
        }
    }

    protected function sendSms(string $recipient, string $message): void
    {
        $apiKey = trim((string) Config::get('services.philsms.api_key'));
        $baseUrl = rtrim(trim((string) Config::get('services.philsms.base_url', '')), '/');
        $senderId = $this->normalizeSenderId(Config::get('services.philsms.sender_id'));
        $fallbackSenderId = $this->normalizeSenderId(Config::get('services.philsms.fallback_sender_id'));

        if (! $apiKey || ! $baseUrl) {
            Log::warning('PhilSMS: missing credentials or base URL; SMS not sent.', [
                'has_api_key' => (bool) $apiKey,
                'base_url' => $baseUrl,
            ]);
            return;
        }

        $endpoint = $baseUrl . '/sms/send';

        $attemptSenders = [];
        if ($senderId) {
            $attemptSenders[] = $senderId;
        }
        if ($fallbackSenderId && (! $senderId || strcasecmp($senderId, $fallbackSenderId) !== 0)) {
            $attemptSenders[] = $fallbackSenderId;
        }
        if (! count($attemptSenders)) {
            $attemptSenders[] = null;
        }

        $lastResponse = null;

        foreach ($attemptSenders as $index => $sender) {
            Log::info('PhilSMS: attempting to send SMS.', [
                'recipient' => $recipient,
                'sender' => $sender,
            ]);

            $response = $this->dispatchSms($endpoint, $apiKey, $recipient, $message, $sender);
            $lastResponse = $response;

            if ($response['ok']) {
                Log::info('PhilSMS: message sent successfully.', [
                    'recipient' => $recipient,
                    'sender' => $sender,
                ]);
                return;
            }

            if ($index === 0 && $sender && $this->isSenderUnauthorized($response)) {
                Log::warning('PhilSMS: sender ID not authorized, attempting next sender.', [
                    'recipient' => $recipient,
                    'sender' => $sender,
                ]);
                continue;
            }

            if ($index < count($attemptSenders) - 1) {
                Log::info('PhilSMS: retrying SMS with alternate sender.', [
                    'recipient' => $recipient,
                    'previous_sender' => $sender,
                ]);
            }
        }

        if ($lastResponse) {
            Log::error('PhilSMS: failed to send message.', [
                'recipient' => $recipient,
                'status' => $lastResponse['status'],
                'body' => $lastResponse['body'],
            ]);
        }
    }

    protected function formatSchedule(ManpowerRequest $request): string
    {
        $start = $request->start_at;
        $end = $request->end_at;

        if ($start && $end) {
            if ($start->isSameDay($end)) {
                return $start->format('M d, Y');
            }

            if ($start->format('Y-m') === $end->format('Y-m')) {
                return sprintf('%s %d-%d, %d',
                    $start->format('M'),
                    $start->day,
                    $end->day,
                    $end->year
                );
            }

            if ($start->year === $end->year) {
                return sprintf('%s %d - %s %d, %d',
                    $start->format('M'),
                    $start->day,
                    $end->format('M'),
                    $end->day,
                    $end->year
                );
            }

            return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
        }

        if ($start) {
            return $start->format('M d, Y');
        }

        if ($end) {
            return $end->format('M d, Y');
        }

        return 'unscheduled';
    }

    protected function adminNumbers(): array
    {
        $raw = Config::get('services.philsms.admin_numbers');

        if (is_array($raw)) {
            $numbers = $raw;
        } elseif (is_string($raw)) {
            $numbers = array_map('trim', explode(',', $raw));
        } else {
            $numbers = [];
        }

        $numbers = [];

        $dbNumbers = User::query()
            ->where('role', 'admin')
            ->whereNotNull('phone')
            ->pluck('phone')
            ->all();

        if ($dbNumbers) {
            $numbers = array_merge($numbers, $dbNumbers);
        }

        $raw = Config::get('services.philsms.admin_numbers');

        if (is_array($raw)) {
            $numbers = array_merge($numbers, $raw);
        } elseif (is_string($raw) && $raw !== '') {
            $fragments = preg_split('/[\n,;]+/', $raw);
            $numbers = array_merge($numbers, array_map('trim', array_filter($fragments)));
        }

        if (! $numbers) {
            return [];
        }

        return $this->normalizeNumbers($numbers);
    }

    protected function normalizeNumbers(array $numbers): array
    {
        $normalized = [];

        foreach ($numbers as $number) {
            $clean = preg_replace('/[^0-9+]/', '', (string) $number);
            if (! $clean) {
                continue;
            }

            if ($clean[0] === '+') {
                $digits = substr($clean, 1);
            } else {
                $digits = $clean;
            }

            if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = '63' . substr($digits, 1);
            } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
                $digits = '63' . $digits;
            }

            if (! str_starts_with($digits, '63')) {
                // Fallback to original clean format if we cannot infer country code.
                $digits = ltrim($clean, '+');
            }

            $normalized[] = '+' . $digits;
        }

        return array_values(array_unique(array_filter($normalized)));
    }

    protected function formatBorrowWindow(BorrowRequest $request): string
    {
        $start = $request->borrow_date;
        $end = $request->return_date;

        if ($start && $end) {
            if ($start->equalTo($end)) {
                return $start->format('M d, Y');
            }

            if ($start->format('Y-m') === $end->format('Y-m')) {
                return sprintf('%s %d-%d, %d',
                    $start->format('M'),
                    $start->day,
                    $end->day,
                    $end->year
                );
            }

            if ($start->year === $end->year) {
                return sprintf('%s %d - %s %d, %d',
                    $start->format('M'),
                    $start->day,
                    $end->format('M'),
                    $end->day,
                    $end->year
                );
            }

            return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
        }

        if ($start) {
            return $start->format('M d, Y');
        }

        if ($end) {
            return $end->format('M d, Y');
        }

        return 'unscheduled';
    }

    protected function formatBorrowStartDate(BorrowRequest $request): ?string
    {
        $start = $request->borrow_date;
        if ($start) {
            return $start->format('M. d, Y');
        }

        return null;
    }

    protected function formatBorrowRequestCode(BorrowRequest $request): string
    {
        $formatted = trim((string) ($request->formatted_request_id ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        if ($request->id) {
            return 'BR-' . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT);
        }

        return 'borrow request';
    }

    protected function resolveBorrowRequesterName(BorrowRequest $request): string
    {
        $user = $request->user;
        if (! $user) {
            return 'A user';
        }

        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));

        if ($first !== '' || $last !== '') {
            return trim($first . ' ' . $last);
        }

        $fullName = trim((string) ($user->full_name ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            return $email;
        }

        return 'A user';
    }

    protected function formatRequestCode(ManpowerRequest $request): string
    {
        $formatted = trim((string) ($request->formatted_request_id ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        if ($request->id) {
            return 'MP-' . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT);
        }

        return 'manpower request';
    }

    protected function limitText(string $value, int $limit = 120): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $value));
        if (strlen($clean) <= $limit) {
            return $clean;
        }

        return substr($clean, 0, max(0, $limit - 3)) . '...';
    }

    protected function isEnabled(): bool
    {
        return (bool) Config::get('services.philsms.api_key');
    }

    protected function dispatchSms(string $endpoint, string $apiKey, string $recipient, string $message, ?string $senderId): array
    {
        $payload = [
            'recipient' => $recipient,
            'message' => $message,
        ];

        if ($senderId) {
            $payload['sender_id'] = $senderId;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post($endpoint, $payload);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => $e->getMessage(),
                'json' => null,
            ];
        }
    }

    protected function normalizeSenderId($value): ?string
    {
        $senderId = trim((string) $value);
        if ($senderId === '') {
            return null;
        }

        return $senderId;
    }

    protected function isSenderUnauthorized(array $response): bool
    {
        if ((int) ($response['status'] ?? 0) !== 404) {
            return false;
        }

        $message = '';
        if (is_array($response['json'])) {
            $message = (string) ($response['json']['message'] ?? '');
        }

        if (! $message && is_string($response['body'])) {
            $message = $response['body'];
        }

        return stripos($message, 'not authorized') !== false;
    }

    protected function resolveRequesterName(ManpowerRequest $request): string
    {
        $user = $request->user;
        if (! $user) {
            return 'Unknown requester';
        }

        $first = trim((string) ($user->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $fullName = trim((string) ($user->full_name ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            return $email;
        }

        return 'Unknown requester';
    }
}
