<?php

namespace Lauthz\Tests;

use Enforcer;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Casbin\Persist\Adapters\Filter;
use Casbin\Exceptions\InvalidFilterTypeException;

class DatabaseAdapterTest extends TestCase
{
    use DatabaseMigrations;

    public function testEnforce()
    {
        $this->assertTrue(Enforcer::enforce('alice', 'data1', 'read'));

        $this->assertFalse(Enforcer::enforce('bob', 'data1', 'read'));
        $this->assertTrue(Enforcer::enforce('bob', 'data2', 'write'));

        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'read'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'write'));
    }

    public function testAddPolicy()
    {
        $this->assertFalse(Enforcer::enforce('eve', 'data3', 'read'));
        Enforcer::addPermissionForUser('eve', 'data3', 'read');
        $this->assertTrue(Enforcer::enforce('eve', 'data3', 'read'));
    }

    public function testAddPolicies()
    {
        $policies = [
            ['u1', 'd1', 'read'],
            ['u2', 'd2', 'read'],
            ['u3', 'd3', 'read'],
        ];
        Enforcer::clearPolicy();
        $this->initTable();
        $this->assertEquals([], Enforcer::getPolicy());
        Enforcer::addPolicies($policies);
        $this->assertEquals($policies, Enforcer::getPolicy());
    }

    public function testSavePolicy()
    {
        $this->assertFalse(Enforcer::enforce('alice', 'data4', 'read'));

        $model = Enforcer::getModel();
        $model->clearPolicy();
        $model->addPolicy('p', 'p', ['alice', 'data4', 'read']);

        $adapter = Enforcer::getAdapter();
        $adapter->savePolicy($model);
        $this->assertTrue(Enforcer::enforce('alice', 'data4', 'read'));
    }

    public function testRemovePolicy()
    {
        $this->assertFalse(Enforcer::enforce('alice', 'data5', 'read'));

        Enforcer::addPermissionForUser('alice', 'data5', 'read');
        $this->assertTrue(Enforcer::enforce('alice', 'data5', 'read'));

        Enforcer::deletePermissionForUser('alice', 'data5', 'read');
        $this->assertFalse(Enforcer::enforce('alice', 'data5', 'read'));
    }

    public function testRemovePolicies()
    {
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

        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write']
        ], Enforcer::getPolicy());
    }

    public function testRemoveFilteredPolicy()
    {
        $this->assertTrue(Enforcer::enforce('alice', 'data1', 'read'));
        Enforcer::removeFilteredPolicy(1, 'data1');
        $this->assertFalse(Enforcer::enforce('alice', 'data1', 'read'));
        $this->assertTrue(Enforcer::enforce('bob', 'data2', 'write'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'read'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'write'));
        Enforcer::removeFilteredPolicy(1, 'data2', 'read');
        $this->assertTrue(Enforcer::enforce('bob', 'data2', 'write'));
        $this->assertFalse(Enforcer::enforce('alice', 'data2', 'read'));
        $this->assertTrue(Enforcer::enforce('alice', 'data2', 'write'));
        Enforcer::removeFilteredPolicy(2, 'write');
        $this->assertFalse(Enforcer::enforce('bob', 'data2', 'write'));
        $this->assertFalse(Enforcer::enforce('alice', 'data2', 'write'));
    }

    public function testUpdatePolicy()
    {
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

        $this->assertEquals([
            ['alice', 'data1', 'write'],
            ['bob', 'data2', 'read'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
        ], Enforcer::getPolicy());
    }

    public function testUpdatePolicies()
    {
        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
        ], Enforcer::getPolicy());

        $oldPolicies = [
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write']
        ];
        $newPolicies = [
            ['alice', 'data1', 'write'],
            ['bob', 'data2', 'read']
        ];

        Enforcer::updatePolicies($oldPolicies, $newPolicies);

        $this->assertEquals([
            ['alice', 'data1', 'write'],
            ['bob', 'data2', 'read'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
        ], Enforcer::getPolicy());
    }

    public function arrayEqualsWithoutOrder(array $expected, array $actual)
    {
        if (method_exists($this, 'assertEqualsCanonicalizing')) {
            $this->assertEqualsCanonicalizing($expected, $actual);
        } else {
            array_multisort($expected);
            array_multisort($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    public function testUpdateFilteredPolicies()
    {
        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
        ], Enforcer::getPolicy());

        Enforcer::updateFilteredPolicies([["alice", "data1", "write"]], 0, "alice", "data1", "read");
        Enforcer::updateFilteredPolicies([["bob", "data2", "read"]], 0, "bob", "data2", "write");

        $policies = [
            ['alice', 'data1', 'write'],
            ['bob', 'data2', 'read'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write']
        ];

        $this->arrayEqualsWithoutOrder($policies, Enforcer::getPolicy());

        // test use updateFilteredPolicies to update all policies of a user
        $this->initTable();
        Enforcer::loadPolicy();
        $policies = [
            ['alice', 'data2', 'write'],
            ['bob', 'data1', 'read']
        ];
        Enforcer::addPolicies($policies);

        $this->arrayEqualsWithoutOrder([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
            ['alice', 'data2', 'write'],
            ['bob', 'data1', 'read']
        ], Enforcer::getPolicy());

        Enforcer::updateFilteredPolicies([['alice', 'data1', 'write'], ['alice', 'data2', 'read']], 0, 'alice');
        Enforcer::updateFilteredPolicies([['bob', 'data1', 'write'], ["bob", "data2", "read"]], 0, 'bob');

        $policies = [
            ['alice', 'data1', 'write'],
            ['alice', 'data2', 'read'],
            ['bob', 'data1', 'write'],
            ['bob', 'data2', 'read'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write']
        ];

        $this->arrayEqualsWithoutOrder($policies, Enforcer::getPolicy());

        // test if $fieldValues contains empty string
        $this->initTable();
        Enforcer::loadPolicy();
        $policies = [
            ['alice', 'data2', 'write'],
            ['bob', 'data1', 'read']
        ];
        Enforcer::addPolicies($policies);

        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
            ['alice', 'data2', 'write'],
            ['bob', 'data1', 'read']
        ], Enforcer::getPolicy());

        Enforcer::updateFilteredPolicies([['alice', 'data1', 'write'], ['alice', 'data2', 'read']], 0, 'alice', '', '');
        Enforcer::updateFilteredPolicies([['bob', 'data1', 'write'], ["bob", "data2", "read"]], 0, 'bob', '', '');

        $policies = [
            ['alice', 'data1', 'write'],
            ['alice', 'data2', 'read'],
            ['bob', 'data1', 'write'],
            ['bob', 'data2', 'read'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write']
        ];

        $this->arrayEqualsWithoutOrder($policies, Enforcer::getPolicy());

        // test if $fieldIndex is not zero
        $this->initTable();
        Enforcer::loadPolicy();
        $policies = [
            ['alice', 'data2', 'write'],
            ['bob', 'data1', 'read']
        ];
        Enforcer::addPolicies($policies);

        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['bob', 'data2', 'write'],
            ['data2_admin', 'data2', 'read'],
            ['data2_admin', 'data2', 'write'],
            ['alice', 'data2', 'write'],
            ['bob', 'data1', 'read']
        ], Enforcer::getPolicy());

        Enforcer::updateFilteredPolicies([['alice', 'data1', 'write'], ['bob', 'data1', 'write']], 2, 'read');
        Enforcer::updateFilteredPolicies([['alice', 'data2', 'read'], ["bob", "data2", "read"]], 2, 'write');

        $policies = [
            ['alice', 'data2', 'read'],
            ['bob', 'data2', 'read'],
        ];

        $this->arrayEqualsWithoutOrder($policies, Enforcer::getPolicy());
    }

    public function testLoadFilteredPolicy()
    {
        $this->initTable();
        Enforcer::clearPolicy();
        $this->initConfig();
        $adapter = Enforcer::getAdapter();
        $adapter->setFiltered(true);
        $this->assertEquals([], Enforcer::getPolicy());

        // invalid filter type
        try {
            $filter = ['alice', 'data1', 'read'];
            Enforcer::loadFilteredPolicy($filter);
            $e = InvalidFilterTypeException::class;
            $this->fail("Expected exception $e not thrown");
        } catch (InvalidFilterTypeException $e) {
            $this->assertEquals("invalid filter type", $e->getMessage());
        }

        // string
        $filter = "v0 = 'bob'";
        Enforcer::loadFilteredPolicy($filter);
        $this->assertEquals([
            ['bob', 'data2', 'write']
        ], Enforcer::getPolicy());
        
        // Filter
        $filter = new Filter(['v2'], ['read']);
        Enforcer::loadFilteredPolicy($filter);
        $this->assertEquals([
            ['alice', 'data1', 'read'],
            ['data2_admin', 'data2', 'read'],
        ], Enforcer::getPolicy());

        // Closure
        Enforcer::loadFilteredPolicy(function ($query) {
            $query->where('v1', 'data1');
        });

        $this->assertEquals([
            ['alice', 'data1', 'read'],
        ], Enforcer::getPolicy());
    }
}
