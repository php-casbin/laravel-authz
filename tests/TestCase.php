<?php

namespace Lauthz\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Lauthz\Exceptions\UnauthorizedException;
use Lauthz\Models\Rule;
use Lauthz\Tests\Models\User;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Enforcer', \Lauthz\Facades\Enforcer::class);
        });

        $this->app->make(Kernel::class)->bootstrap();

        $this->app->register(\Lauthz\LauthzServiceProvider::class);
        $this->initConfig();

        $this->artisan('vendor:publish', ['--provider' => 'Lauthz\LauthzServiceProvider']);
        $this->artisan('migrate', ['--force' => true]);

        $this->afterApplicationCreated(function () {
            $this->initTable();
        });

        return $this->app;
    }

    protected function initConfig()
    {
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql.charset', 'utf8');
        $this->app['config']->set('database.connections.mysql.collation', 'utf8_unicode_ci');
        $this->app['config']->set('cache.default', 'array');
        // $app['config']->set('lauthz.log.enabled', true);
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

    protected function login($name)
    {
        Auth::login($this->user($name));
    }

    protected function user($name)
    {
        $user = new User();
        $user->name = $name;

        return $user;
    }
}
