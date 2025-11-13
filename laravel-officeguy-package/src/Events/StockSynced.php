<?php

namespace NmDigitalHub\LaravelOfficeGuy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockSynced
{
    use Dispatchable, SerializesModels;

    public int $updatedCount;
    public int $failedCount;

    /**
     * Create a new event instance.
     */
    public function __construct(int $updatedCount, int $failedCount)
    {
        $this->updatedCount = $updatedCount;
        $this->failedCount = $failedCount;
    }
}
