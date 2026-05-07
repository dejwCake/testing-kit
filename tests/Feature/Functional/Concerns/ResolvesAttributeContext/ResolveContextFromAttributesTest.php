<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Functional\Concerns\ResolvesAttributeContext;

use DejwCake\TestingKit\Attributes\Context;
use DejwCake\TestingKit\Tests\Models\TestAdminUserModel;
use DejwCake\TestingKit\Tests\Models\TestUserModel;
use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Auth\AuthManager;

#[Context(user: 'anonymous')]
final class ResolveContextFromAttributesTest extends TestCase
{
    #[Context(user: 'customer')]
    public function testCustomerContextLogsInDefaultGuardWithCustomer(): void
    {
        $auth = $this->app->make(AuthManager::class);

        self::assertTrue($auth->guard()->check());
        self::assertInstanceOf(TestUserModel::class, $auth->guard()->user());
    }

    #[Context(user: 'admin-user')]
    public function testAdminUserContextLogsInAdminGuardWithAdminUser(): void
    {
        $auth = $this->app->make(AuthManager::class);

        self::assertTrue($auth->guard('admin')->check());
        self::assertInstanceOf(TestAdminUserModel::class, $auth->guard('admin')->user());
    }

    public function testClassLevelAnonymousLeavesDefaultGuardLoggedOut(): void
    {
        $auth = $this->app->make(AuthManager::class);

        self::assertFalse($auth->guard()->check());
    }
}
