<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Factory\AdminUserFactory;

use DejwCake\TestingKit\Factory\AdminUserFactory;
use DejwCake\TestingKit\Tests\Models\TestAdminUserModel;
use DejwCake\TestingKit\Tests\TestCase;

final class GetAdminUserTest extends TestCase
{
    public function testCreatesAdminUserWithDeterministicAttributes(): void
    {
        $factory = $this->app->make(AdminUserFactory::class);

        $adminUser = $factory->getAdminUser(11);

        self::assertInstanceOf(TestAdminUserModel::class, $adminUser);
        self::assertSame(11, $adminUser->getKey());
        self::assertSame('Admin', $adminUser->first_name);
        self::assertSame('User', $adminUser->last_name);
        self::assertSame('admin@example.com', $adminUser->email);
        self::assertTrue($adminUser->activated);
        self::assertFalse($adminUser->forbidden);
        self::assertSame('en', $adminUser->language);
    }

    public function testAssignsAdministratorRoleOnAdminGuard(): void
    {
        $factory = $this->app->make(AdminUserFactory::class);

        $adminUser = $factory->getAdminUser(12);

        self::assertTrue($adminUser->hasRole('Administrator'));
    }

    public function testRepeatedCallsReturnTheSameInstanceFromCache(): void
    {
        $factory = $this->app->make(AdminUserFactory::class);

        $first = $factory->getAdminUser(13);
        $second = $factory->getAdminUser(13);

        self::assertSame($first, $second);
    }
}
