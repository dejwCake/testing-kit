# AGENTS.md — dejwcake/testing-kit

PHPUnit 13 / Laravel 13 base test cases and helpers for Craftable projects. Composer
`dejwcake/testing-kit`, namespace `DejwCake\TestingKit`. The service provider only merges and
publishes config; everything else is consumed by extending a base `TestCase`. README.md shows
consumer usage.

## Layout

| Class | Use for |
|---|---|
| `Functional\TestCase` | HTTP base: DB refresh, translator mock, dummy CSRF, `actingAs*`, `#[Context]` |
| `Functional\HttpTestCase` | + `withoutMiddleware(PreventRequestForgery::class)` |
| `Functional\WebTestCase` | + `SnapshotAsserts` (HTML/view snapshots) |
| `Functional\ApiTestCase` | + `OpenApiValidationTrait` (regenerates and loads the spec in `setUp()`) |
| `Feature\TestCase` | Service-layer base: DB refresh, translator mock, frozen Carbon, snapshots |

- `src/Concerns/` — `MocksTranslator`, `FreezesCarbon`, `ResolvesAttributeContext`, `AssertsDownload`.
- `src/Factory/` — `UserFactory` / `AdminUserFactory`: **stateful per-test caches**, not
  Eloquent factories; deterministic attributes so snapshots stay stable.
- `src/Snapshot/` — `HtmlDriver` and `SnapshotAsserts` (normalises UUIDs, random ids, CSRF
  values, Vite tags and the Inertia asset `version`).
- `src/OpenApi/` — `OpenApiValidationTrait`, `Util` (swagger-php DSL helper).
- `src/Attributes/Context.php` — `#[Context(user: 'customer'|'admin-user'|'anonymous')]`,
  method-level overrides class-level.

## Commands

Everything runs in Docker from the package root — never against a host PHP. The full,
copy-pasteable list (composer, every QA tool, snapshot regeneration and
the "whole PHP suite" one-liner) is in **README.md → "How to develop this project"**.
The ones you need most:

```shell
docker compose run --rm test composer update
docker compose run --rm test ./vendor/bin/phpunit                         # in-memory SQLite
docker compose run --rm -e UPDATE_SNAPSHOTS=true test ./vendor/bin/phpunit
docker compose run --rm php-qa phpcs -s --colors --extensions=php
docker compose run --rm php-qa phpcbf -s --colors --extensions=php       # auto-fix style
docker compose run --rm php-qa phpstan analyse --configuration=phpstan.neon
docker compose run --rm php-qa phpmd ./config,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
docker compose run --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache
docker compose run --rm php-qa composer normalize
```

A change is done when phpcs, phpstan, phpmd and the test suite are green.

## Code conventions

- PHP `^8.5`, Laravel 13. Every file starts with `declare(strict_types=1);`.
- **No Facades** — inject contracts through the constructor.
- **No helpers**, with these exceptions: `trans()` / `__()` are allowed everywhere; `app()` only in
  models, traits and places where DI is genuinely hard to provide.
- Constructor property promotion. `final` classes and `readonly` wherever possible — prefer a
  `final readonly class`, otherwise readonly properties. A readonly property is public rather than
  hidden behind a getter.
- Always import with `use`; never inline `\Fully\Qualified\Names`.
- Alias the colliding `Repository` contracts:
  `use Illuminate\Contracts\Config\Repository as Config;`,
  `use Illuminate\Contracts\Cache\Repository as Cache;`.
- Name a property after its type: `TranslationImportService $translationImportService`, not `$service`.
- Build strings with `sprintf()` — no `"{$var}"` interpolation and no `.` concatenation.
- Mark overrides with `#[Override]` — **except** a method that overrides a *trait* method
  (e.g. `HasFactory::newFactory()`): PHP 8.5.3 segfaults on that.
- Before adding a native type to an overriding property/parameter, check the parent. If the parent
  is untyped (Laravel's `$fillable`, `$hidden`, a command's `$description`, …) the child must stay
  untyped too.
- Fix new phpstan/phpmd findings in code. Baselines are for accepted, existing debt only — inspect
  the baseline diff before committing it.

## Testing conventions

- PHPUnit 13 + Orchestra Testbench 11. Test namespaces mirror `src/`.
- Several tested methods of one class → a directory named after the class with one
  `<Method>Test.php` per method.
- Feature tests when several real classes collaborate; Unit tests for isolated logic (mock the
  rest). Don't write tests for service providers or install commands.
- PHPUnit assertions are static: `self::assert*()`. Laravel's instance assertions
  (`$this->assertDatabaseHas()`, response asserts) stay on `$this`.
- Resolve services with `$this->app->make()`, never `app()`.
- Test-only models and stubs live in the `tests/` root.

## Package notes

- The factories use the `config()` helper on purpose: injecting `Repository` would create a
  circular bootstrap order.
- A new trait that calls host methods needs `@phpstan-require-extends` (the framework
  `TestCase`, or `Functional\TestCase` when it calls `actingAs*`).
- `Util`'s phpmd complexity is accepted in `phpmd.baseline.xml` — it mirrors the upstream
  swagger-php DSL; don't refactor it unless that API forces it.
- `assertValidOpenApiResponseForRoute` prefixes `/` to Laravel's slash-less route URI.
- Byte-stable snapshots need the frozen Carbon *and* the deterministic admin user (id 123); pin
  `created_by_admin_user_id` / `updated_by_admin_user_id` when several admins exist.
- `src/` has no Spatie imports; `spatie/laravel-permission` is a dev dependency only.
  `tests/TestCase.php` builds the schema itself (`setUpSchema()`, `seedAdministratorRole()`).
- PHP 8.5.3 segfaults on `#[Override]` over `HasFactory::newFactory()` — see
  `tests/Models/TestAdminUserModel.php`.
- No database service: tests run on in-memory SQLite.

## Versioning

The package is on **2.x** and stays there through the Laravel 13 / PHP 8.5 upgrade — don't add
v3 upgrade sections or bump the `branch-alias`. There is no `UPGRADE.md`;
consumer-facing changes go in the release notes.
