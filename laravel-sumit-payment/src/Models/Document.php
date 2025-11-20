<?php

namespace NmDigitalHub\LaravelSumitPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'order_id',
        'customer_id',
        'type',
        'language',
        'currency',
        'total_amount',
        'vat_rate',
        'vat_included',
        'is_draft',
        'sent_by_email',
        'description',
        'items',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_included' => 'boolean',
        'is_draft' => 'boolean',
        'sent_by_email' => 'boolean',
        'items' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('sumit-payment.tables.documents', 'sumit_documents');
    }

    /**
     * Scope a query to only include invoices.
     */
    public function scopeInvoices($query)
    {
        return $query->where('type', 'invoice');
    }

    /**
     * Scope a query to only include receipts.
     */
    public function scopeReceipts($query)
    {
        return $query->where('type', 'receipt');
    }

    /**
     * Scope a query to only include draft documents.
     */
    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Check if the document is an invoice.
     */
    public function isInvoice(): bool
    {
        return $this->type === 'invoice';
    }

    /**
     * Check if the document is a receipt.
     */
    public function isReceipt(): bool
    {
        return $this->type === 'receipt';
    }

    /**
     * Check if the document is a credit note.
     */
    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }
}
