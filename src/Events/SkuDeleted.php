<?php

namespace Gowelle\SkuGenerator\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a model with a SKU is deleted.
 */
class SkuDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model that was deleted
     * @param string $sku The SKU of the deleted model
     * @param string|null $reason Optional reason for deletion
     */
    public function __construct(
        public Model $model,
        public string $sku,
        public ?string $reason = null
    ) {
    }
}
