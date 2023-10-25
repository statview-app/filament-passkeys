<?php

use Illuminate\Support\Facades\Route;
use Statview\Passkeys\Http\Controllers\AuthenticationController;
use Statview\Passkeys\Http\Controllers\RegistrationController;

Route::group([
    'prefix' => '/passkeys',
    'as' => 'passkeys.',
    'middleware' => ['web'],
], function () {

    Route::post('login/verify', [AuthenticationController::class, 'verify'])->name('login.verify');

    Route::post('login/options', [AuthenticationController::class, 'generateOptions'])->name('login.options');

    Route::post('register/verify', [RegistrationController::class, 'verify'])->name('register.verify');

    Route::post('register/options', [RegistrationController::class, 'generateOptions'])->name('register.options');

});
