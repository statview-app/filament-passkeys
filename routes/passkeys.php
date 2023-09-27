<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Statview\Passkeys\Http\Controllers\AuthenticationController;
use Statview\Passkeys\Http\Controllers\RegistrationController;
use Statview\Passkeys\Pages\Auth\PasskeyLogin;

Route::group([], function () {
    foreach (Filament::getPanels() as $panel) {
        if ($panel->hasPlugin('passkeys')) {
            $panelId = $panel->getId();
            $hasTenancy = $panel->hasTenancy();
            $tenantRoutePrefix = $panel->getTenantRoutePrefix();
            $tenantSlugAttribute = $panel->getTenantSlugAttribute();
            $domains = $panel->getDomains();

            foreach ((empty($domains) ? [null] : $domains) as $domain) {
                Route::domain($domain)
                    ->middleware($panel->getMiddleware())
                    ->name("filament.{$panelId}.")
                    ->prefix($panel->getPath())
                    ->group(function () use ($panel, $hasTenancy, $tenantRoutePrefix, $tenantSlugAttribute) {

                        Route::get('/passkey-login', PasskeyLogin::class)->name('passkey-login');

                    });
            }
        }
    }
});

Route::group([
    'prefix' => '/passkeys',
    'as' => 'passkeys',
    'middleware' => ['web'],
], function () {

    Route::post('login/verify', [AuthenticationController::class, 'verify']);

    Route::post('login/options', [AuthenticationController::class, 'generateOptions']);

    Route::post('register/verify', [RegistrationController::class, 'verify']);

    Route::post('register/options', [RegistrationController::class, 'generateOptions']);

});
