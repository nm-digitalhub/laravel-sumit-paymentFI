<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NmDigitalHub\LaravelOfficeGuy\Models\Payment;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\PaymentResource\Pages;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('order_id')
                            ->label('Order ID'),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('₪')
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'ILS' => 'ILS',
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('ILS')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'authorized' => 'Authorized',
                                'success' => 'Success',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'credit_card' => 'Credit Card',
                                'token' => 'Saved Token',
                            ]),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\TextInput::make('document_number')
                            ->label('Document Number'),
                        Forms\Components\TextInput::make('document_type')
                            ->label('Document Type'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Authorization Details')
                    ->schema([
                        Forms\Components\TextInput::make('authorize_amount')
                            ->numeric()
                            ->prefix('₪'),
                        Forms\Components\Toggle::make('auto_capture')
                            ->label('Auto Capture'),
                        Forms\Components\Toggle::make('is_subscription_payment')
                            ->label('Subscription Payment'),
                        Forms\Components\TextInput::make('payments_count')
                            ->numeric()
                            ->label('Number of Payments')
                            ->default(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'authorized',
                        'success' => 'success',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method'),
                Tables\Columns\IconColumn::make('is_subscription_payment')
                    ->boolean()
                    ->label('Subscription'),
                Tables\Columns\TextColumn::make('authorized_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'authorized' => 'Authorized',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\Filter::make('is_subscription_payment')
                    ->query(fn ($query) => $query->where('is_subscription_payment', true))
                    ->label('Subscriptions Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
