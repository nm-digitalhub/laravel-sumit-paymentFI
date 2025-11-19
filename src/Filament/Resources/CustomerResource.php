<?php

namespace Sumit\LaravelPayment\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Sumit\LaravelPayment\Filament\Resources\CustomerResource\Pages;
use Sumit\LaravelPayment\Filament\Resources\CustomerResource\RelationManagers;
use Sumit\LaravelPayment\Models\Customer;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Payment Gateway';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('sumit_customer_id')
                            ->label('SUMIT Customer ID')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->numeric()
                            ->nullable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(50),
                    ])->columns(2),

                Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID')
                            ->maxLength(100),
                    ])->columns(2),

                Section::make('Address Information')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('state')
                            ->label('State/Province')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('country')
                            ->label('Country Code')
                            ->maxLength(2)
                            ->default('IL'),
                        Forms\Components\TextInput::make('zip_code')
                            ->label('Zip Code')
                            ->maxLength(20),
                    ])->columns(2),

                Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Information'),
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
                Tables\Columns\TextColumn::make('sumit_customer_id')
                    ->label('SUMIT ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable()
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
                Tables\Filters\Filter::make('has_user')
                    ->query(fn ($query) => $query->whereNotNull('user_id'))
                    ->label('Has User Account'),
                Tables\Filters\Filter::make('has_company')
                    ->query(fn ($query) => $query->whereNotNull('company_name'))
                    ->label('Business Customers'),
                Tables\Filters\SelectFilter::make('country')
                    ->options([
                        'IL' => 'Israel',
                        'US' => 'United States',
                        'GB' => 'United Kingdom',
                        'DE' => 'Germany',
                        'FR' => 'France',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            RelationManagers\PaymentTokensRelationManager::class,
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
