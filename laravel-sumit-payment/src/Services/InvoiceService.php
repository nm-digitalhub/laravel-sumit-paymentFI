<?php

namespace NmDigitalHub\LaravelSumitPayment\Services;

use NmDigitalHub\LaravelSumitPayment\Models\Document;
use NmDigitalHub\LaravelSumitPayment\Events\InvoiceCreated;

class InvoiceService
{
    protected SumitApiService $api;

    /**
     * Create a new InvoiceService instance.
     */
    public function __construct(SumitApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Create an invoice/receipt.
     */
    public function createInvoice(array $invoiceData): array
    {
        $request = $this->buildInvoiceRequest($invoiceData);
        
        $response = $this->api->post('/accounting/documents/create/', $request, false);

        if ($response['Status'] == 0) {
            $document = Document::create([
                'document_id' => $response['Data']['DocumentID'],
                'order_id' => $invoiceData['order_id'],
                'customer_id' => $response['Data']['CustomerID'] ?? null,
                'type' => $invoiceData['type'] ?? 'invoice',
                'language' => $invoiceData['language'] ?? '',
                'currency' => $invoiceData['currency'] ?? 'ILS',
                'total_amount' => $invoiceData['total_amount'],
                'vat_rate' => $invoiceData['vat_rate'] ?? null,
                'vat_included' => $invoiceData['vat_included'] ?? true,
                'is_draft' => $invoiceData['is_draft'] ?? false,
                'sent_by_email' => $invoiceData['sent_by_email'] ?? false,
                'description' => $invoiceData['description'] ?? '',
                'items' => $invoiceData['items'] ?? null,
                'metadata' => $invoiceData['metadata'] ?? null,
            ]);

            event(new InvoiceCreated($document, $invoiceData['order_id']));

            return [
                'success' => true,
                'document' => $document,
                'document_id' => $response['Data']['DocumentID'],
                'customer_id' => $response['Data']['CustomerID'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response['UserErrorMessage'] ?? 'Invoice creation failed',
        ];
    }

    /**
     * Build invoice request.
     */
    protected function buildInvoiceRequest(array $invoiceData): array
    {
        $request = [
            'Items' => $invoiceData['items'],
            'VATIncluded' => 'true',
            'VATRate' => $invoiceData['vat_rate'] ?? '',
            'Details' => [
                'IsDraft' => ($invoiceData['is_draft'] ?? config('sumit-payment.draft_document')) ? 'true' : 'false',
                'Customer' => $invoiceData['customer'],
                'Language' => $invoiceData['language'] ?? $this->getDocumentLanguage(),
                'Currency' => $invoiceData['currency'] ?? 'ILS',
                'Description' => $invoiceData['description'] ?? '',
                'Type' => $this->getDocumentType($invoiceData['type'] ?? 'invoice'),
            ],
        ];

        if (($invoiceData['sent_by_email'] ?? config('sumit-payment.email_document'))) {
            $request['Details']['SendByEmail'] = ['Original' => 'true'];
        }

        if (isset($invoiceData['payments'])) {
            $request['Payments'] = $invoiceData['payments'];
        }

        return $request;
    }

    /**
     * Create order document.
     */
    public function createOrderDocument(string $orderId, string $customerId, string $originalDocumentId, array $orderData): array
    {
        $request = [
            'Items' => $orderData['items'],
            'VATIncluded' => 'true',
            'VATRate' => $orderData['vat_rate'] ?? '',
            'Details' => [
                'Customer' => ['ID' => $customerId],
                'IsDraft' => config('sumit-payment.draft_document') ? 'true' : 'false',
                'Language' => $orderData['language'] ?? $this->getDocumentLanguage(),
                'Currency' => $orderData['currency'] ?? 'ILS',
                'Type' => '8', // Order document type
                'Description' => $orderData['description'] ?? "Order #$orderId",
            ],
            'OriginalDocumentID' => $originalDocumentId,
        ];

        $response = $this->api->post('/accounting/documents/create/', $request, false);

        if ($response['Status'] == 0) {
            $document = Document::create([
                'document_id' => $response['Data']['DocumentID'],
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'type' => 'order',
                'language' => $orderData['language'] ?? '',
                'currency' => $orderData['currency'] ?? 'ILS',
                'total_amount' => $orderData['total_amount'] ?? 0,
                'vat_rate' => $orderData['vat_rate'] ?? null,
                'is_draft' => config('sumit-payment.draft_document'),
                'description' => $orderData['description'] ?? '',
                'items' => $orderData['items'] ?? null,
            ]);

            return [
                'success' => true,
                'document' => $document,
                'document_id' => $response['Data']['DocumentID'],
            ];
        }

        return [
            'success' => false,
            'error' => $response['UserErrorMessage'] ?? 'Order document creation failed',
        ];
    }

    /**
     * Get document language.
     */
    protected function getDocumentLanguage(): string
    {
        if (!config('sumit-payment.automatic_languages')) {
            return '';
        }

        $locale = app()->getLocale();
        
        return match ($locale) {
            'en', 'en_US' => 'English',
            'ar', 'ar_AR' => 'Arabic',
            'es', 'es_ES' => 'Spanish',
            'he', 'he_IL' => 'Hebrew',
            default => '',
        };
    }

    /**
     * Get document type code.
     */
    protected function getDocumentType(string $type): string
    {
        return match ($type) {
            'invoice' => '1',
            'receipt' => '1',
            'order' => '8',
            'donation_receipt' => 'DonationReceipt',
            default => '1',
        };
    }

    /**
     * Get document by ID.
     */
    public function getDocument(string $documentId): ?Document
    {
        return Document::where('document_id', $documentId)->first();
    }

    /**
     * Get documents by order ID.
     */
    public function getOrderDocuments(string $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return Document::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
