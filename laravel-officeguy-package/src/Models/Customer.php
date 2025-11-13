<?php

namespace NmDigitalHub\LaravelOfficeGuy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'officeguy_customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'external_id',
        'name',
        'email',
        'phone',
        'citizen_id',
        'vat_number',
        'address',
        'city',
        'zip_code',
        'country',
        'language',
        'receive_emails',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'receive_emails' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the customer record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Scope a query to find customer by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope a query to find customer by external ID.
     */
    public function scopeByExternalId($query, string $externalId)
    {
        return $query->where('external_id', $externalId);
    }

    /**
     * Get full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
