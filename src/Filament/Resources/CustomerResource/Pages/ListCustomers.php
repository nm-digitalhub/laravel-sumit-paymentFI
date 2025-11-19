<?php

namespace Sumit\LaravelPayment\Filament\Resources\CustomerResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Sumit\LaravelPayment\Filament\Resources\CustomerResource;
use Sumit\LaravelPayment\Services\CrmSyncService;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncFromCrm')
                ->label('Pull from CRM')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Pull Customers from SUMIT CRM')
                ->modalDescription('This will fetch all customers from SUMIT CRM and sync them to your local database.')
                ->action(function (CrmSyncService $crmSync) {
                    $result = $crmSync->pullCustomersFromCrm();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Customers synced successfully')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('bidirectionalSync')
                ->label('Bidirectional Sync')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Bidirectional CRM Synchronization')
                ->modalDescription('This will sync customers both ways: pull from SUMIT CRM and push local changes.')
                ->action(function (CrmSyncService $crmSync) {
                    $result = $crmSync->bidirectionalSync();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Bidirectional sync completed')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync completed with issues')
                            ->body($result['message'])
                            ->warning()
                            ->send();
                    }
                }),
            
            Actions\CreateAction::make(),
        ];
    }
}
