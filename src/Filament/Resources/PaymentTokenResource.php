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

    protected static ?string $navigationGroup = 'Payment Gateway';

    protected static ?string $navigationLabel = 'Payment Tokens';

    protected static ?string $modelLabel = 'Payment Token';

    protected static ?string $pluralModelLabel = 'Payment Tokens';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Token Details')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('token')
                            ->label('Token')
                            ->disabled(),
                        Forms\Components\TextInput::make('card_last_four')
                            ->label('Card Last 4 Digits')
                            ->disabled(),
                        Forms\Components\TextInput::make('card_brand')
                            ->label('Card Brand')
                            ->disabled(),
                        Forms\Components\TextInput::make('expiry_month')
                            ->label('Expiry Month')
                            ->disabled(),
                        Forms\Components\TextInput::make('expiry_year')
                            ->label('Expiry Year')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Token Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Payment Method'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Metadata')
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
                Tables\Columns\TextColumn::make('card_brand')
                    ->label('Card Brand')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Visa' => 'info',
                        'MasterCard' => 'warning',
                        'American Express' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('card_last_four')
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
            'view' => Pages\ViewPaymentToken::route('/{record}'),
            'edit' => Pages\EditPaymentToken::route('/{record}/edit'),
        ];
    }
}
