<?php

namespace Statview\Passkeys\Actions;

use Filament\Actions\Action;

class BasicAction extends Action
{
    public ?string $alpineClickHandler = null;

    protected function setUp(): void
    {
        $this->livewireClickHandlerEnabled(false);

        parent::setUp();
    }

    public function alpineClickHandler(?string $alpineClickHandler): static
    {
        $this->alpineClickHandler = $alpineClickHandler;

        return $this;
    }

    public function getAlpineClickHandler(): ?string
    {
        return $this->alpineClickHandler;
    }
}