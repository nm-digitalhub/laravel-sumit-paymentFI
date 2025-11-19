<?php

namespace Sumit\LaravelPayment\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Sumit\LaravelPayment\Models\PaymentToken;

class PaymentTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentTokens';

    protected static ?string $recordTitleAttribute = 'card_brand';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('card_type')
                    ->label('Card Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Visa' => 'info',
                        'MasterCard' => 'warning',
                        'American Express' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_four')
                    ->label('Card Ending')
                    ->formatStateUsing(fn (string $state): string => "**** **** **** {$state}"),
                Tables\Columns\TextColumn::make('expiry')
                    ->label('Expiry')
                    ->formatStateUsing(fn ($record): string => 
                        str_pad($record->expiry_month, 2, '0', STR_PAD_LEFT) . '/' . $record->expiry_year
                    ),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_default')
                    ->query(fn ($query) => $query->where('is_default', true))
                    ->label('Default Only'),
                Tables\Filters\Filter::make('is_active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Active Only'),
            ])
            ->headerActions([
                // Not allowing creation of tokens from here
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (PaymentToken $record): string => route('filament.admin.resources.payment-tokens.view', ['record' => $record])),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('created_at', 'desc');
    }
}
