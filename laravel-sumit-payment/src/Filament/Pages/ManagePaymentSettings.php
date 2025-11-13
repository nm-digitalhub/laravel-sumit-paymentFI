<?php

namespace Sumit\LaravelPayment\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Sumit\LaravelPayment\Settings\PaymentSettings;

class ManagePaymentSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $settings = PaymentSettings::class;

    protected static ?string $navigationGroup = 'Payment Gateway';

    protected static ?string $title = 'SUMIT Payment Settings';

    protected static ?string $navigationLabel = 'Payment Settings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('API Credentials')
                    ->description('Your SUMIT API credentials for payment processing')
                    ->schema([
                        Forms\Components\TextInput::make('company_id')
                            ->label('Company ID')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('api_public_key')
                            ->label('API Public Key')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('merchant_number')
                            ->label('Merchant Number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subscriptions_merchant_number')
                            ->label('Subscriptions Merchant Number')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Environment Settings')
                    ->description('Configure the environment and mode settings')
                    ->schema([
                        Forms\Components\Select::make('environment')
                            ->label('Environment')
                            ->options([
                                'www' => 'Production (www)',
                                'dev' => 'Development (dev)',
                            ])
                            ->default('www')
                            ->required(),
                        Forms\Components\Toggle::make('testing_mode')
                            ->label('Testing Mode')
                            ->helperText('Enable testing mode for development (authorize only, no capture)')
                            ->default(false),
                        Forms\Components\Select::make('pci_mode')
                            ->label('PCI Compliance Mode')
                            ->options([
                                'direct' => 'Direct',
                                'redirect' => 'Redirect',
                            ])
                            ->default('direct')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Payment Settings')
                    ->description('Configure payment processing options')
                    ->schema([
                        Forms\Components\TextInput::make('maximum_payments')
                            ->label('Maximum Installments')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(36)
                            ->default(12)
                            ->required(),
                        Forms\Components\Select::make('token_method')
                            ->label('Token Method')
                            ->options([
                                'J2' => 'J2',
                                'J5' => 'J5',
                            ])
                            ->default('J2')
                            ->required(),
                        Forms\Components\TextInput::make('api_timeout')
                            ->label('API Timeout (seconds)')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(300)
                            ->default(180)
                            ->required(),
                        Forms\Components\Toggle::make('send_client_ip')
                            ->label('Send Client IP')
                            ->helperText('Send client IP address with payment requests')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Document Settings')
                    ->description('Configure invoice and receipt generation')
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
                        Forms\Components\Toggle::make('draft_document')
                            ->label('Draft Documents')
                            ->helperText('Create documents as drafts')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('Authorization Settings')
                    ->description('Configure authorization-only transactions (J5)')
                    ->schema([
                        Forms\Components\Toggle::make('authorize_only')
                            ->label('Authorization Only')
                            ->helperText('Authorize payments without capturing (J5 mode)')
                            ->default(false),
                        Forms\Components\Toggle::make('auto_capture')
                            ->label('Auto Capture')
                            ->helperText('Automatically capture authorized payments')
                            ->default(true),
                        Forms\Components\TextInput::make('authorize_added_percent')
                            ->label('Authorization Added Percent')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('authorize_minimum_addition')
                            ->label('Authorization Minimum Addition')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('₪'),
                    ])->columns(2),

                Forms\Components\Section::make('VAT Settings')
                    ->description('Configure VAT calculation')
                    ->schema([
                        Forms\Components\Toggle::make('vat_included')
                            ->label('VAT Included')
                            ->helperText('Prices include VAT')
                            ->default(true),
                        Forms\Components\TextInput::make('default_vat_rate')
                            ->label('Default VAT Rate')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(17)
                            ->suffix('%')
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
