<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use App\Mixins\BuilderMixins;
use Illuminate\Routing\Route;
use App\Models\PersonalAccessToken;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

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
            $actionName = resolve(Route::class)->getActionName();

            return substr($actionName, strpos($actionName, '@') + 1);
        });
        Builder::mixin(new BuilderMixins());
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
