<?php

namespace Lauthz\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Lauthz\Exceptions\UnauthorizedException;
use Lauthz\Facades\Enforcer;
use Lauthz\LauthzServiceProvider;
use Lauthz\Models\Rule;
use Lauthz\Tests\Models\User;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LauthzServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<string, class-string<\Illuminate\Support\Facades\Facade>>
     */
    protected function getPackageAliases($app)
    {
        return [
            'Enforcer' => Enforcer::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $this->app = $app;
        $config = require __DIR__ . '/../config/lauthz.php';
        $app['config']->set('lauthz', $config);
        if ($app['config']->get('database.default') === 'mysql') {
            $app['config']->set('database.connections.mysql.charset', 'utf8');
            $app['config']->set('database.connections.mysql.collation', 'utf8_unicode_ci');
        }
        $this->initConfig();
    }

    protected function initConfig()
    {
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * The parameters to use with the migrate:fresh command.
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing()
    {
        return [
            '--path' => realpath(__DIR__ . '/../database/migrations'),
            '--realpath' => true,
        ];
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->initTable();
    }

    protected function initTable()
    {
        Rule::truncate();

        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => 'data1', 'v2' => 'read']);
        Rule::create(['ptype' => 'p', 'v0' => 'bob', 'v1' => 'data2', 'v2' => 'write']);

        Rule::create(['ptype' => 'p', 'v0' => 'data2_admin', 'v1' => 'data2', 'v2' => 'read']);
        Rule::create(['ptype' => 'p', 'v0' => 'data2_admin', 'v1' => 'data2', 'v2' => 'write']);
        Rule::create(['ptype' => 'g', 'v0' => 'alice', 'v1' => 'data2_admin']);
    }

    protected function runMiddleware($middleware, $request, ...$args)
    {
        $middleware = $this->app->make($middleware);
        try {
            return $middleware->handle($request, function () {
                return (new Response())->setContent('<html></html>');
            }, ...$args)->status();
        } catch (UnauthorizedException $e) {
            return 'Unauthorized Exception';
        }

        return 'Exception';
    }

    protected function login($name): void
    {
        Auth::login($this->user($name));
    }

    protected function user($name): User
    {
        $user = new User();
        $user->name = $name;

        return $user;
    }
}
