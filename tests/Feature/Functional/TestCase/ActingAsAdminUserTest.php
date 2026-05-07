<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Functional\TestCase;

use DejwCake\TestingKit\Tests\Models\TestAdminUserModel;
use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Auth\AuthManager;

final class ActingAsAdminUserTest extends TestCase
{
    public function testActingAsAdminUserUsesAdminGuardFromAdminAuthConfig(): void
    {
        $this->actingAsAdminUser();

        $auth = $this->app->make(AuthManager::class);

        self::assertTrue($auth->guard('admin')->check());
        self::assertInstanceOf(TestAdminUserModel::class, $auth->guard('admin')->user());
        self::assertSame(123, $auth->guard('admin')->user()->getAuthIdentifier());
    }

    public function testActingAsAdminUserWithExplicitId(): void
    {
        $this->actingAsAdminUser(55);

        $auth = $this->app->make(AuthManager::class);

        self::assertSame(55, $auth->guard('admin')->user()->getAuthIdentifier());
    }
}
