<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Functional\TestCase;

use DejwCake\TestingKit\Factory\AdminUserFactory;
use DejwCake\TestingKit\Factory\UserFactory;
use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Contracts\Config\Repository as Config;

final class SetUpTest extends TestCase
{
    public function testFactoriesAreResolved(): void
    {
        self::assertInstanceOf(UserFactory::class, $this->userFactory);
        self::assertInstanceOf(AdminUserFactory::class, $this->adminUserFactory);
    }

    public function testConfigIsResolved(): void
    {
        self::assertInstanceOf(Config::class, $this->config);
    }

    public function testDummyCsrfTokenIsPlacedInSession(): void
    {
        self::assertSame('csrf-token-mock', $this->app['session']->driver()->get('_token'));
    }

    public function testTranslatorMockerIsRegistered(): void
    {
        self::assertNotEmpty($this->translatorMocker);
    }
}
