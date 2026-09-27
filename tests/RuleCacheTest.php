<?php

namespace Lauthz\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lauthz\Adapters\DatabaseAdapter;
use Lauthz\Facades\Enforcer;
use Lauthz\Models\Rule;

class RuleCacheTest extends TestCase
{
    public function testEnableCache()
    {
        $this->enableCache();

        DB::connection()->enableQueryLog();

        app(Rule::class)->forgetCache();

        app(Rule::class)->getAllFromCache();
        $this->assertCount(1, DB::getQueryLog());

        app(Rule::class)->getAllFromCache();
        $this->assertCount(1, DB::getQueryLog());

        DB::flushQueryLog();
        app(Rule::class)->getAllFromCache();
        $this->assertCount(0, DB::getQueryLog());

        $rule = Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => 'data1', 'v2' => 'read']);
        app(Rule::class)->getAllFromCache();
        $this->assertCount(2, DB::getQueryLog());

        $rule->delete();
        app(Rule::class)->getAllFromCache();
        app(Rule::class)->getAllFromCache();
        $this->assertCount(4, DB::getQueryLog());

        DB::flushQueryLog();
    }

    public function testDisableCache()
    {
        $this->app['config']->set('lauthz.basic.cache.enabled', false);

        DB::connection()->enableQueryLog();
        app(Rule::class)->getAllFromCache();
        $this->assertCount(1, DB::getQueryLog());

        $rule = Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => 'data1', 'v2' => 'read']);
        app(Rule::class)->getAllFromCache();
        $this->assertCount(3, DB::getQueryLog());

        $rule->delete();
        app(Rule::class)->getAllFromCache();
        app(Rule::class)->getAllFromCache();
        $this->assertCount(6, DB::getQueryLog());

        DB::flushQueryLog();
    }

    protected function enableCache()
    {
        $this->app['config']->set('lauthz.basic.cache.enabled', true);
    }

    public function testCacheKeysAreIsolatedBetweenGuards()
    {
        $this->enableCache();

        Schema::dropIfExists('rules2');
        Schema::create('rules2', function ($table) {
            $table->increments('id');
            $table->string('ptype')->nullable();
            $table->string('v0')->nullable();
            $table->string('v1')->nullable();
            $table->string('v2')->nullable();
            $table->string('v3')->nullable();
            $table->string('v4')->nullable();
            $table->string('v5')->nullable();
            $table->timestamps();
        });

        $this->app['config']->set('lauthz.second', [
            'model' => [
                'config_type' => 'text',
                'config_text' => $this->getModelText(),
            ],
            'adapter' => DatabaseAdapter::class,
            'database' => [
                'rules_table' => 'rules2',
            ],
            'cache' => [
                'enabled' => true,
                // same key as the default of every other guard: with the old
                // behavior both guards would share a single cache entry.
                'key' => 'rules',
            ],
        ]);

        Rule::create(['ptype' => 'p', 'v0' => 'alice', 'v1' => 'data1', 'v2' => 'read']);
        (new Rule(['ptype' => 'p', 'v0' => 'bob', 'v1' => 'data2', 'v2' => 'write'], 'second'))->save();

        $basic = new Rule([], 'basic');
        $second = new Rule([], 'second');

        $basicRows = $basic->getAllFromCache();
        $this->assertCount(1, $basicRows);
        $this->assertSame('alice', $basicRows[0]['v0']);

        $secondRows = $second->getAllFromCache();
        $this->assertCount(1, $secondRows);
        $this->assertSame('bob', $secondRows[0]['v0']);

        $this->assertTrue(Enforcer::guard('second')->enforce('bob', 'data2', 'write'));
        $this->assertFalse(Enforcer::guard('second')->enforce('alice', 'data1', 'read'));

        Schema::dropIfExists('rules2');
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

    protected function initTable()
    {
        Rule::truncate();
    }
}
