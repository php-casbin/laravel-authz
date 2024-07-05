<?php

namespace Lauthz\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;

class GatesAuthorizationTest extends TestCase
{
    use DatabaseMigrations;

    public function testNotLogin()
    {
        $this->assertFalse(Gate::allows('enforcer', ['data1', 'read']));
    }

    public function testAfterLogin()
    {
        $this->login('alice');
        $this->assertTrue(Gate::allows('enforcer', ['data1', 'read']));
        $this->assertTrue(Gate::allows('enforcer', ['data2', 'read']));
        $this->assertTrue(Gate::allows('enforcer', ['data2', 'write']));

        $this->login('bob');
        $this->assertFalse(Gate::allows('enforcer', ['data1', 'read']));
        $this->assertTrue(Gate::allows('enforcer', ['data2', 'write']));
    }
}
