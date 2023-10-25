<?php

namespace Statview\Passkeys;

use App\Filament\Pages\Auth\Login;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TextInput;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\View\View;
use Statview\Passkeys\Pages\MyPasskeys;

class PasskeysPlugin implements Plugin
{
    public bool $shouldRenderUserMenuItem = false;

    public function getId(): string
    {
        return 'passkeys';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                MyPasskeys::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('My passkeys')
                    ->icon('heroicon-o-lock-closed')
                    ->url(fn () => MyPasskeys::getUrl())
                    ->visible(fn () => $panel->getPlugin('passkeys')->getShouldRenderUserMenuItem()),
            ])
            ->renderHook(
                name: 'panels::auth.login.form.after',
                hook: fn (): View => view('passkeys::passkey-login'),
            );
    }

    public function shouldRenderUserMenuItem($flag = true): static
    {
        $this->shouldRenderUserMenuItem = $flag;

        return $this;
    }

    public function getShouldRenderUserMenuItem(): bool
    {
        return $this->shouldRenderUserMenuItem;
    }

    public function boot(Panel $panel): void
    {
        if (request()->route()->getName() === 'filament.' . $panel->getId() . '.auth.login') {
            TextInput::configureUsing(function (TextInput $component) {
                if ($component->getName() !== 'email') {
                    return;
                }

                $component->extraInputAttributes([
                    'autocomplete' => 'username webauthn',
                ]);
            }, null, true);
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }
}