<?php

namespace NmDigitalHub\LaravelSumitPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentToken extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'card_type',
        'last_four',
        'expiry_month',
        'expiry_year',
        'citizen_id',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('sumit-payment.tables.payment_tokens', 'sumit_payment_tokens');
    }

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scope a query to only include default tokens.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include tokens for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the masked card number.
     */
    public function getMaskedCardAttribute(): string
    {
        return '****' . $this->last_four;
    }

    /**
     * Get the expiry date in MM/YY format.
     */
    public function getExpiryAttribute(): string
    {
        return $this->expiry_month . '/' . substr($this->expiry_year, -2);
    }
}
