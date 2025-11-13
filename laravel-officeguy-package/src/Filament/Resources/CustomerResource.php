<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NmDigitalHub\LaravelOfficeGuy\Models\Customer;
use NmDigitalHub\LaravelOfficeGuy\Filament\Resources\CustomerResource\Pages;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('external_id')
                            ->label('External ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('citizen_id')
                            ->label('Citizen ID')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('vat_number')
                            ->label('VAT Number')
                            ->maxLength(50),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('zip_code')
                            ->maxLength(20),
                        Forms\Components\Select::make('country')
                            ->options([
                                'IL' => 'Israel',
                                'US' => 'United States',
                                'GB' => 'United Kingdom',
                                'DE' => 'Germany',
                                'FR' => 'France',
                            ])
                            ->searchable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Preferences')
                    ->schema([
                        Forms\Components\Select::make('language')
                            ->options([
                                'he' => 'Hebrew',
                                'en' => 'English',
                            ])
                            ->default('he'),
                        Forms\Components\Toggle::make('receive_emails')
                            ->label('Receive Emails')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('external_id')
                    ->label('External ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('receive_emails')
                    ->boolean()
                    ->label('Emails')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->options([
                        'IL' => 'Israel',
                        'US' => 'United States',
                        'GB' => 'United Kingdom',
                    ]),
                Tables\Filters\Filter::make('receive_emails')
                    ->query(fn ($query) => $query->where('receive_emails', true))
                    ->label('Receives Emails'),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
