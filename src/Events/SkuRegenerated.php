<?php

namespace Gowelle\SkuGenerator\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a SKU is regenerated.
 */
class SkuRegenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model for which the SKU was regenerated
     * @param string $oldSku The previous SKU value
     * @param string $newSku The new SKU value
     * @param string|null $reason Optional reason for regeneration
     */
    public function __construct(
        public Model $model,
        public string $oldSku,
        public string $newSku,
        public ?string $reason = null
    ) {
    }
}
