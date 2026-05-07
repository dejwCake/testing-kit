<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Functional\TestCase;

use DejwCake\TestingKit\Tests\Models\TestUserModel;
use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Auth\AuthManager;

final class ActingAsCustomerTest extends TestCase
{
    public function testActingAsCustomerLogsInDefaultGuard(): void
    {
        $this->actingAsCustomer();

        $auth = $this->app->make(AuthManager::class);

        self::assertTrue($auth->guard()->check());
        self::assertInstanceOf(TestUserModel::class, $auth->guard()->user());
        self::assertSame(123, $auth->guard()->user()->getAuthIdentifier());
    }

    public function testActingAsCustomerWithExplicitId(): void
    {
        $this->actingAsCustomer(99);

        $auth = $this->app->make(AuthManager::class);

        self::assertSame(99, $auth->guard()->user()->getAuthIdentifier());
    }
}
