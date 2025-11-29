<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class RequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // payload is an array with keys: type, message, borrow_request_id, items, borrow_date, return_date, reason, actor etc
    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        // small normalization
        $this->payload['created_at'] = now()->toDateTimeString();
    }

    public function via($notifiable)
    {
        // persist, and broadcast
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        // Stored in `data` column of notifications table
        return $this->payload;
    }

    public function toArray($notifiable)
    {
        // fallback
        return $this->payload;
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->payload);
    }

    // Optional: set a custom broadcast type (helps Echo handlers)
    public function broadcastType(): string
    {
        $type = strtolower((string) ($this->payload['type'] ?? ''));
        if ($type === 'overdue') {
            return 'request.overdue';
        }
        return 'request.notification';
    }
}
