<?php

namespace NmDigitalHub\LaravelOfficeGuy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'officeguy_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id',
        'user_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'token_id',
        'request_data',
        'response_data',
        'error_message',
        'document_number',
        'document_type',
        'is_subscription_payment',
        'payments_count',
        'auto_capture',
        'authorize_amount',
        'authorized_at',
        'captured_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'authorize_amount' => 'decimal:2',
        'is_subscription_payment' => 'boolean',
        'auto_capture' => 'boolean',
        'payments_count' => 'integer',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the payment token associated with the payment.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(PaymentToken::class, 'token_id');
    }

    /**
     * Scope a query to only include successful payments.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include subscription payments.
     */
    public function scopeSubscription($query)
    {
        return $query->where('is_subscription_payment', true);
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is authorized.
     */
    public function isAuthorized(): bool
    {
        return $this->status === 'authorized';
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccessful(): void
    {
        $this->update([
            'status' => 'success',
            'captured_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark payment as authorized.
     */
    public function markAsAuthorized(): void
    {
        $this->update([
            'status' => 'authorized',
            'authorized_at' => now(),
        ]);
    }
}
