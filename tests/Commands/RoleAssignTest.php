<?php

namespace Lauthz\Tests\Commands;

use Casbin\Model\Model;
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

        $model = Model::newModel();
        $model->addDef('r', 'r', 'sub, obj, act');
        $model->addDef('p', 'p', 'sub, obj, act');
        $model->addDef('g', 'g', '_, _');
        $model->addDef('g', 'g2', '_, _');
        $model->addDef('e', 'e', 'some(where (p.eft == allow))');
        $model->addDef('m', 'm', 'g(r.sub, p.sub) && g2(r.obj, p.obj) && r.act == p.act');
        Enforcer::setModel($model);
        Enforcer::loadPolicy();
        $this->assertFalse(Enforcer::hasNamedGroupingPolicy('g2', 'eve', 'writer'));
        $exitCode = Artisan::call('role:assign', ['user' => 'eve', 'role' => 'writer', '--ptype' => 'g2']);
        $this->assertTrue(0 === $exitCode);
        $exitCode = Artisan::call('role:assign', ['user' => 'eve', 'role' => 'writer', '--ptype' => 'g2']);
        $this->assertFalse(0 === $exitCode);
        $this->assertTrue(Enforcer::hasNamedGroupingPolicy('g2', 'eve', 'writer'));
    }
}
