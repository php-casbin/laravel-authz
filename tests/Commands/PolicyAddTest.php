<?php

namespace Lauthz\Tests\Commands;

use Lauthz\Facades\Enforcer;
use Lauthz\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

class PolicyAddTest extends TestCase
{
    use DatabaseMigrations;

    public function testHandle()
    {
        $this->assertFalse(Enforcer::enforce('eve', 'articles', 'read'));
        $exitCode = Artisan::call('policy:add', ['policy' => 'eve, articles, read']);
        $this->assertTrue(0 === $exitCode);
        $this->assertTrue(Enforcer::enforce('eve', 'articles', 'read'));

        $exitCode = Artisan::call('policy:add', ['policy' => 'eve, articles, read']);
        $this->assertFalse(0 === $exitCode);
    }
}
