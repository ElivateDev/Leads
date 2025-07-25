<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->dehydrated(fn($state) => filled($state))
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'client' => 'Client',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state === 'admin') {
                            $set('client_id', null);
                        }
                    }),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->placeholder('Select a client')
                    ->visible(fn(Forms\Get $get): bool => $get('role') === 'client')
                    ->required(fn(Forms\Get $get): bool => $get('role') === 'client')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'success',
                        'client' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('No client assigned')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'client' => 'Client',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->relationship('client', 'name')
                    ->label('Client')
                    ->multiple(),
                Tables\Filters\Filter::make('verified')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Email verified'),
            ])
            ->actions([
                Tables\Actions\Action::make('login_as_user')
                    ->label('Login as User')
                    ->icon('heroicon-m-arrow-right-on-rectangle')
                    ->color('warning')
                    ->visible(fn(User $record): bool => $record->isClient())
                    ->url(fn(User $record): string => route('impersonate-form', $record))
                    ->openUrlInNewTab(true),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
