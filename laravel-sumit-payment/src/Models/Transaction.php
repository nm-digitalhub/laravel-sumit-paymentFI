<?php

namespace NmDigitalHub\LaravelSumitPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'payment_id',
        'auth_number',
        'amount',
        'first_payment_amount',
        'non_first_payment_amount',
        'currency',
        'payments_count',
        'status',
        'status_description',
        'valid_payment',
        'payment_token_id',
        'card_last_four',
        'card_expiry_month',
        'card_expiry_year',
        'document_id',
        'customer_id',
        'is_subscription',
        'is_test',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'first_payment_amount' => 'decimal:2',
        'non_first_payment_amount' => 'decimal:2',
        'valid_payment' => 'boolean',
        'is_subscription' => 'boolean',
        'is_test' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('sumit-payment.tables.transactions', 'sumit_transactions');
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Get the payment token used for this transaction.
     */
    public function paymentToken(): BelongsTo
    {
        return $this->belongsTo(PaymentToken::class);
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include valid payments.
     */
    public function scopeValid($query)
    {
        return $query->where('valid_payment', true);
    }

    /**
     * Check if the transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->valid_payment;
    }

    /**
     * Check if the transaction is a subscription payment.
     */
    public function isSubscription(): bool
    {
        return $this->is_subscription;
    }
}
