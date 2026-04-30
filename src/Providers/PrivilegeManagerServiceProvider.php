<?php

namespace LaravelPrivilegeManager\Providers;

use Illuminate\Support\ServiceProvider;
use LaravelPrivilegeManager\Middleware\CheckPrivilege;

class PrivilegeManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/privilege-manager.php',
            'privilege-manager'
        );

        // Register the service as singleton
        $this->app->singleton('privilege-manager', function ($app) {
            return new \LaravelPrivilegeManager\Services\PrivilegeService();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/privilege-manager.php' => config_path('privilege-manager.php'),
        ], 'privilege-manager-config');

        // Register middleware
        $this->app['router']->aliasMiddleware('privilege', CheckPrivilege::class);

        // Load helper functions
        $this->loadHelpers();
    }

    /**
     * Load helper functions
     */
    private function loadHelpers()
    {
        $helperPath = __DIR__ . '/../Helpers/privilege_helpers.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }
}
