<?php

namespace Statview\Passkeys\Forms;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Concerns\CanGenerateUuids;
use Filament\Forms\Components\Field;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Table;
use Statview\Passkeys\Models\Passkey;

class Passkeys extends Field
{
    use CanGenerateUuids;

    protected string $view = 'passkeys::forms.passkeys';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadStateFromRelationshipsUsing(function (Passkeys $component) {
            $this->setState($component);
        });

        $this->registerActions([
            fn(Passkeys $component): Action => $component->getDeleteAction(),
            fn(Passkeys $component): Action => $component->getRegisterAction(),
        ]);
    }

    protected function setState(Passkeys $component): void
    {
        $passkeys = $component->getRecord()->passkeys()->get();

        $passkeys = $passkeys->mapWithKeys(function (Passkey $passkey) use ($component) {
            return [$component->generateUuid() => [
                'id' => $passkey->id,
                'created_at' => $passkey->created_at->format(Table::$defaultDateTimeDisplayFormat),
            ]];
        });

        $component->state($passkeys);
    }

    public function getRegisterAction(): Action
    {
        return Action::make('register')
            ->label(__('passkeys::passkeys.create_passkey'))
            ->color('gray')
            ->icon('heroicon-o-plus')
            ->action(function (Passkeys $component) {
                $livewire = $component->getLivewire();

                $livewire->dispatch('startPasskeyRegistration');
            });
    }

    public function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('passkeys::passkeys.delete_passkey'))
            ->icon('heroicon-m-trash')
            ->color(Color::Red)
            ->action(function (array $arguments, Passkeys $component): void {
                $items = $component->getState();

                $passkeyId = $items[$arguments['item']]['id'];

                $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

                $passkey->deleteOrFail();

                $this->setState($component);
            })
            ->requiresConfirmation()
            ->iconButton()
            ->size(ActionSize::Small);
    }

    public static function make(string $name = null): static
    {
        return parent::make('passkeys');
    }
}