<?php

namespace NmDigitalHub\LaravelOfficeGuy\Console\Commands;

use Illuminate\Console\Command;
use NmDigitalHub\LaravelOfficeGuy\Services\OfficeGuyApiService;

class TestCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officeguy:test-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SUMIT API credentials';

    /**
     * Execute the console command.
     */
    public function handle(OfficeGuyApiService $apiService)
    {
        $this->info('Testing SUMIT API credentials...');
        $this->newLine();

        // Display current configuration
        $this->table(
            ['Setting', 'Value'],
            [
                ['Company ID', config('officeguy.company_id') ?: 'Not set'],
                ['Environment', config('officeguy.environment')],
                ['Private Key', config('officeguy.api_private_key') ? '***' . substr(config('officeguy.api_private_key'), -4) : 'Not set'],
                ['Public Key', config('officeguy.api_public_key') ? '***' . substr(config('officeguy.api_public_key'), -4) : 'Not set'],
            ]
        );

        $this->newLine();

        // Test private credentials
        $this->info('Testing private API key...');
        $privateResult = $apiService->checkCredentials();

        if ($privateResult === null) {
            $this->info('✓ Private API credentials are valid');
        } else {
            $this->error('✗ Private API credentials failed: ' . $privateResult);
        }

        $this->newLine();

        // Test public credentials
        $this->info('Testing public API key...');
        $publicResult = $apiService->checkPublicCredentials();

        if ($publicResult === null) {
            $this->info('✓ Public API credentials are valid');
        } else {
            $this->error('✗ Public API credentials failed: ' . $publicResult);
        }

        $this->newLine();

        if ($privateResult === null && $publicResult === null) {
            $this->info('All credentials are valid! You can start using the package.');
            return Command::SUCCESS;
        } else {
            $this->error('Credential validation failed. Please check your configuration.');
            return Command::FAILURE;
        }
    }
}
