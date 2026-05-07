# Testing Kit

Testing Kit is a Laravel package that provides reusable testing infrastructure for Craftable-based projects. It supplies:

- A `TestCase` base (database refresh, translator mocking, dummy CSRF, snapshot helpers, download assertions, declarative actor resolution from PHP attributes)
- `HttpTestCase`, `WebTestCase`, `ApiTestCase` for HTTP / web / API integration tests
- `Context` PHP attribute for declarative `customer | admin-user | anonymous` setup per test method or class
- `UserFactory` and `AdminUserFactory` with the underlying model class pluggable via config (constructor-injected `Hasher`); `AdminUserFactory` also assigns the configured admin role
- HTML snapshot driver and `SnapshotAsserts` trait for view snapshot testing
- `OpenApiValidationTrait` and helper `Util` for asserting responses against an OpenAPI spec

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel.

## Install

Add the path repository (or a Packagist version) and require as a dev dependency in your application:

```json
"require-dev": {
    "dejwcake/testing-kit": "*"
}
```

The `TestingKitServiceProvider` is auto-discovered by Laravel.

Publish the config (optional) to override the user models, default emails, role name, or the OpenAPI spec path:

```shell
php artisan vendor:publish --tag=testing-kit-config
```

### Config

`config/testing-kit.php` (after publishing):

| Key | Default | Purpose |
|---|---|---|
| `user_model` | `App\Models\User` | Model class used by `UserFactory` |
| `admin_user_model` | `Brackets\AdminAuth\Models\AdminUser` | Model class used by `AdminUserFactory` |
| `authenticated_user_id` | `123` | Default id used by `actingAsCustomer()` / `actingAsAdminUser()` when none is passed |
| `dummy_csrf_token` | `'csrf-token-mock'` | Token put into the test session |
| `default_locale` | `'en'` | Locale set in `createApplication()` |
| `default_user_email` | `'test@example.com'` | Email pinned on customer fixtures |
| `default_admin_user_email` | `'admin@example.com'` | Email pinned on admin user fixtures |
| `default_admin_role` | `'Administrator'` | Role assigned to admin user fixtures via Spatie `HasRoles` |
| `openapi.spec_path` | `storage_path('api-docs/openapi.json')` | Spec file used by `ApiTestCase` |
| `openapi.regenerate_command` | `'l5-swagger:generate'` | Artisan command to regenerate the spec |
| `openapi.regenerate_on_init` | `true` | Whether `ApiTestCase::setUp` regenerates the spec |

## Usage

### Web test (HTML snapshot + download assertion)

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

### API test (OpenAPI validation)

```php
use DejwCake\TestingKit\Functional\ApiTestCase;
use DejwCake\TestingKit\Attributes\Context;

final class PostsControllerTest extends ApiTestCase
{
    #[Context(user: 'customer')]
    public function testCustomerCanListPosts(): void
    {
        $response = $this->getJson(route('api/posts/index'));

        $response->assertOk();
        $this->assertValidOpenApiResponseForRoute('GET', 'api/posts/index', $response);
    }
}
```

### Acting as an admin

`#[Context(user: 'admin-user')]` resolves the guard from `admin-auth.defaults.guard` (Spatie `HasRoles` then sees the admin guard, so the role lookup matches the admin-auth installation migration). The fixture admin user has `id` = `testing-kit.authenticated_user_id`.

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
