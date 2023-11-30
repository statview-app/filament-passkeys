<div
    x-data=""
    x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('passkey-login', 'statview/filament-passkeys'))]"
    class="flex justify-center"
>
    <x-filament::link tag="button" x-on:click="window.loginWithPasskey()">
        {{ __('passkeys::passkeys.login_with_passkeys') }}
    </x-filament::link>
</div>