<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Donne tous les droits à l'admin quoi qu'il arrive
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
        if (!function_exists('array_all')) {
            /**
             * Determine if all elements in the array pass a given truth test.
             */
            function array_all(array $array, callable $callback)
            {
                foreach ($array as $key => $value) {
                    if (!$callback($value, $key)) {
                        return false;
                    }
                }
                return true;
            }
        }
    }
}
