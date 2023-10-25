# filament-passkeys

## Installation
Install the package
```bash
composer require statview/filament-passkeys
```

Run the migrations
```bash
php artisan migrate
```

Run the assets command of Filament
```bash
php artisan filament:assets
```

Add the `HasPasskeys` trait to the user mode

```php
<?php

use Statview\Passkeys\Concerns\HasPasskeys;

class User extends Model {
    use HasPasskeys;
...
```

Exclude the `passkeys` routes from the CSRF check
```php
<?php

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'passkeys/*',
    ];
}
```
Add our plugin to your Filament context(s)

```php
<?php

use Statview\Passkeys\PasskeysPlugin;

class AdminPanel extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(PasskeysPlugin::make())
            // ...
    }    
}
```

## Credits
Inspired and created by following the following article:
https://blog.joe.codes/implementing-passkey-authentication-in-your-laravel-app
