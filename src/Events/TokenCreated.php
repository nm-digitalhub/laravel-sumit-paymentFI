<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sumit\LaravelPayment\DTO\TokenData;

/**
 * Token Created Event
 * 
 * Fired when a payment token is created.
 * This event uses DTOs and is completely model-agnostic.
 */
class TokenCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly TokenData $tokenData,
        public readonly mixed $userId
    ) {}
}
