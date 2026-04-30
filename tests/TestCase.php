<?php

namespace LaravelPrivilegeManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use LaravelPrivilegeManager\Providers\PrivilegeManagerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PrivilegeManagerServiceProvider::class,
        ];
    }
}
