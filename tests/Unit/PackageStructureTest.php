<?php

namespace LaravelPrivilegeManager\Tests\Unit;

use LaravelPrivilegeManager\Tests\TestCase;

class PackageStructureTest extends TestCase
{
    public function test_package_configuration_is_available(): void
    {
        $this->assertIsArray(config('privilege-manager.tables'));
        $this->assertSame('tbl_user_privilege', config('privilege-manager.tables.user_privileges'));
    }

    public function test_service_provider_registers_middleware_alias(): void
    {
        $router = $this->app['router'];
        $this->assertArrayHasKey('privilege', $router->getMiddleware());
    }
}
