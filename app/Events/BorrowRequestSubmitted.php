<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Models\BorrowRequest;

class BorrowRequestSubmitted implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public BorrowRequest $borrowRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(BorrowRequest $borrowRequest)
    {
        // Keep a serialized copy of the important payload
        $this->borrowRequest = $borrowRequest->load('user');
    }

    /**
     * Channels to broadcast on: admin + the owner user
     * -> private-admin
     * -> private-user.{id}
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('user.' . $this->borrowRequest->user_id),
        ];
    }

    /**
     * Payload sent to clients.
     */
    public function broadcastWith(): array
    {
        $user = $this->borrowRequest->user;

        return [
            'id' => $this->borrowRequest->id,
            'user_id' => $this->borrowRequest->user_id,
            'user_name' => $user ? trim($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown',
            'borrow_date' => (string) $this->borrowRequest->borrow_date,
            'return_date' => (string) $this->borrowRequest->return_date,
            'message' => "New borrow request #{$this->borrowRequest->id} submitted",
            'status' => $this->borrowRequest->status,
        ];
    }

    /**
     * Short, friendly event name for Echo listeners.
     */
    public function broadcastAs(): string
    {
        return 'BorrowRequestSubmitted';
    }
}
