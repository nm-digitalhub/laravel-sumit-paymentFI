<?php

namespace NmDigitalHub\LaravelOfficeGuy\Models;

use Illuminate\Database\Eloquent\Model;

class StockSyncLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'officeguy_stock_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'external_identifier',
        'product_name',
        'old_stock',
        'new_stock',
        'status',
        'error_message',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'old_stock' => 'integer',
        'new_stock' => 'integer',
        'synced_at' => 'datetime',
    ];

    /**
     * Scope a query to only include successful syncs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to get logs for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
