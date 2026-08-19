<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Custom macro to bring @selected functionality to Laravel 8
        Blade::directive('selected', function ($expression) {
            return "<?php echo ({$expression}) ? 'selected' : ''; ?>";
        });

        // Custom macro for @checked
        Blade::directive('checked', function ($expression) {
            return "<?php echo ({$expression}) ? 'checked' : ''; ?>";
        });

        // Custom macro for @disabled
        Blade::directive('disabled', function ($expression) {
            return "<?php echo ({$expression}) ? 'disabled' : ''; ?>";
        });
    }
}
