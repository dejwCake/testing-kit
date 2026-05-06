# Testing Kit

Testing Kit is a Laravel package that provides reusable testing infrastructure for Craftable-based projects. It supplies:

- Base `TestCase` (database refresh, translator mocking, dummy CSRF, snapshot helpers, download assertions, declarative context resolution from PHP attributes)
- `HttpTestCase`, `ApiTestCase`, `WebTestCase` for HTTP-driven integration tests
- `Context` PHP attribute for declarative `customer | admin-user | anonymous` setup per test method or class
- `UserFactory` and `AdminUserFactory` with model class pluggable via config (constructor-injected `Hasher`)
- HTML snapshot driver and `SnapshotAsserts` trait for view snapshot testing
- `OpenApiValidationTrait` and helper `Util` for asserting responses against an OpenAPI spec

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel.

## Install

Add the path repository (or a Packagist version) and require as a dev dependency in your application:

```json
"repositories": [
    { "type": "path", "url": "packages/*", "options": { "symlink": true } }
],
"require-dev": {
    "dejwcake/testing-kit": "*"
}
```

The `TestingKitServiceProvider` is auto-discovered by Laravel.

Publish the config (optional) to override the user models, default emails or the OpenAPI spec path:

```shell
php artisan vendor:publish --tag=testing-kit-config
```

## Usage

Extend the package base classes from your project's test cases:

```php
use DejwCake\TestingKit\Functional\WebTestCase;
use DejwCake\TestingKit\Attributes\Context;

final class InstructionsControllerTest extends WebTestCase
{
    #[Context(user: 'customer')]
    public function testCustomerCanDownloadInstruction(): void
    {
        $response = $this->get(route('web/instructions/show', ['legoSet' => 5]));

        $response->assertOk();
        $this->assertDownload($response, 'dummy.pdf');
    }
}
```

`createApplication()` resolves your project's `bootstrap/app.php` automatically via the Composer runtime API. Override `getApplicationBootstrapPath()` in your local TestCase if needed.

## Issues

If something is not working as expected, please open an issue in the main repository https://github.com/dejwCake/craftable.

## How to develop this project

### Composer

Update dependencies:
```shell
docker compose run -it --rm test composer update
```

Composer normalization:
```shell
docker compose run -it --rm php-qa composer normalize
```

### Run tests

Run tests with pcov:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit -d pcov.enabled=1
```

To regenerate snapshots use:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit -d pcov.enabled=1 -d --update-snapshots
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:
```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
```

### Run code analysis tools (php-qa)

PHP compatibility:
```shell
docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache
```

Code style:
```shell
docker compose run -it --rm php-qa phpcs -s --colors --extensions=php
```

Fix style issues:
```shell
docker compose run -it --rm php-qa phpcbf -s --colors --extensions=php
```

Static analysis (phpstan):
```shell
docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon
```

Mess detector (phpmd):
```shell
docker compose run -it --rm php-qa phpmd ./config,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
```

