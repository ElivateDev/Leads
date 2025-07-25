<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Client notes about this lead')
                    ->columnSpanFull()
                    ->rows(4),
                Forms\Components\TextInput::make('from_email')
                    ->email()
                    ->label('From Email')
                    ->helperText('Email address from which the lead was generated')
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->label('Disposition')
                    ->options(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->getLeadDispositions();
                    })
                    ->required()
                    ->default('new'),
                Forms\Components\Select::make('source')
                    ->options([
                        'website' => 'Website',
                        'phone' => 'Phone',
                        'referral' => 'Referral',
                        'social' => 'Social Media',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('website'),
                Forms\Components\TextInput::make('campaign')
                    ->label('Campaign')
                    ->helperText('Optional campaign identifier for tracking lead sources')
                    ->maxLength(255),
                Forms\Components\TextInput::make('campaign')
                    ->label('Campaign')
                    ->helperText('Optional campaign identifier for tracking lead sources')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->placeholder('No notes')
                    ->color('info')
                    ->icon('heroicon-m-pencil-square'),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Disposition')
                    ->options(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->getLeadDispositions();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'website' => 'success',
                        'phone' => 'info',
                        'referral' => 'warning',
                        'social' => 'danger',
                        'other' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('campaign')
                    ->placeholder('No campaign')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Disposition')
                    ->options(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->getLeadDispositions();
                    }),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'website' => 'Website',
                        'phone' => 'Phone',
                        'referral' => 'Referral',
                        'social' => 'Social Media',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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
}
