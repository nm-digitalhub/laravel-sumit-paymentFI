<?php

namespace Sumit\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'sumit_customer_id',
        'email',
        'phone',
        'name',
        'company_name',
        'tax_id',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('sumit-payment.tables.customers', 'sumit_customers');
    }

    /**
     * Get the user that owns the customer record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Find customer by SUMIT customer ID.
     */
    public static function findBySumitId(string $sumitCustomerId): ?self
    {
        return static::where('sumit_customer_id', $sumitCustomerId)->first();
    }

    /**
     * Find or create customer by user.
     */
    public static function findOrCreateByUser($userId, array $data = []): self
    {
        $customer = static::where('user_id', $userId)->first();

        if (!$customer) {
            $customer = static::create(array_merge([
                'user_id' => $userId,
            ], $data));
        }

        return $customer;
    }
}
