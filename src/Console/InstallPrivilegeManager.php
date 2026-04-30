<?php

namespace LaravelPrivilegeManager\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallPrivilegeManager extends Command
{
    protected $signature = 'privilege-manager:install {--force : Overwrite existing published files}';

    protected $description = 'Publish the privilege manager config and migrations for a fresh Laravel project';

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

        $this->newLine();
        $this->info('Installation completed.');
        $this->line('Next: run php artisan migrate and add the HasPrivileges trait to your App\\Models\\User model.');

        return self::SUCCESS;
    }
}
