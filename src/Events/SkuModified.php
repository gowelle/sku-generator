<?php

namespace Gowelle\SkuGenerator\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a SKU is manually modified.
 */
class SkuModified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model for which the SKU was modified
     * @param string $oldSku The previous SKU value
     * @param string $newSku The new SKU value
     * @param string|null $reason Optional reason for modification
     */
    public function __construct(
        public Model $model,
        public string $oldSku,
        public string $newSku,
        public ?string $reason = null
    ) {
    }
}
