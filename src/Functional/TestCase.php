<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

use Composer\InstalledVersions;
use DejwCake\TestingKit\Factory\AdminUserFactory;
use DejwCake\TestingKit\Factory\UserFactory;
use DejwCake\TestingKit\Snapshot\HtmlDriver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Mockery;
use Spatie\Snapshots\MatchesSnapshots;

abstract class TestCase extends BaseTestCase
{
    use MatchesSnapshots;
    use RefreshDatabase;

    protected Translator|Mockery\MockInterface $translatorMocker;
    protected ?UserFactory $userFactory = null;
    protected ?AdminUserFactory $adminUserFactory = null;

    protected Authenticatable $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->createUserFactory();
        $this->adminUserFactory = $this->createAdminUserFactory();

        if (!$this->app->runningUnitTests()) {
            throw new \RuntimeException('don\'t forget for ENV=testing !!');
        }

        $this->mockTranslator();

        // https://github.com/laravel/framework/issues/9632#issuecomment-122070654
        $this->app['request']->setLaravelSession($this->app['session']->driver('array'));

        $this->app['session']->driver()->put('_token', $this->dummyCsrfToken());
    }

    public function createApplication(): Application
    {
        $app = require $this->getApplicationBootstrapPath();
        assert($app instanceof Application);

        $app->make(Kernel::class)->bootstrap();

        $app->setLocale((string) config('testing-kit.default_locale', 'en'));

        return $app;
    }

    protected function getApplicationBootstrapPath(): string
    {
        $rootPackage = InstalledVersions::getRootPackage();
        $installPath = $rootPackage['install_path'] ?? getcwd();
        $bootstrapPath = realpath(rtrim($installPath, '/') . '/bootstrap/app.php');

        if ($bootstrapPath === false) {
            throw new \RuntimeException(
                sprintf('Could not locate `bootstrap/app.php` from root package install path `%s`.', $installPath),
            );
        }

        return $bootstrapPath;
    }

    protected function createUserFactory(): UserFactory
    {
        return new UserFactory();
    }

    protected function createAdminUserFactory(): AdminUserFactory
    {
        return new AdminUserFactory();
    }

    protected function authenticatedUserId(): int
    {
        return (int) config('testing-kit.authenticated_user_id', 123);
    }

    protected function dummyCsrfToken(): string
    {
        return (string) config('testing-kit.dummy_csrf_token', 'csrf-token-mock');
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

    public function assertMatchesHtmlSnapshot(string $actual): void
    {
        $this->assertMatchesSnapshot($actual, new HtmlDriver());
    }

    public function assertDownload(TestResponse $response, ?string $filename = null): void
    {
        $contentDisposition = explode(';', $response->headers->get('content-disposition'));

        if (trim($contentDisposition[0]) !== 'attachment' && trim($contentDisposition[0]) !== 'inline') {
            self::fail(
                'Response does not offer a file download.' . PHP_EOL .
                'Disposition [' . trim($contentDisposition[0]) . '] found in header, [attachment] expected.',
            );
        }

        if (!is_null($filename)) {
            if (isset($contentDisposition[1]) && trim(explode('=', $contentDisposition[1])[0]) !== 'filename') {
                self::fail(
                    'Unsupported Content-Disposition header provided.' . PHP_EOL .
                    'Disposition [' . trim(
                        explode('=', $contentDisposition[1])[0],
                    ) . '] found in header, [filename] expected.',
                );
            }

            $message = "Expected file [{$filename}] is not present in Content-Disposition header.";

            if (!isset($contentDisposition[1])) {
                self::fail($message);
            } else {
                self::assertSame(
                    $filename,
                    isset(explode('=', $contentDisposition[1])[1])
                        ? trim(explode('=', $contentDisposition[1])[1], " \"'")
                        : '',
                    $message,
                );
            }
        } else {
            self::assertTrue(true);
        }
    }
}
