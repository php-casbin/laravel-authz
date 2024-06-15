<?php

namespace Lauthz\Tests;

use Lauthz\Facades\Enforcer;
use InvalidArgumentException;
use RuntimeException;


class ModelLoaderTest extends TestCase
{
    public function testUrlLoader(): void
    {
        $this->initUrlConfig();

        $this->assertFalse(Enforcer::enforce('alice', 'data', 'read'));

        Enforcer::addPolicy('data_admin', 'data', 'read');
        Enforcer::addRoleForUser('alice', 'data_admin');
        $this->assertTrue(Enforcer::enforce('alice', 'data', 'read'));
    }

    public function testTextLoader(): void
    {
        $this->initTextConfig();

        Enforcer::addPolicy('data_admin', 'data', 'read');
        $this->assertFalse(Enforcer::enforce('alice', 'data', 'read'));
        $this->assertTrue(Enforcer::enforce('data_admin', 'data', 'read'));
    }

    public function testFileLoader(): void
    {
        $this->assertFalse(Enforcer::enforce('alice', 'data', 'read'));

        Enforcer::addPolicy('data_admin', 'data', 'read');
        Enforcer::addRoleForUser('alice', 'data_admin');
        $this->assertTrue(Enforcer::enforce('alice', 'data', 'read'));
    }

    public function testCustomLoader(): void
    {
        $this->initCustomConfig();
        Enforcer::guard('second')->addPolicy('data_admin', 'data', 'read');
        $this->assertFalse(Enforcer::guard('second')->enforce('alice', 'data', 'read'));
        $this->assertTrue(Enforcer::guard('second')->enforce('data_admin', 'data', 'read'));
    }

    public function testMultipleLoader(): void
    {
        $this->testFileLoader();
        $this->testCustomLoader();
    }

    public function testEmptyModel(): void
    {
        Enforcer::shouldUse('third');
        $this->expectException(InvalidArgumentException::class);
        $this->assertFalse(Enforcer::enforce('alice', 'data', 'read'));
    }

    public function testEmptyLoaderType(): void
    {
        $this->app['config']->set('lauthz.basic.model.config_type', '');
        $this->expectException(InvalidArgumentException::class);

        $this->assertFalse(Enforcer::enforce('alice', 'data', 'read'));
    }

    public function testBadUlrConnection(): void
    {
        $this->initUrlConfig();
        $this->app['config']->set('lauthz.basic.model.config_url', 'http://filenoexists');
        $this->expectException(RuntimeException::class);

        $this->assertFalse(Enforcer::enforce('alice', 'data', 'read'));
    }

    protected function initUrlConfig(): void
    {
        $this->app['config']->set('lauthz.basic.model.config_type', 'url');
        $this->app['config']->set(
            'lauthz.basic.model.config_url',
            'https://raw.githubusercontent.com/casbin/casbin/master/examples/rbac_model.conf'
        );
    }

    protected function initTextConfig(): void
    {
        $this->app['config']->set('lauthz.basic.model.config_type', 'text');
        $this->app['config']->set(
            'lauthz.basic.model.config_text',
            $this->getModelText()
        );
    }

    protected function initCustomConfig(): void {
        $this->app['config']->set('lauthz.second.model.config_loader_class', '\Lauthz\Loaders\TextLoader');
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