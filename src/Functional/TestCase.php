<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use Composer\InstalledVersions;
use DejwCake\TestingKit\Attributes\Context;
use DejwCake\TestingKit\Factory\AdminUserFactory;
use DejwCake\TestingKit\Factory\UserFactory;
use DejwCake\TestingKit\Snapshot\HtmlDriver;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Mockery;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;

abstract class TestCase extends BaseTestCase
{
    use MatchesSnapshots;
    use RefreshDatabase;

    protected Translator|Mockery\MockInterface $translatorMocker;
    protected ?UserFactory $userFactory = null;
    protected ?AdminUserFactory $adminUserFactory = null;

    protected Authenticatable $user;

    public function createApplication(): Application
    {
        $app = require $this->getApplicationBootstrapPath();
        assert($app instanceof Application);

        $app->make(Kernel::class)->bootstrap();

        $app->setLocale((string) config('testing-kit.default_locale', 'en'));

        return $app;
    }

    public function assertMatchesHtmlSnapshot(string $actual): void
    {
        $this->assertMatchesSnapshot($actual, new HtmlDriver());
    }

    public function assertDownload(TestResponse $response, ?string $filename = null): void
    {
        $contentDisposition = explode(';', (string) $response->headers->get('content-disposition'));

        $this->assertContentDispositionType($contentDisposition[0]);

        if ($filename !== null) {
            $this->assertContentDispositionFilename($contentDisposition, $filename);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->createUserFactory();
        $this->adminUserFactory = $this->createAdminUserFactory();

        if (!$this->app->runningUnitTests()) {
            throw new RuntimeException('don\'t forget for ENV=testing !!');
        }

        $this->mockTranslator();

        // https://github.com/laravel/framework/issues/9632#issuecomment-122070654
        $this->app['request']->setLaravelSession($this->app['session']->driver('array'));

        $this->app['session']->driver()->put('_token', $this->dummyCsrfToken());

        $this->resolveContextFromAttributes();
    }

    protected function getApplicationBootstrapPath(): string
    {
        $rootPackage = InstalledVersions::getRootPackage();
        $installPath = $rootPackage['install_path'] ?? getcwd();
        $bootstrapPath = realpath(rtrim($installPath, '/') . '/bootstrap/app.php');

        if ($bootstrapPath === false) {
            throw new RuntimeException(
                sprintf('Could not locate `bootstrap/app.php` from root package install path `%s`.', $installPath),
            );
        }

        return $bootstrapPath;
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
        return (int) config('testing-kit.authenticated_user_id', 123);
    }

    protected function dummyCsrfToken(): string
    {
        return (string) config('testing-kit.dummy_csrf_token', 'csrf-token-mock');
    }

    protected function actingAsCustomer(?int $userId = null, ?string $guard = null): self
    {
        $this->user = $this->userFactory->getCustomer($userId ?: $this->authenticatedUserId());

        return $this->actingAs($this->user, $guard);
    }

    protected function actingAsAdminUser(?int $userId = null, ?string $guard = null): self
    {
        $this->user = $this->adminUserFactory->getAdminUser($userId ?: $this->authenticatedUserId());

        return $this->actingAs($this->user, $guard);
    }

    protected function resolveContextFromAttributes(): void
    {
        [$classAttributes, $methodAttributes] = $this->getTestCaseAttributes();

        $context = new Context();
        $context = $this->mergeContextFromAttributes($context, $classAttributes);
        $context = $this->mergeContextFromAttributes($context, $methodAttributes);

        $this->setUserFromContext($context);
    }

    /**
     * @return array{0: ReflectionAttribute[], 1: ReflectionAttribute[]}
     */
    private function getTestCaseAttributes(): array
    {
        $reflectionClass = new ReflectionClass(static::class);
        $classAttributes = $reflectionClass->getAttributes();
        $reflectionMethod = $reflectionClass->getMethod($this->name());
        $methodAttributes = $reflectionMethod->getAttributes();

        return [$classAttributes, $methodAttributes];
    }

    /**
     * @param array<int, ReflectionAttribute> $attributes
     */
    private function mergeContextFromAttributes(Context $context, array $attributes): Context
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() !== Context::class) {
                continue;
            }

            $newContext = $attribute->newInstance();
            assert($newContext instanceof Context);
            if ($newContext->user !== null) {
                $context = new Context(user: $newContext->user);
            }
        }

        return $context;
    }

    private function setUserFromContext(Context $context): void
    {
        if ($context->user === null) {
            return;
        }

        match ($context->user) {
            'customer' => $this->applyCustomerContext(),
            'admin-user' => $this->applyAdminUserContext(),
            'anonymous' => $this->applyAnonymousContext(),
            default => $this->failUnsupportedUser($context->user),
        };
    }

    private function applyCustomerContext(): void
    {
        $this->actingAsCustomer();
    }

    private function applyAdminUserContext(): void
    {
        $this->actingAsAdminUser();
    }

    private function applyAnonymousContext(): void
    {
        $auth = $this->app->make(AuthManager::class);
        assert($auth instanceof AuthManager);

        if ($auth->guard()->check()) {
            $auth->guard()->logoutCurrentDevice();
        }
    }

    private function failUnsupportedUser(string $user): never
    {
        $this->fail(
            sprintf(
                'Unsupported user. [%s] Allowed are: anonymous|customer|admin-user',
                $user,
            ),
        );
    }

    private function assertContentDispositionType(string $type): void
    {
        $type = trim($type);
        if ($type !== 'attachment' && $type !== 'inline') {
            self::fail(
                'Response does not offer a file download.' . PHP_EOL .
                sprintf('Disposition [%s] found in header, [attachment] expected.', $type),
            );
        }
    }

    /**
     * @param array<int, string> $contentDisposition
     */
    private function assertContentDispositionFilename(array $contentDisposition, string $filename): void
    {
        if (!isset($contentDisposition[1])) {
            self::fail(sprintf('Expected file [%s] is not present in Content-Disposition header.', $filename));
        }

        $parts = explode('=', $contentDisposition[1]);
        $key = trim($parts[0]);

        if ($key !== 'filename') {
            self::fail(
                'Unsupported Content-Disposition header provided.' . PHP_EOL .
                sprintf('Disposition [%s] found in header, [filename] expected.', $key),
            );
        }

        $actualFilename = isset($parts[1]) ? trim($parts[1], " \"'") : '';

        self::assertSame(
            $filename,
            $actualFilename,
            sprintf('Expected file [%s] is not present in Content-Disposition header.', $filename),
        );
    }

    private function mockTranslator(): void
    {
        $this->translatorMocker = Mockery::mock(
            $this->app['translator'],
            static function (Mockery\MockInterface $mock): void {
                $mock->makePartial();

                $mock->shouldReceive('choice')
                    ->andReturnUsing(static fn ($key) => $key);

                $mock->shouldReceive('get')
                    ->andReturnUsing(static fn ($key) => $key);
            },
        );

        $this->instance('translator', $this->translatorMocker);

        // we need to extend validator factory to set our new mocked translator to it
        $this->app->singleton('validator', static function ($app) {
            // same code as in ValidationServiceProvider::registerValidationFactory() method
            $validator = new Factory($app['translator'], $app);

            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }
}
