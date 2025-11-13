<?php

namespace NmDigitalHub\LaravelOfficeGuy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentToken extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'officeguy_payment_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token',
        'card_type',
        'last_four',
        'expiry_month',
        'expiry_year',
        'card_pattern',
        'citizen_id',
        'brand',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * The attributes that should be hidden.
     *
     * @var array
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the payments associated with this token.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'token_id');
    }

    /**
     * Scope a query to only include default tokens.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to get tokens for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Set this token as the default for the user.
     */
    public function setAsDefault(): void
    {
        // Remove default from all other tokens for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this token as default
        $this->update(['is_default' => true]);
    }

    /**
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        $expiryDate = sprintf('%s-%s-01', $this->expiry_year, $this->expiry_month);
        $expiry = \Carbon\Carbon::parse($expiryDate)->endOfMonth();
        
        return $expiry->isPast();
    }

    /**
     * Get the masked card number.
     */
    public function getMaskedCardAttribute(): string
    {
        return $this->card_pattern ?? '**** **** **** ' . $this->last_four;
    }

    /**
     * Get formatted expiry date.
     */
    public function getFormattedExpiryAttribute(): string
    {
        return sprintf('%s/%s', $this->expiry_month, substr($this->expiry_year, -2));
    }
}
