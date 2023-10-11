<?php

namespace Statview\Passkeys\Pages;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Statview\Passkeys\Actions\BasicAction;

class MyPasskeys extends Page implements HasTable, HasActions
{
    use InteractsWithTable, InteractsWithActions;

    protected static string $view = 'passkeys::pages.my-passkeys';

    protected static bool $shouldRegisterNavigation = false;

    protected function getHeaderActions(): array
    {
        return [
            BasicAction::make('add_passkey')
                ->label('Create passkey')
                ->alpineClickHandler('startPasskeyRegistration'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => auth()->user()->passkeys()->getQuery())
            ->actions([
                DeleteAction::make(),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime(),
            ]);
    }
}