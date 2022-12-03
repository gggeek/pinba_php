<?php

use PinbaPhp\Polyfill\Pinba as pinba;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ClassTest extends TestCase
{
    /**
     * @before
     */
    public function setTestUp()
    {
        /// @todo delete all existing timers
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
        $t = pinba::timer_start(array('tag1' => 'hello', 'tag2' => 10), array('whatever'));
        $v = pinba::timer_get_info($t);
        sleep(1);
        $r = pinba::timer_stop($t);

        $this->assertSame(true, $r, 'timer_stop should return true for running timers');
        $this->assertSame(true, $v['started'], 'Timer started should be true before stop');
        $this->assertGreaterThan(0, $v['value'], 'Timer time should be bigger than zero after start');
        $this->assertLessThan(0.1, $v['value'], 'Timer time should be less than 0.1 secs after start');

        $v = pinba::timer_get_info($t);
        $this->assertGreaterThan(1.0, $v['value'], 'Timer time should be bigger than sleep time');
        $this->assertSame(array('tag1' => 'hello', 'tag2' => 10), $v['tags'], 'Timer tags should keep injected value');
        $this->assertSame(array('whatever'), $v['data'], 'Timer data should keep injected value');
        $this->assertSame(false, $v['started'], 'Timer started should be false after stop');

        $r = pinba::timer_stop($t);
        $this->assertSame(false, $r, 'timer_stop should return false for stopped timers');

        sleep(1);
        $v = pinba::timer_get_info($t);
        $this->assertLessThan(1.5, $v['value'], 'Timer time should not increase after stop');

        $v2 = pinba::get_info();
        $this->assertSame($v, $v2['timers'][0], 'get_info should return same timer as timer_get_info');
    }
}
