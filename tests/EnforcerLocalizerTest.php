<?php

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Lauthz\Facades\Enforcer;
use Lauthz\Tests\TestCase;

class EnforcerLocalizerTest extends TestCase
{
    use DatabaseMigrations;

    public function testRegisterAtGates()
    {
        $user = $this->user('alice');
        $this->assertTrue($user->can('data1,read'));
        $this->assertFalse($user->can('data1,write'));
        $this->assertFalse($user->cannot('data2,read'));

        Enforcer::guard('second')->addPolicy('alice', 'data1', 'read');
        $this->assertTrue($user->can('data1,read', 'second'));
        $this->assertFalse($user->can('data3,read', 'second'));
    }

    public function testNotLogin()
    {
        $this->assertFalse(app(Gate::class)->allows('data1,read'));
        $this->assertTrue(app(Gate::class)->forUser($this->user('alice'))->allows('data1,read'));
        $this->assertFalse(app(Gate::class)->forUser($this->user('bob'))->allows('data1,read'));
    }

    public function testAfterLogin()
    {
        $this->login('alice');
        $this->assertTrue(app(Gate::class)->allows('data1,read'));
        $this->assertTrue(app(Gate::class)->allows('data2,read'));
        $this->assertTrue(app(Gate::class)->allows('data2,write'));

        $this->login('bob');
        $this->assertFalse(app(Gate::class)->allows('data1,read'));
        $this->assertTrue(app(Gate::class)->allows('data2,write'));
    }

    public function initConfig()
    {
        parent::initConfig();
        $this->app['config']->set('lauthz.second.model.config_type', 'text');
        $this->app['config']->set(
            'lauthz.second.model.config_text',
            $this->getModelText()
        );
    }

    protected function getModelText(): string
    {
        return <<<EOT
[request_definition]
r = sub, obj, act

[policy_definition]
p = sub, obj, act

[policy_effect]
e = some(where (p.eft == allow))

[matchers]
m = r.sub == p.sub && r.obj == p.obj && r.act == p.act
EOT;
    }
}
