<?php

include_once('APITest.php');

use PinbaPhp\Polyfill\PinbaFunctions as pinba;

class GatherTest extends APITest
{
    /**
     * @before
     */
    public function setTestUp()
    {
        // delete all existing timers and tags
        $this->pReset();
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testGetInfo($prefix)
    {
        $v = $this->cpf($prefix, 'get_info');
        $this->assertGreaterThan(0, $v['req_time'], 'Request time should be bigger than 0');
        $this->assertGreaterThan(1024000, $v['mem_peak_usage'], 'Used mem time should be bigger than 1MB');
        $this->assertSame('php', $v['hostname'], 'Host name should default to php for cli scripts');
        $this->assertSame('./vendor/bin/phpunit', $v['script_name'], 'Script name should match phpunit runner');
        $this->assertSame('unknown', $v['server_name'], 'Server name should be unknown');
        /// @todo
        //$this->assertSame(1, $v['req_count'], 'Req count should be 1 for cli scripts');

        $this->cpf($prefix, 'hostname_set', 'hello');
        $this->cpf($prefix, 'script_name_set', 'world.php');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testSetInfo($prefix)
    {
        $this->cpf($prefix, 'hostname_set', 'hello');
        $this->cpf($prefix, 'script_name_set', 'world.php');
        $v = $this->cpf($prefix, 'get_info');
        $this->assertSame('hello', $v['hostname'], 'Host name should match what was set');
        $this->assertSame('world.php', $v['script_name'], 'Script name should match what was set');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testTimer($prefix)
    {
        $t = $this->cpf($prefix, 'timer_start', array('timer' => 'testTimer', 'tag2' => '10'), array('whatever'));
        usleep(10);
        $v = $this->cpf($prefix, 'timer_get_info', $t);
        usleep(100000);
        $r = $this->cpf($prefix, 'timer_stop', $t);

        $this->assertSame(true, $r, 'timer_stop should return true for running timers');
        $this->assertSame(true, $v['started'], 'Timer started should be true before stop');
        $this->assertGreaterThan(0, $v['value'], 'Timer time should be bigger than zero after start');
        $this->assertLessThan(0.01, $v['value'], 'Timer time should be less than 0.01 secs after start');

        $v = $this->cpf($prefix, 'timer_get_info', $t);
        $this->assertGreaterThan(0.1, $v['value'], 'Timer time should be bigger than sleep time');
        ksort($v['tags']);
        $this->assertSame(array('tag2' => '10', 'timer' => 'testTimer'), $v['tags'], 'Timer tags should keep injected value');
        $this->assertSame(array('whatever'), $v['data'], 'Timer data should keep injected value');
        $this->assertSame(false, $v['started'], 'Timer started should be false after stop');

        $v1 = $v['value'];
        /// @todo this generates a warning. Move it to its own test, tagged as expect exception
        //$r = $this->cpf($prefix, 'timer_stop', $t);
        //$this->assertSame(false, $r, 'timer_stop should return false for stopped timers');

        usleep(100000);
        $v = $this->cpf($prefix, 'timer_get_info', $t);
        $this->assertSame($v1, $v['value'], 'Timer time should not increase after stop');

        $v2 = $this->cpf($prefix, 'get_info');
        $this->assertSame($v, $v2['timers'][0], 'get_info should return same timer as timer_get_info');

        $this->cpf($prefix, 'timer_data_merge', $t, array('x' => 'y'));
        $v = $this->cpf($prefix, 'timer_get_info', $t);
        $this->assertSame(array('whatever', 'x' => 'y'), $v['data'], 'Timer data should have merged value');

        $this->cpf($prefix, 'timer_data_replace', $t, array('x' => 'y'));
        $v = $this->cpf($prefix, 'timer_get_info', $t);
        $this->assertSame(array('x' => 'y'), $v['data'], 'Timer data should have replaced value');

        $this->cpf($prefix, 'timer_tags_merge', $t, array('x' => 'y'));
        $v = $this->cpf($prefix, 'timer_get_info', $t);
        ksort($v['tags']);
        $this->assertSame(array('tag2' => '10', 'timer' => 'testTimer', 'x' => 'y'), $v['tags'], 'Timer tags should have merged value');

        $this->cpf($prefix, 'timer_tags_replace', $t, array('x' => 'y'));
        $v = $this->cpf($prefix, 'timer_get_info', $t);
        $this->assertSame(array('x' => 'y'), $v['tags'], 'Timer tags should have replaced value');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testTimerAdd($prefix)
    {
        $t = $this->cpf($prefix, 'timer_add', array('timer' => 'testTimerAdd'), 2.0);
        $v = $this->cpf($prefix, 'timer_get_info', $t);

        $this->assertSame(false, $v['started'], 'Timer started should be false before stop');
        $this->assertSame(2.0, $v['value'], 'Timer time should be the same as set');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testNoTimer($prefix)
    {
        // might be a warning or a notice, depending upon the API in use
        $this->expectException('\PHPUnit\Framework\Error\Error');
        $v = $this->cpf($prefix, 'timer_get_info', -1);
    }

    // this test can not be easily run using the pinba extension: we would have to use an invalid resource instead of -1
    function testBadStop1($prefix = self::PPC)
    {
        $v = $this->cpf($prefix, 'timer_stop', -1);
        $this->assertSame(false, $v, 'Timer stop should fail on non-timer');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testBadStop2($prefix)
    {
        // might be a warning or a notice, depending upon the API in use
        $this->expectException('\PHPUnit\Framework\Error\Error');
        $t = $this->cpf($prefix, 'timer_add', array('timer' => 'testBadStop2'), 1.0);
        $v = $this->cpf($prefix, 'timer_stop', $t);
        $v = $this->cpf($prefix, 'timer_stop', $t);
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testTimerDelete($prefix)
    {
        $t = $this->cpf($prefix, 'timer_start', array('timer' => 'testTimerDelete'));
        $r = $this->cpf($prefix, 'timer_delete', $t);
        $this->assertSame(true, $r, 'the timer should have been deleted');
        $timers = $this->cpf($prefix, 'timers_get');
        $this->assertSame(0, count($timers), 'there should be no timers');
        if ($prefix != 'pinba_') {
            /// @todo we should find a resource to pass instead of an invalid int
            $r = $this->cpf($prefix, 'timer_delete', 999);
            $this->assertSame(false, $r, 'inexisting timer should not have been deleted');
        }
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testGetTimers($prefix)
    {
        $t1 = $this->cpf($prefix, 'timer_start', array('timer' => 'testGetTimers_1'));
        $t2 = $this->cpf($prefix, 'timer_start', array('timer' => 'testGetTimers_2'));
        $this->cpf($prefix, 'timer_stop', $t1);
        $timers = $this->cpf($prefix, 'timers_get');
        $this->assertSame(2, count($timers), 'there should be 2 timers');
        $timers = $this->cpf($prefix, 'timers_get', pinba::ONLY_STOPPED_TIMERS);
        $this->assertSame(1, count($timers), 'there should be 1 stopped timer');
        $this->cpf($prefix, 'timers_stop');
        $timers = $this->cpf($prefix, 'timers_get');
        $this->assertSame(2, count($timers), 'there should be 2 timers');
        $timers = $this->cpf($prefix, 'timers_get', pinba::ONLY_STOPPED_TIMERS);
        $this->assertSame(2, count($timers), 'there should be 2 stopped timers');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testTags($prefix)
    {
        $v = $this->cpf($prefix, 'tag_get', 'hey');
        $this->assertSame(false, $v, 'there should be no tag');
        $this->cpf($prefix, 'tag_set', 'hey', 'there');
        $v = $this->cpf($prefix, 'tag_get', 'hey');
        $this->assertSame('there', $v, 'there should a tag');
        $this->cpf($prefix, 'tag_set', 'hey', 'you');
        $v = $this->cpf($prefix, 'tag_get', 'hey');
        $this->assertSame('you', $v, 'tag should have been modified');
        $this->cpf($prefix, 'tag_set', 'you', 'hey');
        $v = $this->cpf($prefix, 'tags_get');
        $this->assertSame(array('hey' => 'you', 'you' => 'hey'), $v, 'tags should be present');
        $v = $this->cpf($prefix, 'get_info');
        $this->assertSame(array('hey' => 'you', 'you' => 'hey'), $v['tags'], 'tags should be present');
        $v = $this->cpf($prefix, 'tag_delete', 'hey');
        $this->assertSame(true, $v, 'tag deletion should succeed');
        $v = $this->cpf($prefix, 'tag_get', 'hey');
        $this->assertSame(false, $v, 'there should be no tag');
        $v = $this->cpf($prefix, 'tag_delete', 'hey');
        $this->assertSame(false, $v, 'tag deletion should fail');
    }
}
