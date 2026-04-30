<?php

namespace LaravelPrivilegeManager\Providers;

use LaravelPrivilegeManager\Console\InstallPrivilegeManager;
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPrivilegeManager::class,
            ]);
        }
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

        // Publish package migrations for fresh Laravel projects
        $this->publishes([
            __DIR__ . '/../../database/migrations/2026_04_30_000001_create_tbl_menu_list_table.php' => database_path('migrations/2026_04_30_000001_create_tbl_menu_list_table.php'),
            __DIR__ . '/../../database/migrations/2026_04_30_000002_create_tbl_user_privilege_table.php' => database_path('migrations/2026_04_30_000002_create_tbl_user_privilege_table.php'),
        ], 'privilege-manager-migrations');

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
