<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use DejwCake\TestingKit\Factory\AdminUserFactory;
use DejwCake\TestingKit\Factory\UserFactory;
use DejwCake\TestingKit\Functional\Concerns\AssertsDownload;
use DejwCake\TestingKit\Functional\Concerns\MocksTranslator;
use DejwCake\TestingKit\Functional\Concerns\ResolvesAttributeContext;
use DejwCake\TestingKit\Snapshot\HtmlDriver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;

abstract class TestCase extends BaseTestCase
{
    use AssertsDownload;
    use MatchesSnapshots;
    use MocksTranslator;
    use RefreshDatabase;
    use ResolvesAttributeContext;

    protected ?UserFactory $userFactory = null;
    protected ?AdminUserFactory $adminUserFactory = null;

    protected Authenticatable $user;
    protected Config $config;

    public function assertMatchesHtmlSnapshot(string $actual, ?string $id = null): void
    {
        $this->assertMatchesSnapshot($actual, new HtmlDriver(), $id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->createUserFactory();
        $this->adminUserFactory = $this->createAdminUserFactory();
        $this->config = $this->app->make(Config::class);

        if (!$this->app->runningUnitTests()) {
            throw new RuntimeException('don\'t forget for ENV=testing !!');
        }

        $this->app->setLocale((string) $this->config->get('testing-kit.default_locale', 'en'));

        $this->mockTranslator();

        // https://github.com/laravel/framework/issues/9632#issuecomment-122070654
        $this->app['request']->setLaravelSession($this->app['session']->driver('array'));

        $this->app['session']->driver()->put('_token', $this->dummyCsrfToken());

        $this->resolveContextFromAttributes();
    }

    protected function createUserFactory(): UserFactory
    {
        $factory = $this->app->make(UserFactory::class);
        assert($factory instanceof UserFactory);

        return $factory;
    }

    protected function createAdminUserFactory(): AdminUserFactory
    {
        $factory = $this->app->make(AdminUserFactory::class);
        assert($factory instanceof AdminUserFactory);

        return $factory;
    }

    protected function authenticatedUserId(): int
    {
        return (int) $this->config->get('testing-kit.authenticated_user_id', 123);
    }

    protected function dummyCsrfToken(): string
    {
        return (string) $this->config->get('testing-kit.dummy_csrf_token', 'csrf-token-mock');
    }

    protected function actingAsCustomer(?int $userId = null): self
    {
        $this->user = $this->userFactory->getCustomer($userId ?: $this->authenticatedUserId());

        return $this->actingAs($this->user);
    }

    protected function actingAsAdminUser(?int $userId = null): self
    {
        $this->user = $this->adminUserFactory->getAdminUser($userId ?: $this->authenticatedUserId());

        return $this->actingAs($this->user, (string) $this->config->get('admin-auth.defaults.guard'));
    }
}
