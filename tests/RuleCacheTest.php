<?php

namespace Lauthz\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Lauthz\Models\Rule;

class RuleCacheTest extends TestCase
{
    use DatabaseMigrations;

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

    protected function initTable()
    {
        Rule::truncate();
    }
}
