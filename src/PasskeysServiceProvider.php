<?php

namespace Statview\Passkeys;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class PasskeysServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/passkeys.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'passkeys');

        FilamentAsset::register([
            Js::make('passkey-login', __DIR__.'/../dist/js/passkey-login.js')->loadedOnRequest(),
            Js::make('passkey-register', __DIR__.'/../dist/js/passkey-register.js')->loadedOnRequest(),
        ], 'statview/filament-passkeys');
    }
}