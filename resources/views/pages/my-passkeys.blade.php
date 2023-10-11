<x-filament::page>

    <div
        x-data=""
        x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('passkey-register', 'statview/filament-passkeys'))]"
    ></div>

    {{ $this->table }}

</x-filament::page>