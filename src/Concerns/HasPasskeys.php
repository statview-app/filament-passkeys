<?php

namespace Statview\Passkeys\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Statview\Passkeys\Models\Passkey;

trait HasPasskeys
{
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }
}