<?php

namespace Sumit\LaravelPayment\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Sumit\LaravelPayment\Settings\SumitPaymentSettings;

class ManageSumitPaymentSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SumitPaymentSettings::class;

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static ?int $navigationSort = 99;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('API Credentials')
                    ->schema([
                        Forms\Components\TextInput::make('company_id')
                            ->label('Company ID')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->required()
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('api_public_key')
                            ->label('API Public Key')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Environment Settings')
                    ->schema([
                        Forms\Components\Select::make('environment')
                            ->label('Environment')
                            ->options([
                                'www' => 'Production',
                                'dev' => 'Development',
                            ])
                            ->default('www')
                            ->required(),
                        Forms\Components\Toggle::make('testing_mode')
                            ->label('Testing Mode')
                            ->helperText('Enable testing mode for development (authorize only, no capture)')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Merchant Settings')
                    ->schema([
                        Forms\Components\TextInput::make('merchant_number')
                            ->label('Merchant Number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subscriptions_merchant_number')
                            ->label('Subscriptions Merchant Number')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Document Settings')
                    ->schema([
                        Forms\Components\Toggle::make('email_document')
                            ->label('Email Documents')
                            ->helperText('Automatically email invoices/receipts to customers')
                            ->default(true),
                        Forms\Components\Select::make('document_language')
                            ->label('Document Language')
                            ->options([
                                'he' => 'Hebrew',
                                'en' => 'English',
                            ])
                            ->default('he')
                            ->required(),
                        Forms\Components\TextInput::make('maximum_payments')
                            ->label('Maximum Payments')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(36)
                            ->default(12)
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
}
