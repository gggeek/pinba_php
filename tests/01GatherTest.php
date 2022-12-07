<?php

use PinbaPhp\Polyfill\PinbaFunctions as pinba;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class GatherTest extends TestCase
{
    /**
     * @before
     */
    public function setTestUp()
    {
        // delete all existing timers and tags
        pinba::reset();
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
        $t = pinba::timer_start(array('tag1' => 'testTimer', 'tag2' => 10), array('whatever'));
        $v = pinba::timer_get_info($t);
        usleep(100000);
        $r = pinba::timer_stop($t);

        $this->assertSame(true, $r, 'timer_stop should return true for running timers');
        $this->assertSame(true, $v['started'], 'Timer started should be true before stop');
        $this->assertGreaterThan(0, $v['value'], 'Timer time should be bigger than zero after start');
        $this->assertLessThan(0.01, $v['value'], 'Timer time should be less than 0.01 secs after start');

        $v = pinba::timer_get_info($t);
        $this->assertGreaterThan(0.1, $v['value'], 'Timer time should be bigger than sleep time');
        $this->assertSame(array('tag1' => 'testTimer', 'tag2' => 10), $v['tags'], 'Timer tags should keep injected value');
        $this->assertSame(array('whatever'), $v['data'], 'Timer data should keep injected value');
        $this->assertSame(false, $v['started'], 'Timer started should be false after stop');

        $v1 = $v['value'];
        /// @todo this generates a warning. Move it to its own test, tagged as expect exception
        //$r = pinba::timer_stop($t);
        //$this->assertSame(false, $r, 'timer_stop should return false for stopped timers');

        usleep(100000);
        $v = pinba::timer_get_info($t);
        $this->assertEquals($v1, $v['value'], 'Timer time should not increase after stop');

        $v2 = pinba::get_info();
        $this->assertSame($v, $v2['timers'][0], 'get_info should return same timer as timer_get_info');

        pinba::timer_data_merge($t, array('x' => 'y'));
        $v = pinba::timer_get_info($t);
        $this->assertEquals(array('whatever', 'x' => 'y'), $v['data'], 'Timer data should have merged value');

        pinba::timer_data_replace($t, array('x' => 'y'));
        $v = pinba::timer_get_info($t);
        $this->assertEquals(array('x' => 'y'), $v['data'], 'Timer data should have replaced value');

        pinba::timer_tags_merge($t, array('x' => 'y'));
        $v = pinba::timer_get_info($t);
        $this->assertEquals(array('tag1' => 'testTimer', 'tag2' => 10, 'x' => 'y'), $v['tags'], 'Timer tags should have merged value');

        pinba::timer_tags_replace($t, array('x' => 'y'));
        $v = pinba::timer_get_info($t);
        $this->assertEquals(array('x' => 'y'), $v['tags'], 'Timer tags should have replaced value');
    }

    function testTimerAdd()
    {
        $t = pinba::timer_add(array('tag1' => 'testTimerAdd'), 2.0);
        $v = pinba::timer_get_info($t);

        $this->assertSame(false, $v['started'], 'Timer started should be false before stop');
        $this->assertSame(2.0, $v['value'], 'Timer time should be the same as set');
    }

    function testNoTimer()
    {
        $this->expectException('\PHPUnit\Framework\Error\Warning');
        $v = pinba::timer_get_info(-1);
    }

    function testBadStop1()
    {
        $v = pinba::timer_stop(-1);
        $this->assertSame(false, $v, 'Timer stop should fail on non-timer');
    }

    function testBadStop2()
    {
        $this->expectException('\PHPUnit\Framework\Error\Warning');
        $t = pinba::timer_add(array('tag1' => 'testBadStop2'), 1.0);
        $v = pinba::timer_stop($t);
        $v = pinba::timer_stop($t);
    }

    function testTimerDelete()
    {
        $t = pinba::timer_start(array('tag1' => 'testTimerDelete'));
        $r = pinba::timer_delete($t);
        $this->assertEquals(true, $r, 'the timer should have been deleted');
        $timers = pinba::timers_get();
        $this->assertEquals(0, count($timers), 'there should be no timers');
        $r = pinba::timer_delete(999);
        $this->assertEquals(false, $r, 'inexisting timer should not have been deleted');
    }

    function testGetTimers()
    {
        $t1 = pinba::timer_start(array('tag1' => 'testGetTimers_1'));
        $t2 = pinba::timer_start(array('tag1' => 'testGetTimers_2'));
        pinba::timer_stop($t1);
        $timers = pinba::timers_get();
        $this->assertEquals(2, count($timers), 'there should be 2 timers');
        $timers = pinba::timers_get(Pinba::ONLY_STOPPED_TIMERS);
        $this->assertEquals(1, count($timers), 'there should be 1 stopped timer');
        pinba::timers_stop();
        $timers = pinba::timers_get();
        $this->assertEquals(2, count($timers), 'there should be 2 timers');
        $timers = pinba::timers_get(Pinba::ONLY_STOPPED_TIMERS);
        $this->assertEquals(2, count($timers), 'there should be 2 stopped timers');
    }

    function testTags()
    {
        $v = pinba::tag_get('hey');
        $this->assertEquals(null, $v, 'there should be no tag');
        pinba::tag_set('hey', 'there');
        $v = pinba::tag_get('hey');
        $this->assertEquals('there', $v, 'there should a tag');
        pinba::tag_set('hey', 'you');
        $v = pinba::tag_get('hey');
        $this->assertEquals('you', $v, 'tag should have been modified');
        pinba::tag_set('you', 'hey');
        $v = pinba::get_info();
        $this->assertEquals(array('hey' => 'you', 'you' => 'hey'), $v['tags'], 'tags should be present');
        $v = pinba::tag_delete('hey');
        $this->assertEquals(true, $v, 'tag deletion should succeed');
        $v = pinba::tag_get('hey');
        $this->assertEquals(null, $v, 'there should be no tag');
        $v = pinba::tag_delete('hey');
        $this->assertEquals(false, $v, 'tag deletion should fail');
    }
}
