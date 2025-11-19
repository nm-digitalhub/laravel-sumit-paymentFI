<?php

namespace Sumit\LaravelPayment\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Sumit\LaravelPayment\Models\Transaction;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'transaction_id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'authorized' => 'info',
                        'cancelled' => 'gray',
                        'active' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'authorized' => 'Authorized',
                        'cancelled' => 'Cancelled',
                        'active' => 'Active',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'payment' => 'Payment',
                        'subscription' => 'Subscription',
                        'refund' => 'Refund',
                    ]),
            ])
            ->headerActions([
                // Not allowing creation of transactions from here
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Transaction $record): string => route('filament.admin.resources.transactions.view', ['record' => $record])),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('created_at', 'desc');
    }
}
