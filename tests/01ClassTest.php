<?php

use PinbaPhp\Polyfill\Pinba as pinba;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ClassTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setEnvUp()
    {
        ini_set('pinba.enabled', 1);
        ini_set('pinba.server', getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT'));
    }

    function testGetInfo()
    {
        $v = pinba::get_info();
        $this->assertGreaterThan(0, $v['req_time'], 'Request time should be bigger than 0');
        $this->assertGreaterThan(1024000, $v['mem_peak_usage'], 'Used mem time should be bigger than 1MB');
        $this->assertSame('php', $v['hostname'], 'Host name should default to php for cli scripts');
        $this->assertSame('./vendor/bin/phpunit', $v['script_name'], 'Script name should match phpunit runner');
        $this->assertSame('unknown', $v['server_name'], 'Server name should be unknown');
        /// @todo
        //$this->assertSame(1, $v['req_count'], 'Req count should be 1 for cli scripts');

        pinba::hostname_set('hello');
        pinba::script_name_set('world.php');
    }

    function testSetInfo()
    {
        pinba::hostname_set('hello');
        pinba::script_name_set('world.php');
        $v = pinba::get_info();
        $this->assertSame('hello', $v['hostname'], 'Host name should match what was set');
        $this->assertSame('world.php', $v['script_name'], 'Script name should match what was set');
    }

    function testTimer()
    {
        $t = pinba::timer_start(array('tag1', 'tag2'), 'whatever');
        sleep(1);
        $r = pinba::timer_stop($t);
        $this->assertSame(true, $r, 'timer_stop should return true for running timers');
        $r = pinba::timer_stop($t);
        $this->assertSame(false, $r, 'timer_stop should return false for stopped timers');
        $v = pinba::timer_get_info($t);
        $this->assertGreaterThan(1.0, $v['value'], 'Timer time should be bigger than 1 sec');
        $this->assertSame(array('tag1', 'tag2'), $v['tags'], 'Timer tags should keep injected value');
        $this->assertSame('whatever', $v['data'], 'Timer data should keep injected value');
        $this->assertSame(false, $v['started'], 'Timer started should be false after stop');
    }
}
