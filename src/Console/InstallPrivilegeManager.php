<?php

namespace LaravelPrivilegeManager\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LaravelPrivilegeManager\Database\Seeders\MenuSeeder;

class InstallPrivilegeManager extends Command
{
    protected $signature = 'privilege-manager:install {--force : Overwrite existing published files}';

    protected $description = 'Publish privilege manager config, migrations, and seed .env variables';

    /**
     * .env variables to write on install.
     * Each entry: [key, value, comment]
     */
    private const ENV_VARIABLES = [
        'PRIVILEGE_CACHE_DRIVER' => ['redis', 'Cache strategy: redis, request, or none'],
        'PRIVILEGE_CACHE_TTL'    => ['86400', 'Privilege cache TTL in seconds (24h)'],
        'PRIVILEGE_CACHE_ENABLED' => ['true', 'Enable privilege result caching'],
        'PRIVILEGE_RATE_LIMIT_ENABLED' => ['true', 'Enable rate limiting on privilege checks'],
        'PRIVILEGE_RATE_LIMIT_ATTEMPTS' => ['1000', 'Max privilege checks per decay window'],
        'PRIVILEGE_RATE_LIMIT_DECAY' => ['1', 'Rate limit decay window in minutes'],
        'PRIVILEGE_LOG_CHECKS' => ['true', 'Log privilege check attempts'],
        'PRIVILEGE_LOG_DENIALS' => ['true', 'Log privilege denials'],
        'PRIVILEGE_PRELOAD' => ['true', 'Preload user privileges on auth'],
        'PRIVILEGE_BATCH_OPS' => ['true', 'Enable batch privilege operations'],
        'PRIVILEGE_PUBLISH_MIGRATIONS' => ['true', 'Publish package migrations for fresh projects'],
    ];

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Publishing privilege manager configuration...');
        Artisan::call('vendor:publish', [
            '--tag' => 'privilege-manager-config',
            '--force' => $force,
        ]);
        $this->line(trim(Artisan::output()));

        $this->info('Publishing privilege manager migrations...');
        Artisan::call('vendor:publish', [
            '--tag' => 'privilege-manager-migrations',
            '--force' => $force,
        ]);
        $this->line(trim(Artisan::output()));

        // Write .env variables
        $this->seedEnvVariables();

        // Run package migrations
        $this->runMigrations();

        // Seed default menu records
        $this->seedDefaultMenus();

        $this->newLine();
        $this->info('Installation completed.');
        $this->line('Next: run php artisan migrate and add the HasPrivileges trait to your App\\Models\\User model.');

        return self::SUCCESS;
    }

    /**
     * Append missing PRIVILEGE_* variables to the project's .env file.
     */
    private function seedEnvVariables(): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('.env file not found — skipping environment variable setup.');
            return;
        }

        $envContents = file_get_contents($envPath);
        $added = 0;

        foreach (self::ENV_VARIABLES as $key => [$value, $comment]) {
            // Skip if the key already exists in .env (active or commented out)
            if (preg_match('/^#?\s*' . preg_quote($key, '/') . '=/m', $envContents)) {
                continue;
            }

            $envContents .= "\n# {$comment}\n{$key}={$value}\n";
            $added++;
        }

        if ($added > 0) {
            file_put_contents($envPath, $envContents);
            $this->info("Seeded {$added} PRIVILEGE_* variable(s) into .env");
        } else {
            $this->line('All PRIVILEGE_* variables already present in .env — nothing to add.');
        }
    }

    /**
     * Run package migrations if the tables don't already exist.
     */
    private function runMigrations(): void
    {
        $menusTable = config('privilege-manager.database.menus_table', 'tbl_menu_list');

        if (\Illuminate\Support\Facades\Schema::hasTable($menusTable)) {
            $this->line("Table '{$menusTable}' already exists — skipping migrations.");
            return;
        }

        $this->info('Running package migrations...');
        Artisan::call('migrate', [
            '--path'  => 'vendor/laravel-privilege-manager/database/migrations',
            '--force' => true,
        ]);
        $this->line(trim(Artisan::output()));
    }

    /**
     * Seed the default menu records (User Privileges, User Type, User Account).
     */
    private function seedDefaultMenus(): void
    {
        $this->info('Seeding default menu records...');
        $seeder = new MenuSeeder();
        $seeder->run();
        $this->line('   Seeded 3 default menus: User Privileges, User Type, User Account');
    }
}
