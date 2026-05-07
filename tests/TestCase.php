<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests;

use DejwCake\TestingKit\Functional\TestCase as FunctionalTestCase;
use DejwCake\TestingKit\TestingKitServiceProvider;
use DejwCake\TestingKit\Tests\Models\TestAdminUserModel;
use DejwCake\TestingKit\Tests\Models\TestUserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Override;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends FunctionalTestCase
{
    use CreatesApplication;

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @param Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TestingKitServiceProvider::class,
            PermissionServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:fhmTMYCgGleEXIJlSQqEVAisVbsHiwxYwG7Vzs6QdlA=');
        $app['config']->set('app.locale', 'en');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'admin_users',
        ]);
        $app['config']->set('auth.providers.admin_users', [
            'driver' => 'eloquent',
            'model' => TestAdminUserModel::class,
        ]);
        $app['config']->set('auth.providers.users.model', TestUserModel::class);

        $app['config']->set('admin-auth.defaults.guard', 'admin');

        $app['config']->set('testing-kit.user_model', TestUserModel::class);
        $app['config']->set('testing-kit.admin_user_model', TestAdminUserModel::class);
        $app['config']->set('testing-kit.openapi.regenerate_on_init', false);
    }

    #[Override]
    protected function afterRefreshingDatabase(): void
    {
        $this->setUpSchema();
        $this->seedAdministratorRole();
    }

    private function setUpSchema(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $schema->create('users', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
        });

        $schema->create('admin_users', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->boolean('activated')->default(false);
            $table->boolean('forbidden')->default(false);
            $table->string('language', 2)->default('en');
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes('deleted_at');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        $schema->create('permissions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        $schema->create('roles', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        $schema->create('model_has_permissions', static function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(
                ['permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary',
            );
        });

        $schema->create('model_has_roles', static function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(
                ['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary',
            );
        });

        $schema->create('role_has_permissions', static function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    private function seedAdministratorRole(): void
    {
        $this->app['db']->connection()->table('roles')->insert([
            'name' => 'Administrator',
            'guard_name' => 'admin',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }
}
