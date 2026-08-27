<?php

use Illuminate\Contracts\Auth\Access\Gate;
use Lauthz\Tests\TestCase;

class EnforcerCustomLocalizerTest extends TestCase
{
    public function testCustomRegisterAtGatesBefore()
    {
        $user = $this->user("alice");
        $this->assertFalse($user->can('data3,read'));

        app(Gate::class)->before(function () {
            return true;
        });

        $this->assertTrue($user->can('data3,read'));
    }

    public function testCustomRegisterAtGatesDefine()
    {
        $user = $this->user("alice");
        $this->assertFalse($user->can('data3,read'));

        app(Gate::class)->define('data3,read', function () {
            return true;
        });

        $this->assertTrue($user->can('data3,read'));
    }

    public function initConfig()
    {
        parent::initConfig();
        $this->app['config']->set('lauthz.localizer.enabled_register_at_gates', false);
    }
}
