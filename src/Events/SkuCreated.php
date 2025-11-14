<?php

namespace Gowelle\SkuGenerator\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new SKU is created.
 */
class SkuCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model for which the SKU was created
     * @param string $sku The generated SKU
     * @param string|null $reason Optional reason for the SKU creation
     */
    public function __construct(
        public Model $model,
        public string $sku,
        public ?string $reason = null
    ) {
    }
}
