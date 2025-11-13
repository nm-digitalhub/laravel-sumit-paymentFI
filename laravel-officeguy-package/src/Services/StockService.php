<?php

namespace NmDigitalHub\LaravelOfficeGuy\Services;

use NmDigitalHub\LaravelOfficeGuy\Models\StockSyncLog;
use NmDigitalHub\LaravelOfficeGuy\Events\StockSynced;

class StockService
{
    protected OfficeGuyApiService $apiService;
    protected ?int $lastSyncTime = null;

    public function __construct(OfficeGuyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Update stock from SUMIT API.
     */
    public function updateStock(bool $forceSync = false): array
    {
        // Check if we should skip sync
        if (!$forceSync && $this->shouldSkipSync()) {
            $this->apiService->writeToLog('Stock: Skipping sync (synced recently)', 'debug');
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Sync skipped (synced recently)',
            ];
        }

        try {
            // Prepare request
            $request = [
                'Credentials' => $this->apiService->getCredentials(),
            ];

            // Get stock data from API
            $response = $this->apiService->post(
                $request,
                '/stock/stock/list/',
                false
            );

            if (!$response) {
                throw new \Exception('No response from stock API');
            }

            if (!isset($response['Data']['Stock'])) {
                throw new \Exception('Invalid response format');
            }

            $stockData = $response['Data']['Stock'];
            $updated = 0;
            $failed = 0;

            // Update each product
            foreach ($stockData as $stockItem) {
                try {
                    $result = $this->updateProductStock($stockItem);
                    if ($result) {
                        $updated++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->apiService->writeToLog(
                        'Stock update failed for item: ' . ($stockItem['Name'] ?? 'unknown') . ' - ' . $e->getMessage(),
                        'error'
                    );
                }
            }

            // Update last sync time
            $this->updateLastSyncTime();

            // Fire event
            event(new StockSynced($updated, $failed));

            return [
                'success' => true,
                'updated' => $updated,
                'failed' => $failed,
                'total' => count($stockData),
            ];
        } catch (\Exception $e) {
            $this->apiService->writeToLog('Stock sync error: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update individual product stock.
     */
    protected function updateProductStock(array $stockItem): bool
    {
        $productId = $stockItem['ExternalIdentifier'] ?? null;
        $stockQuantity = $stockItem['Stock'] ?? 0;
        $productName = $stockItem['Name'] ?? '';

        // Log the sync attempt
        $log = StockSyncLog::create([
            'product_id' => $productId ?? 'unknown',
            'external_identifier' => $stockItem['ExternalIdentifier'] ?? null,
            'product_name' => $productName,
            'new_stock' => $stockQuantity,
            'status' => 'pending',
            'synced_at' => now(),
        ]);

        try {
            // Here you would integrate with your product/inventory system
            // This is a placeholder - implement based on your application's needs
            
            // Example: Update via event or direct database update
            // $product = Product::find($productId);
            // if ($product) {
            //     $log->old_stock = $product->stock_quantity;
            //     $product->update(['stock_quantity' => $stockQuantity]);
            // }

            $log->update([
                'status' => 'success',
            ]);

            $this->apiService->writeToLog(
                "Stock: Updated {$productId}: {$stockQuantity}",
                'debug'
            );

            return true;
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if sync should be skipped.
     */
    protected function shouldSkipSync(): bool
    {
        $lastSync = cache()->get('officeguy_last_stock_sync');
        
        if (!$lastSync) {
            return false;
        }

        // Skip if synced within the last hour
        return (time() - $lastSync) < 3600;
    }

    /**
     * Update last sync time.
     */
    protected function updateLastSyncTime(): void
    {
        cache()->put('officeguy_last_stock_sync', time(), 86400); // 24 hours
    }

    /**
     * Get sync frequency from config.
     */
    public function getSyncFrequency(): string
    {
        return config('officeguy.stock.sync_frequency', 'none');
    }

    /**
     * Check if stock sync is enabled.
     */
    public function isSyncEnabled(): bool
    {
        $frequency = $this->getSyncFrequency();
        return $frequency !== 'none';
    }

    /**
     * Get stock sync logs.
     */
    public function getSyncLogs(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return StockSyncLog::orderBy('synced_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get stock sync logs for a specific product.
     */
    public function getProductSyncLogs(string $productId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return StockSyncLog::forProduct($productId)
            ->orderBy('synced_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
