<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NmDigitalHub\LaravelOfficeGuy\Models\StockSyncLog;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\StockSyncLogResource\Pages;

class StockSyncLogResource extends Resource
{
    protected static ?string $model = StockSyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Stock Sync Logs';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sync Details')
                    ->schema([
                        Forms\Components\TextInput::make('product_id')
                            ->label('Product ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('external_identifier')
                            ->label('External ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('product_name')
                            ->label('Product Name')
                            ->disabled(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Stock Information')
                    ->schema([
                        Forms\Components\TextInput::make('old_stock')
                            ->label('Old Stock')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('new_stock')
                            ->label('New Stock')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'success' => 'Success',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Error Details')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\DateTimePicker::make('synced_at')
                            ->label('Synced At')
                            ->disabled(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_id')
                    ->label('Product ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('external_identifier')
                    ->label('External ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('old_stock')
                    ->label('Old Stock')
                    ->numeric(),
                Tables\Columns\TextColumn::make('new_stock')
                    ->label('New Stock')
                    ->numeric(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('synced_at', 'desc');
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
            'index' => Pages\ListStockSyncLogs::route('/'),
            'view' => Pages\ViewStockSyncLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Logs are created automatically, not manually
    }
}
