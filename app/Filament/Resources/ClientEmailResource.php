<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientEmailResource\Pages;
use App\Filament\Resources\ClientEmailResource\RelationManagers;
use App\Models\ClientEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientEmailResource extends Resource
{
    protected static ?string $model = ClientEmail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->helperText('Email address that should be assigned to this client when processing leads'),
                Forms\Components\TextInput::make('description')
                    ->placeholder('e.g., Contact form submissions, Support emails')
                    ->helperText('Optional description to help identify this email mapping'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Inactive mappings will be ignored during email processing'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
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
            'index' => Pages\ListClientEmails::route('/'),
            'create' => Pages\CreateClientEmail::route('/create'),
            'edit' => Pages\EditClientEmail::route('/{record}/edit'),
        ];
    }
}
