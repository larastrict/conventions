<?php

namespace App\Models;

final class ModelAction
{
    public function execute(): mixed
    {
        return now();
    }
}

namespace App\Providers;

final class AppServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        now();
    }
}
-----
<?php

namespace App\Models;

final class ModelAction
{
    public function execute(): mixed
    {
        return now();
    }
}

namespace App\Providers;

final class AppServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        now();
    }
}
