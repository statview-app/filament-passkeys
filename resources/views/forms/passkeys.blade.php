<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $deleteAction = $getAction('delete');
        $registerAction = $getAction('register');

        $state = $getState() ?? [];
    @endphp

    @if(count($state) === 0)
        <p class="text-gray-600 text-sm">{{ __('passkeys::passkeys.no_passkeys') }}</p>
    @endif

    <ul class="grid grid-cols-1 gap-2" x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('passkey-register', 'statview/filament-passkeys'))]">
        @foreach($state as $uuid => $passkey)
            <li class="border rounded-lg p-3 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-950">{{ $passkey['created_at'] }}</span>
                <div>
                    {{ $deleteAction(['item' => $uuid]) }}
                </div>
            </li>
        @endforeach
    </ul>

    <div>
        {{ $registerAction([]) }}
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('startPasskeyRegistration', (event) => {
                window.startPasskeyRegistration();
            });
        });
    </script>
</x-dynamic-component>