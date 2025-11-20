<?php

namespace Sumit\LaravelPayment\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\Pages;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\RelationManagers;
use Sumit\LaravelPayment\Models\PaymentToken;
use Sumit\LaravelPayment\Services\PaymentService;

class PaymentTokenResource extends Resource
{
    protected static ?string $model = PaymentToken::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Payment Gateway';

    protected static ?string $navigationLabel = 'Payment Tokens';

    protected static ?string $modelLabel = 'Payment Token';

    protected static ?string $pluralModelLabel = 'Payment Tokens';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Token Details')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('token')
                            ->label('Token')
                            ->disabled(),
                        Forms\Components\TextInput::make('last_four')
                            ->label('Card Last 4 Digits')
                            ->disabled(),
                        Forms\Components\TextInput::make('card_type')
                            ->label('Card Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('cardholder_name')
                            ->label('Cardholder Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('expiry_month')
                            ->label('Expiry Month')
                            ->disabled(),
                        Forms\Components\TextInput::make('expiry_year')
                            ->label('Expiry Year')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Token Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Payment Method'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Token Metadata')
                            ->disabled(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable()
                    ->searchable(),
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
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Uses')
                    ->counts('transactions')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transactions_sum_amount')
                    ->label('Total Charged')
                    ->sum('transactions', 'amount')
                    ->money('ILS')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_default')
                    ->query(fn ($query) => $query->where('is_default', true))
                    ->label('Default Tokens Only'),
                Tables\Filters\Filter::make('is_active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Active Tokens Only'),
                Tables\Filters\SelectFilter::make('card_brand')
                    ->options([
                        'Visa' => 'Visa',
                        'MasterCard' => 'MasterCard',
                        'American Express' => 'American Express',
                        'Diners' => 'Diners',
                        'Discover' => 'Discover',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('charge')
                    ->label('Charge Token')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->prefix('₪')
                            ->helperText('Amount to charge in ILS'),
                        Forms\Components\TextInput::make('order_id')
                            ->label('Order ID')
                            ->maxLength(255)
                            ->helperText('Optional order identifier'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Payment description'),
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_email')
                            ->label('Customer Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Customer Phone')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->action(function (PaymentToken $record, array $data) {
                        $paymentService = app(PaymentService::class);
                        
                        $paymentData = [
                            'user_id' => $record->user_id,
                            'amount' => $data['amount'],
                            'currency' => 'ILS',
                            'order_id' => $data['order_id'] ?? null,
                            'description' => $data['description'] ?? 'Payment via saved token',
                            'customer_name' => $data['customer_name'] ?? '',
                            'customer_email' => $data['customer_email'] ?? '',
                            'customer_phone' => $data['customer_phone'] ?? null,
                        ];
                        
                        $result = $paymentService->processPaymentWithToken($paymentData, $record->id);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Processed')
                                ->body('Payment of ₪' . number_format($data['amount'], 2) . ' charged successfully.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Payment Failed')
                                ->body($result['message'] ?? 'Failed to process payment.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalDescription(fn (PaymentToken $record): string => 
                        "Charge payment to card ending in {$record->last_four}?"
                    )
                    ->visible(fn (PaymentToken $record) => 
                        !$record->isExpired() && $record->is_active
                    ),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTokens::route('/'),
            'view' => Pages\ViewPaymentToken::route('/{record}'),
            'edit' => Pages\EditPaymentToken::route('/{record}/edit'),
        ];
    }
}
