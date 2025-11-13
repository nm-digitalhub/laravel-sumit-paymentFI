<?php

namespace Sumit\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'transaction_id',
        'payment_method',
        'amount',
        'currency',
        'status',
        'type',
        'payments_count',
        'description',
        'document_id',
        'document_type',
        'customer_id',
        'authorization_number',
        'last_four_digits',
        'is_subscription',
        'is_donation',
        'payment_token_id',
        'refund_amount',
        'refund_status',
        'metadata',
        'error_message',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'is_subscription' => 'boolean',
        'is_donation' => 'boolean',
        'metadata' => 'array',
        'processed_at' => 'datetime',
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
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'processing';
    }

    /**
     * Check if transaction has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed' || $this->status === 'cancelled';
    }

    /**
     * Mark transaction as completed.
     */
    public function markAsCompleted(string $transactionId = null, string $documentId = null): self
    {
        $this->status = 'completed';
        $this->processed_at = now();
        
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        
        if ($documentId) {
            $this->document_id = $documentId;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed(string $errorMessage = null): self
    {
        $this->status = 'failed';
        $this->processed_at = now();
        
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope a query to only include failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'cancelled']);
    }

    /**
     * Scope a query to only include subscription transactions.
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('is_subscription', true);
    }

    /**
     * Get the payment token associated with the transaction.
     */
    public function paymentToken(): BelongsTo
    {
        return $this->belongsTo(PaymentToken::class);
    }
}
