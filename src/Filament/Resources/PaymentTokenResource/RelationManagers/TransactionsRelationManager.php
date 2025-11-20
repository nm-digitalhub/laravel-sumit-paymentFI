<?php

namespace Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Sumit\LaravelPayment\Models\Transaction;
use Filament\Support\Enums\FontWeight;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'transaction_id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold),
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'payment' => 'primary',
                        'subscription' => 'success',
                        'refund' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Transaction $record): string => 
                        route('filament.admin.resources.transactions.view', ['record' => $record])
                    ),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
