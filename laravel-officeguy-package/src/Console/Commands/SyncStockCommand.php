<?php

namespace NmDigitalHub\LaravelOfficeGuy\Console\Commands;

use Illuminate\Console\Command;
use NmDigitalHub\LaravelOfficeGuy\Services\StockService;

class SyncStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officeguy:sync-stock {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize stock/inventory from SUMIT API';

    /**
     * Execute the console command.
     */
    public function handle(StockService $stockService)
    {
        $this->info('Starting stock synchronization...');

        $forceSync = $this->option('force');

        if ($forceSync) {
            $this->warn('Force sync enabled - ignoring recent sync check');
        }

        $result = $stockService->updateStock($forceSync);

        if ($result['success']) {
            if (isset($result['skipped']) && $result['skipped']) {
                $this->info($result['message']);
            } else {
                $this->info('Stock synchronization completed successfully!');
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Total Items', $result['total'] ?? 0],
                        ['Updated', $result['updated'] ?? 0],
                        ['Failed', $result['failed'] ?? 0],
                    ]
                );
            }

            return Command::SUCCESS;
        } else {
            $this->error('Stock synchronization failed: ' . ($result['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }
}
