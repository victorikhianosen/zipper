<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Request::macro('resolveActionName', function () {
            info(strtoupper(bin2hex(random_bytes(5))));
            $actionName = resolve(Route::class)->getActionName();

            return substr($actionName, strpos($actionName, '@') + 1);
        });
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
