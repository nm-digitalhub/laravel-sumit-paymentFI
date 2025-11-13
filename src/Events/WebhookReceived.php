<?php

namespace Sumit\LaravelPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public string $eventType;
    public array $data;

    public function __construct(string $eventType, array $data)
    {
        $this->eventType = $eventType;
        $this->data = $data;
    }
}
