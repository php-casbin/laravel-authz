<?php

namespace Lauthz\Tests;

use Lauthz\Middlewares\EnforcerMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;

class EnforcerMiddlewareTest extends TestCase
{
    use DatabaseMigrations;

    public function testNotLogin()
    {
        $this->assertEquals($this->middleware('data1', 'read'), 'Unauthorized Exception');
    }

    public function testAfterLogin()
    {
        $this->login('alice');
        $this->assertEquals($this->middleware('data1', 'read'), 200);
        $this->assertEquals($this->middleware('data2', 'read'), 200);
        $this->assertEquals($this->middleware('data2', 'write'), 200);

        $this->login('bob');
        $this->assertEquals($this->middleware('data1', 'read'), 'Unauthorized Exception');
        $this->assertEquals($this->middleware('data2', 'write'), 200);
    }

    protected function middleware(...$args)
    {
        return parent::runMiddleware(EnforcerMiddleware::class, new Request(), ...$args);
    }
}
