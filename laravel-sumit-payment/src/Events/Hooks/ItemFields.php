<?php

namespace NmDigitalHub\LaravelSumitPayment\Events\Hooks;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Hook event for customizing item fields.
 * 
 * Similar to WooCommerce's 'sumit_item_fields' filter.
 * 
 * Usage in listener:
 * public function handle(ItemFields $event) {
 *     // Add details to item name
 *     $event->item['Name'] .= ' - ' . $event->product['sku'];
 *     
 *     // Remove zero priced items
 *     if ($event->unitPrice == 0) {
 *         $event->removeItem();
 *     }
 * }
 */
class ItemFields
{
    use Dispatchable;

    public ?array $item;
    public array $product;
    public float $unitPrice;
    public array $orderItemData;
    public array $orderData;
    protected bool $shouldRemove = false;

    /**
     * Create a new event instance.
     */
    public function __construct(
        array $item,
        array $product,
        float $unitPrice,
        array $orderItemData,
        array $orderData
    ) {
        $this->item = $item;
        $this->product = $product;
        $this->unitPrice = $unitPrice;
        $this->orderItemData = $orderItemData;
        $this->orderData = $orderData;
    }

    /**
     * Get the modified item data.
     */
    public function getValue(): ?array
    {
        return $this->shouldRemove ? null : $this->item;
    }

    /**
     * Set an item field.
     */
    public function setField(string $key, $value): void
    {
        if ($this->item !== null) {
            $this->item[$key] = $value;
        }
    }

    /**
     * Mark this item for removal.
     */
    public function removeItem(): void
    {
        $this->shouldRemove = true;
    }

    /**
     * Check if item should be removed.
     */
    public function shouldRemove(): bool
    {
        return $this->shouldRemove;
    }
}
