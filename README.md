# Exception reporter for LaveroAdmin

Stores exception information into the database and provides a web interface to inspect stack traces and errors.

Repository: [https://github.com/LaveroAdmin/reporter](https://github.com/LaveroAdmin/reporter)

## Installation

```bash
composer require laveroadmin/reporter

php artisan vendor:publish --tag=lavero-admin-reporter

php artisan migrate --path=vendor/laveroadmin/reporter/database/migrations

php artisan admin:import reporter
```

In `app/Exceptions/Handler.php` (or `bootstrap/app.php` in Laravel 11-13):

For Laravel 9/10 `app/Exceptions/Handler.php`:
```php
use LaveroAdmin\Admin\Reporter\Reporter;
use Throwable;

public function register()
{
    $this->reportable(function (Throwable $e) {
        Reporter::report($e);
    });
}
```

For Laravel 11/12/13 in `bootstrap/app.php`:
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->reportable(function (Throwable $e) {
        \LaveroAdmin\Admin\Reporter\Reporter::report($e);
    });
})
```

Open `/admin/exceptions` in your browser to view exceptions.

## License

Licensed under [The MIT License (MIT)](LICENSE).

## Thanks

- Special thanks to [open-admin](https://github.com/open-admin-org/open-admin) for open-admin development.
- Special thanks to [z-song](https://github.com/z-song) for original development.
