<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AdminSettingsResource\Pages;

class AdminSettingsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Admin Settings';

    protected static ?string $modelLabel = 'Admin Setting';

    protected static ?string $pluralModelLabel = 'Admin Settings';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 99;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Admin User Information')
                    ->description('Basic information for this admin user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Email Processing Notifications')
                    ->description('Configure when this admin receives email notifications about system activity')
                    ->schema([
                        Forms\Components\Toggle::make('admin_notify_email_processed')
                            ->label('Notify on Every Email Processed')
                            ->helperText('Receive a notification every time an email is successfully processed and leads are created')
                            ->default(false),

                        Forms\Components\Toggle::make('admin_notify_errors')
                            ->label('Notify on Email Processing Errors')
                            ->helperText('Receive notifications when email processing fails or encounters errors')
                            ->default(true),

                        Forms\Components\Toggle::make('admin_notify_rules_not_matched')
                            ->label('Notify When No Client Rules Match')
                            ->helperText('Receive notifications when an email doesn\'t match any client email rules')
                            ->default(false),

                        Forms\Components\Toggle::make('admin_notify_campaign_rules_not_matched')
                            ->label('Notify When No Campaign Rules Match')
                            ->helperText('Receive notifications when a lead doesn\'t match any campaign distribution rules')
                            ->default(false),

                        Forms\Components\Toggle::make('admin_notify_duplicate_leads')
                            ->label('Notify on Duplicate Leads')
                            ->helperText('Receive notifications when duplicate leads are detected and handled')
                            ->default(false),
                    ])->columns(1),

                Forms\Components\Section::make('System Monitoring')
                    ->description('Configure notifications for system health and maintenance')
                    ->schema([
                        Forms\Components\Toggle::make('admin_notify_high_email_volume')
                            ->label('Notify on High Email Volume')
                            ->helperText('Receive notifications when email processing volume exceeds normal thresholds')
                            ->default(false),

                        Forms\Components\Toggle::make('admin_notify_imap_connection_issues')
                            ->label('Notify on IMAP Connection Issues')
                            ->helperText('Receive notifications when IMAP connection problems are detected')
                            ->default(true),

                        Forms\Components\Toggle::make('admin_notify_smtp_issues')
                            ->label('Notify on SMTP/Mail Delivery Issues')
                            ->helperText('Receive notifications when outbound email delivery fails')
                            ->default(true),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Admin Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_notifications_enabled')
                    ->label('Email Processing')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Notified on every email processed'),
                Tables\Columns\IconColumn::make('error_notifications_enabled')
                    ->label('Errors')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Notified on processing errors'),
                Tables\Columns\IconColumn::make('rules_notifications_enabled')
                    ->label('Unmatched Rules')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Notified when no client rules match'),
                Tables\Columns\IconColumn::make('campaign_rules_notifications_enabled')
                    ->label('Campaign Rules')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Notified when no campaign rules match'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Configure Notifications'),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('No Admin Users Found')
            ->emptyStateDescription('Only users with admin role can configure notification settings.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminSettings::route('/'),
            'edit' => Pages\EditAdminSettings::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Don't allow creating admin settings, only editing existing admins
    }

    public static function canDelete($record): bool
    {
        return false; // Don't allow deleting admin users from this interface
    }
}
