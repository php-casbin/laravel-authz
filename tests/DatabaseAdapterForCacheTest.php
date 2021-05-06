<?php

namespace Lauthz\Tests;

use Enforcer;
use Lauthz\Models\Rule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Casbin\Persist\Adapters\Filter;
use Casbin\Exceptions\InvalidFilterTypeException;

class DatabaseAdapterForCacheTest extends TestCase
{

    use DatabaseMigrations;

    public function testAddPolicy()
    {
        $this->enableCache();
        $this->assertFalse(Enforcer::enforce('eve', 'data3', 'read'));
        Enforcer::addPermissionForUser('eve', 'data3', 'read');
        $this->refreshPolicies();
        $this->assertTrue(Enforcer::enforce('eve', 'data3', 'read'));
    }

    public function testAddPolicies()
    {
        $this->enableCache();
        $policies = [
            ['u1', 'd1', 'read'],
            ['u2', 'd2', 'read'],
            ['u3', 'd3', 'read'],
        ];
        $this->refreshPolicies();
        Rule::truncate();
        Enforcer::addPolicies($policies);
        $this->refreshPolicies();
        $this->assertEquals($policies, Enforcer::getPolicy());
    }

    public function testSavePolicy()
    {
        $this->enableCache();
        $this->assertFalse(Enforcer::enforce('alice', 'data4', 'read'));

        $model = Enforcer::getModel();
        $model->clearPolicy();
        $model->addPolicy('p', 'p', ['alice', 'data4', 'read']);

        $adapter = Enforcer::getAdapter();
        $adapter->savePolicy($model);
        $this->refreshPolicies();
        $this->assertTrue(Enforcer::enforce('alice', 'data4', 'read'));
    }

    public function testRemovePolicy()
    {
        $this->enableCache();
        $this->assertFalse(Enforcer::enforce('alice', 'data5', 'read'));

        Enforcer::addPermissionForUser('alice', 'data5', 'read');
        $this->refreshPolicies();
        $this->assertTrue(Enforcer::enforce('alice', 'data5', 'read'));

        Enforcer::deletePermissionForUser('alice', 'data5', 'read');
        $this->refreshPolicies();
        $this->assertFalse(Enforcer::enforce('alice', 'data5', 'read'));
    }

    public function testRemovePolicies()
    {
        $this->enableCache();
        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
                ], Enforcer::getPolicy());

        Enforcer::removePolicies([
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
        ]);
        $this->refreshPolicies();
        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write']
                ], Enforcer::getPolicy());
    }

    public function testRemoveFilteredPolicy()
    {
        $this->enableCache();
        $this->assertTrue(Enforcer::enforce('alice', 'data1', 'read'));
        Enforcer::removeFilteredPolicy(1, 'data1');
        $this->refreshPolicies();
        $this->assertFalse(Enforcer::enforce('alice', 'data1', 'read'));
        $this->assertTrue(Enforcer::enforce('bob', 'data2', 'write'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'read'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'write'));
        Enforcer::removeFilteredPolicy(1, 'data2', 'read');
        $this->refreshPolicies();
        $this->assertTrue(Enforcer::enforce('bob', 'data2', 'write'));
        $this->assertFalse(Enforcer::enforce('alice', 'data2', 'read'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'write'));
        Enforcer::removeFilteredPolicy(2, 'write');
        $this->refreshPolicies();
        $this->assertFalse(Enforcer::enforce('bob', 'data2', 'write'));
        $this->assertFalse(Enforcer::enforce('alice', 'data2', 'write'));
    }

    public function testUpdatePolicy()
    {
        $this->enableCache();
        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
                ], Enforcer::getPolicy());

        Enforcer::updatePolicy(
                ['alice', 'data1', 'read'],
                ['alice', 'data1', 'write']
        );

        Enforcer::updatePolicy(
                ['bob', 'data2', 'write'],
                ['bob', 'data2', 'read']
        );
        $this->refreshPolicies();
        $this->assertEquals([
            ['alice', 'data1', 'write'],
            ['bob', 'data2', 'read'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
                ], Enforcer::getPolicy());
    }

    protected function refreshPolicies()
    {
        Enforcer::loadPolicy();
    }

    protected function enableCache()
    {
        $this->app['config']->set('lauthz.basic.cache.enabled', true);
    }

}
