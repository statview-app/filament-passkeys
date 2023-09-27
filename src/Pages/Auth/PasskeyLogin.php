<?php

namespace Statview\Passkeys\Pages\Auth;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Filament\Support\Assets\Asset;

class PasskeyLogin extends Login
{
    protected static string $view = 'passkeys::pages.passkey-login';

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('login')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->action(function ($livewire) {
                $data = $this->form->getState();

                $email = $data['email'];

                $livewire->js('window.loginWithPasskey("' . $email . '")');
            });
    }
}