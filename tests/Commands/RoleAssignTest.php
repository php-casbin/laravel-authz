<?php

namespace Lauthz\Tests\Commands;

use Lauthz\Facades\Enforcer;
use Lauthz\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

class RoleAssignTest extends TestCase
{
    use DatabaseMigrations;

    public function testHandle()
    {
        $this->assertFalse(Enforcer::hasRoleForUser('eve', 'writer'));
        $exitCode = Artisan::call('role:assign', ['user' => 'eve', 'role' => 'writer']);
        $this->assertTrue(0 === $exitCode);
        $exitCode = Artisan::call('role:assign', ['user' => 'eve', 'role' => 'writer']);
        $this->assertFalse(0 === $exitCode);
        $this->assertTrue(Enforcer::hasRoleForUser('eve', 'writer'));
    }
}
