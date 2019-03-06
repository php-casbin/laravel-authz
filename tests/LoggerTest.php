<?php

namespace Lauthz\Tests;

use Mockery as m;
use Monolog\Logger as Monolog;
use Lauthz\Logger;

class LoggerTest extends TestCase
{
    public function testLogger()
    {
        if (class_exists(\Illuminate\Log\Logger::class)) {
            $writer = new \Illuminate\Log\Logger($monolog = m::mock(Monolog::class));
        } else {
            $writer = new \Illuminate\Log\Writer($monolog = m::mock(Monolog::class));
        }

        $logger = new Logger($writer);

        $logger->enableLog(false);
        $this->assertFalse($logger->isEnabled());

        $logger->enableLog(true);
        $this->assertTrue($logger->isEnabled());

        $monolog->shouldReceive('info')->once()->with('foo', []);
        $logger->write('foo');

        $monolog->shouldReceive('info')->once()->with('foo1foo2', []);
        $logger->write('foo1', 'foo2');

        $monolog->shouldReceive('info')->once()->with(json_encode(['foo1', 'foo2']), []);
        $logger->write(['foo1', 'foo2']);

        $monolog->shouldReceive('info')->once()->with(sprintf('There are %u million cars in %s.', 2, 'Shanghai'), []);
        $logger->writef('There are %u million cars in %s.', 2, 'Shanghai');
    }
}
