<?php

namespace NmDigitalHub\LaravelSumitPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NmDigitalHub\LaravelSumitPayment\Models\Document;

class InvoiceCreated
{
    use Dispatchable, SerializesModels;

    public Document $document;
    public string $orderId;

    /**
     * Create a new event instance.
     */
    public function __construct(Document $document, string $orderId)
    {
        $this->document = $document;
        $this->orderId = $orderId;
    }
}
