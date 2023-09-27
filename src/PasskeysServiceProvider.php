<?php

namespace Statview\Passkeys;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Statview\Passkeys\Pages\Auth\PasskeyLogin;

class PasskeysServiceProvider extends ServiceProvider
{
    public function register()
    {
        Livewire::component('passkeys::passkey-login', PasskeyLogin::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/passkeys.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'passkeys');

        FilamentAsset::register([
            Js::make('passkeys-script', __DIR__.'/../dist/js/passkeys.js'),
        ], 'statview/filament-passkeys');
    }
}