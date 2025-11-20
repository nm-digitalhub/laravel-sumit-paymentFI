<?php

namespace Sumit\LaravelPayment\Filament\Resources;

use Filament\Actions\ViewAction;
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
use Sumit\LaravelPayment\Models\Transaction;
use Sumit\LaravelPayment\Filament\Resources\TransactionResource\Pages;
use Sumit\LaravelPayment\Services\RefundService;
use Sumit\LaravelPayment\Services\PaymentService;
use Filament\Support\Enums\FontWeight;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Payment Gateway';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->disabled()
                            ->prefix('₪'),
                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'authorized' => 'Authorized',
                                'cancelled' => 'Cancelled',
                                'active' => 'Active',
                            ])
                            ->disabled(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'payment' => 'Payment',
                                'subscription' => 'Subscription',
                                'refund' => 'Refund',
                            ])
                            ->disabled(),
                    ])->columns(2),

                Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('customer_id')
                            ->label('Customer ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('order_id')
                            ->label('Order ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('payment_token_id')
                            ->label('Payment Token ID')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Payment Details')
                    ->schema([
                        Forms\Components\TextInput::make('document_id')
                            ->label('Document ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->disabled()
                            ->prefix('₪'),
                        Forms\Components\TextInput::make('refund_status')
                            ->label('Refund Status')
                            ->disabled(),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Transaction Metadata')
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->searchable(),
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
                Tables\Columns\IconColumn::make('is_subscription')
                    ->label('Subscription')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paymentToken.last_four')
                    ->label('Card')
                    ->formatStateUsing(fn ($state) => $state ? "****{$state}" : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\Filter::make('is_subscription')
                    ->query(fn ($query) => $query->where('is_subscription', true))
                    ->label('Subscriptions Only'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('capture')
                    ->label('Capture Payment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('capture_amount')
                            ->label('Capture Amount')
                            ->numeric()
                            ->required()
                            ->prefix('₪')
                            ->helperText(fn ($record) => "Maximum capturable: ₪{$record->amount}"),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        $paymentService = app(PaymentService::class);
                        $result = $paymentService->captureTransaction(
                            $record,
                            $data['capture_amount'] ?? null
                        );

                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Captured')
                                ->body('The payment has been captured successfully.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Capture Failed')
                                ->body($result['message'] ?? 'Failed to capture payment.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to capture this authorized payment? This will complete the transaction.')
                    ->visible(fn (Transaction $record) => 
                        $record->status === 'authorized'
                    ),
                Action::make('refund')
                    ->label('Process Refund')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->required()
                            ->prefix('₪')
                            ->helperText(fn ($record) => "Maximum refundable: ₪{$record->amount}"),
                        Forms\Components\Textarea::make('refund_reason')
                            ->label('Refund Reason')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        $refundService = app(RefundService::class);
                        $result = $refundService->processRefund(
                            $record,
                            $data['refund_amount'] ?? null,
                            $data['refund_reason'] ?? null
                        );

                        if ($result['success']) {
                            Notification::make()
                                ->title('Refund Processed')
                                ->body('The refund has been processed successfully.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Refund Failed')
                                ->body($result['message'] ?? 'Failed to process refund.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to process this refund? This action cannot be undone.')
                    ->visible(fn (Transaction $record) => 
                        $record->status === 'completed' && 
                        $record->type !== 'refund' &&
                        (!$record->refund_amount || $record->refund_amount < $record->amount)
                    ),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
