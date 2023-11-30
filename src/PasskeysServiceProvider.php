<?php

namespace Statview\Passkeys;

use Filament\Forms\Components\Actions\Action;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class PasskeysServiceProvider extends ServiceProvider
{
    public function register()
    {
        //$this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Action::macro('alpineClickHandler', function (?string $alpineClickHandler) {
            $this->alpineClickHandler = $alpineClickHandler;

            return $this;
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/passkeys.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'passkeys');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'passkeys');

        $this->publishes([
            __DIR__.'/../database/migrations/2023_09_27_000000_create_passkeys_table.php' => database_path('migrations/2023_09_27_000000_create_passkeys_table.php'),
        ], 'passkeys-migrations');

        FilamentAsset::register([
            Js::make('passkey-login', __DIR__.'/../dist/js/passkey-login.js')->loadedOnRequest(),
            Js::make('passkey-register', __DIR__.'/../dist/js/passkey-register.js')->loadedOnRequest(),
        ], 'statview/filament-passkeys');
    }
}