<?php

namespace Lauthz\Tests;

use Lauthz\Middlewares\RequestMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Lauthz\Models\Rule;

class RequestMiddlewareTest extends TestCase
{
    use DatabaseMigrations;

    public function testNotLogin()
    {
        $this->assertEquals($this->middleware('/foo', 'GET'), 'Unauthorized Exception');
    }

    public function testAfterLogin()
    {
        $this->login('alice');
        $this->assertEquals($this->middleware(Request::create('/foo', 'GET')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo/1', 'GET')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo', 'POST')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo/1', 'PUT')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo/1', 'DELETE')), 200);

        $this->assertEquals($this->middleware(Request::create('/foo/2', 'GET')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo/2', 'PUT')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo/2', 'DELETE')), 200);

        $this->assertEquals($this->middleware(Request::create('/foo1/123', 'GET')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo1/123', 'POST')), 200);
        $this->assertEquals($this->middleware(Request::create('/foo1/123', 'PUT')), 'Unauthorized Exception');

        $this->assertEquals($this->middleware(Request::create('/proxy', 'GET')), 'Unauthorized Exception');
    }

    protected function middleware($request)
    {
        return parent::runMiddleware(RequestMiddleware::class, $request);
    }

    protected function initConfig()
    {
        parent::initConfig();
        $this->app['config']->set('lauthz.basic.model.config_type', 'text');
        $text = <<<'EOT'
[request_definition]
r = sub, obj, act

[policy_definition]
p = sub, obj, act

[role_definition]
g = _, _

[policy_effect]
e = some(where (p.eft == allow))

[matchers]
m = g(r.sub, p.sub) && r.sub == p.sub && keyMatch2(r.obj, p.obj) && regexMatch(r.act, p.act)
EOT;
        $this->app['config']->set('lauthz.basic.model.config_text', $text);
    }

    protected function initTable()
    {
        Rule::truncate();

        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => '/foo', 'v2' => 'GET']);
        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => '/foo/:id', 'v2' => 'GET']);
        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => '/foo', 'v2' => 'POST']);
        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => '/foo/:id', 'v2' => 'PUT']);
        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => '/foo/:id', 'v2' => 'DELETE']);
        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => '/foo1/*', 'v2' => '(GET)|(POST)']);
    }
}
