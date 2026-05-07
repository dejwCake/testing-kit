<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Factory\UserFactory;

use DejwCake\TestingKit\Factory\UserFactory;
use DejwCake\TestingKit\Tests\Models\TestUserModel;
use DejwCake\TestingKit\Tests\TestCase;

final class GetCustomerTest extends TestCase
{
    public function testCreatesCustomerWithGivenIdAndConfiguredEmail(): void
    {
        $factory = $this->app->make(UserFactory::class);

        $customer = $factory->getCustomer(7);

        self::assertInstanceOf(TestUserModel::class, $customer);
        self::assertSame(7, $customer->getKey());
        self::assertSame('test@example.com', $customer->email);
    }

    public function testRepeatedCallsReturnTheSameInstanceFromCache(): void
    {
        $factory = $this->app->make(UserFactory::class);

        $first = $factory->getCustomer(8);
        $second = $factory->getCustomer(8);

        self::assertSame($first, $second);
    }
}
