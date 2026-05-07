<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Factory\AdminUserFactory;

use DejwCake\TestingKit\Factory\AdminUserFactory;
use DejwCake\TestingKit\Tests\TestCase;

final class RoleNameFromConfigTest extends TestCase
{
    public function testRoleNameIsResolvedFromConfig(): void
    {
        $this->config->set('testing-kit.default_admin_role', 'Manager');
        $this->app['db']->connection()->table('roles')->insert([
            'name' => 'Manager',
            'guard_name' => 'admin',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $factory = $this->app->make(AdminUserFactory::class);
        $adminUser = $factory->getAdminUser(21);

        self::assertTrue($adminUser->hasRole('Manager'));
        self::assertFalse($adminUser->hasRole('Administrator'));
    }
}
