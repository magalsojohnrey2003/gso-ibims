<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Models\BorrowRequest;

class BorrowRequestStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public BorrowRequest $borrowRequest;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(BorrowRequest $borrowRequest, string $oldStatus, string $newStatus)
    {
        $this->borrowRequest = $borrowRequest->load('user');
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        // broadcast to the owner and also to admin channel (admins may need to see)
        return [
            new PrivateChannel('user.' . $this->borrowRequest->user_id),
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastWith(): array
    {
        $user = $this->borrowRequest->user;
        return [
            'id' => $this->borrowRequest->id,
            'user_id' => $this->borrowRequest->user_id,
            'user_name' => $user ? trim($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown',
            'message' => "Request #{$this->borrowRequest->id} changed from {$this->oldStatus} → {$this->newStatus}",
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }

    public function broadcastAs(): string
    {
        return 'BorrowRequestStatusUpdated';
    }
}
