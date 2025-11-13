<?php

namespace Sumit\LaravelPayment\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Sumit\LaravelPayment\Models\PaymentToken;
use Sumit\LaravelPayment\Filament\Resources\PaymentTokenResource\Pages;

class PaymentTokenResource extends Resource
{
    protected static ?string $model = PaymentToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Tokens';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Token Details')
                    ->schema([
                        Forms\Components\TextInput::make('token')
                            ->label('Token')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('card_type')
                            ->label('Card Type'),
                        Forms\Components\TextInput::make('last_four')
                            ->label('Last 4 Digits')
                            ->maxLength(4),
                        Forms\Components\TextInput::make('cardholder_name')
                            ->label('Cardholder Name')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Expiry Information')
                    ->schema([
                        Forms\Components\TextInput::make('expiry_month')
                            ->label('Expiry Month')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->required(),
                        Forms\Components\TextInput::make('expiry_year')
                            ->label('Expiry Year')
                            ->numeric()
                            ->minValue(date('Y'))
                            ->required(),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Card')
                            ->default(false),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('card_type')
                    ->label('Card Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('last_four')
                    ->label('Card Number')
                    ->formatStateUsing(fn ($record) => '**** **** **** ' . $record->last_four),
                Tables\Columns\TextColumn::make('cardholder_name')
                    ->label('Cardholder')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expiry_month')
                    ->label('Expiry')
                    ->formatStateUsing(fn ($record) => sprintf('%02d/%s', $record->expiry_month, substr($record->expiry_year, -2))),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_default')
                    ->query(fn ($query) => $query->where('is_default', true))
                    ->label('Default Cards Only'),
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->active())
                    ->label('Active Cards Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPaymentTokens::route('/'),
            'create' => Pages\CreatePaymentToken::route('/create'),
            'view' => Pages\ViewPaymentToken::route('/{record}'),
            'edit' => Pages\EditPaymentToken::route('/{record}/edit'),
        ];
    }
}
