<?php

namespace App\Services;

use App\Models\ItemInstance;
use App\Models\ItemInstanceEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

class ItemInstanceEventLogger
{
    public function log(
        ItemInstance $instance,
        string $action,
        array $payload = [],
        ?Authenticatable $actor = null,
        ?CarbonImmutable $performedAt = null
    ): ItemInstanceEvent {
        $actorId = null;
        $actorName = null;
        $actorType = 'system';

        if ($actor instanceof User) {
            $actorId = $actor->getAuthIdentifier();
            $actorName = $actor->name;
            $actorType = 'user';
        } elseif ($actor) {
            if (isset($actor->name)) {
                $actorName = $actor->name;
            } elseif (method_exists($actor, 'getAuthIdentifier')) {
                $actorName = (string) $actor->getAuthIdentifier();
            }
            $actorType = method_exists($actor, 'getMorphClass')
                ? $actor->getMorphClass()
                : class_basename($actor);
        }

        return ItemInstanceEvent::create([
            'item_instance_id' => $instance->id,
            'item_id' => $instance->item_id,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'actor_type' => $actorType,
            'action' => $action,
            'payload' => empty($payload) ? null : $payload,
            'performed_at' => ($performedAt ?? CarbonImmutable::now())->toDateTimeString(),
        ]);
    }
}
