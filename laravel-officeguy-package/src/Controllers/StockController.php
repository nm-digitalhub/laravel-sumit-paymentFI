<?php

namespace NmDigitalHub\LaravelOfficeGuy\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NmDigitalHub\LaravelOfficeGuy\Services\StockService;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Trigger stock synchronization.
     */
    public function sync(Request $request)
    {
        $forceSync = $request->input('force', false);
        
        $result = $this->stockService->updateStock($forceSync);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get stock sync logs.
     */
    public function logs(Request $request)
    {
        $limit = $request->input('limit', 100);
        $productId = $request->input('product_id');

        if ($productId) {
            $logs = $this->stockService->getProductSyncLogs($productId, $limit);
        } else {
            $logs = $this->stockService->getSyncLogs($limit);
        }

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Get stock sync status.
     */
    public function status()
    {
        $isEnabled = $this->stockService->isSyncEnabled();
        $frequency = $this->stockService->getSyncFrequency();
        $lastSync = cache()->get('officeguy_last_stock_sync');

        return response()->json([
            'success' => true,
            'enabled' => $isEnabled,
            'frequency' => $frequency,
            'last_sync' => $lastSync ? date('Y-m-d H:i:s', $lastSync) : null,
        ]);
    }
}
