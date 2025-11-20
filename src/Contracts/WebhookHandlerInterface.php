<?php

namespace Sumit\LaravelPayment\Contracts;

use Illuminate\Http\Request;

/**
 * Webhook Handler Interface
 * 
 * Defines the contract for webhook handler implementations.
 * Users can implement this interface to create custom webhook handlers.
 */
interface WebhookHandlerInterface
{
    /**
     * Handle incoming webhook
     *
     * @param Request $request Webhook request
     * @return array Response data
     */
    public function handle(Request $request): array;

    /**
     * Verify webhook signature
     *
     * @param Request $request Webhook request
     * @return bool True if signature is valid
     */
    public function verifySignature(Request $request): bool;

    /**
     * Parse webhook payload
     *
     * @param Request $request Webhook request
     * @return array Parsed payload
     */
    public function parsePayload(Request $request): array;
}
