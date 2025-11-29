<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\BorrowRequest;
use App\Services\PhilSmsService;
use App\Notifications\RequestNotification;
use Carbon\Carbon;

class SendOverdueNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrow:send-overdue-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily overdue notifications (SMS + in-app) to borrowers for delivered requests that are past return date.';

    public function handle()
    {
        $this->info('Starting overdue notification job...');

        $philSms = app(PhilSmsService::class);

        $today = Carbon::now()->startOfDay();

        $overdueRequests = BorrowRequest::with('user')
            ->where(function ($q) {
                $q->where('delivery_status', 'delivered')
                  ->orWhere('status', 'delivered');
            })
            ->where('status', '<>', 'returned')
            ->whereDate('return_date', '<', $today->toDateString())
            ->get();

        $count = $overdueRequests->count();
        $this->info("Found {$count} overdue request(s).");

        foreach ($overdueRequests as $req) {
            try {
                $user = $req->user;
                if (! $user) {
                    Log::warning('Overdue notification skipped - borrower missing', ['borrow_request_id' => $req->id]);
                    continue;
                }

                $returnAt = $req->return_date ? Carbon::parse($req->return_date)->startOfDay() : null;
                $daysOverdue = $returnAt ? $returnAt->diffInDays($today) : 0;
                if ($daysOverdue <= 0) {
                    // Shouldn't happen because of query, but guard anyway
                    continue;
                }

                // Send SMS using the existing service (new method added)
                try {
                    if (method_exists($philSms, 'notifyBorrowerOverdue')) {
                        $philSms->notifyBorrowerOverdue($req, $daysOverdue);
                    } else {
                        Log::info('PhilSmsService::notifyBorrowerOverdue not available; skipping SMS', ['borrow_request_id' => $req->id]);
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to send overdue SMS', ['borrow_request_id' => $req->id, 'error' => $e->getMessage()]);
                }

                // Send in-app notification (database + broadcast)
                $code = $req->formatted_request_id ?: ('BR-' . str_pad((string) $req->id, 4, '0', STR_PAD_LEFT));
                $message = sprintf('Request %s is overdue by %d day%s.', $code, $daysOverdue, $daysOverdue === 1 ? '' : 's');

                $payload = [
                    'type' => 'overdue',
                    'message' => $message,
                    'borrow_request_id' => $req->id,
                    'formatted_request_id' => $code,
                    'days_overdue' => $daysOverdue,
                    'borrow_date' => (string) $req->borrow_date,
                    'return_date' => (string) $req->return_date,
                    'actor_id' => null,
                ];

                try {
                    Notification::send($user, new RequestNotification($payload));
                } catch (\Throwable $e) {
                    Log::error('Failed to send overdue in-app notification', ['borrow_request_id' => $req->id, 'error' => $e->getMessage()]);
                }

                $this->info("Notified request {$req->id} (days overdue: {$daysOverdue})");
            } catch (\Throwable $e) {
                Log::error('Overdue notification loop error', ['borrow_request_id' => $req->id ?? null, 'error' => $e->getMessage()]);
            }
        }

        $this->info('Overdue notification job completed.');
        return 0;
    }
}
