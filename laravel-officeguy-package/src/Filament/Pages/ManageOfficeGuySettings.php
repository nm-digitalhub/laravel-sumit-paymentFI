<?php

namespace NmDigitalHub\LaravelOfficeGuy\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use NmDigitalHub\LaravelOfficeGuy\Settings\OfficeGuySettings;

class ManageOfficeGuySettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = OfficeGuySettings::class;

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
                        Forms\Components\TextInput::make('api_private_key')
                            ->label('API Private Key')
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
                    ])
                    ->columns(1),

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

                Forms\Components\Section::make('Payment Processing')
                    ->schema([
                        Forms\Components\Toggle::make('testing_mode')
                            ->label('Testing Mode')
                            ->helperText('Enable testing mode for development')
                            ->default(false),
                        Forms\Components\Toggle::make('authorize_only')
                            ->label('Authorize Only')
                            ->helperText('Only authorize payments, do not capture')
                            ->default(false),
                        Forms\Components\Toggle::make('auto_capture')
                            ->label('Auto Capture')
                            ->helperText('Automatically capture authorized payments')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Document Settings')
                    ->schema([
                        Forms\Components\Toggle::make('draft_document')
                            ->label('Draft Documents')
                            ->helperText('Create documents as drafts')
                            ->default(false),
                        Forms\Components\Toggle::make('send_document_by_email')
                            ->label('Email Documents')
                            ->helperText('Automatically email documents to customers')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
