<?php

include_once(__DIR__.'/APITest.php');

use PinbaPhp\Polyfill\PinbaFunctions as pinba;

class FlushTest extends APITest
{
    protected $id;

    /**
     * @beforeClass
     */
    public static function setEnvUp()
    {
        pinba::ini_set('pinba.server', getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT'));
        if (extension_loaded('pinba')) {
            ini_set('pinba.server', getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT'));
        }

        self::dbConnect();
    }

    /**
     * @afterClass
     */
    public static function shutEnvDown()
    {
        // avoid flushing on end of phpunit
        pinba::ini_set('pinba.enabled', 0);
        if (extension_loaded('pinba')) {
            ini_set('pinba.enabled', 0);
        }
    }

    /**
     * @before
     */
    public function setTestUp()
    {
        // delete all existing timers and tags
        /// @todo delete all existing timers, tags and request   data in mysql (is that possible at all?)
        $this->pReset();

        // generate a unique id for the test, transparently used in flush() calls
        $this->id = uniqid('02');
        pinba::script_name_set($this->id);
        if (extension_loaded('pinba')) {
            pinba_script_name_set($this->id);
        }

        // in case any test sets pinba.enabled=0
        pinba::ini_set('pinba.enabled', 1);
        if (extension_loaded('pinba')) {
            ini_set('pinba.enabled', 1);
        }
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testFlush($prefix)
    {
        $t1 = $this->cpf($prefix, 'timer_start', array('timer' => 'testFlush'));
        $this->cpf($prefix, 'tag_set', 'class', 'FlushTest');
        $this->cpf($prefix, 'tag_set', 'test', 'testFlush');

        $this->cpf($prefix, 'flush');

        $v1 = $this->cpf($prefix, 'timer_get_info', $t1);
        $this->assertSame(false, $v1['started'], 'timer should have been stopped by flush call');

        $v = $this->cpf($prefix, 'get_info');
        $this->assertSame(0, count($v['timers']), 'timer should have been deleted by flush call');

        sleep(2); // we can not reduce it, as we have to wait for rollup into the reports tables

        if (self::$pinba1) {
            $r = self::$db->query("SELECT * FROM request WHERE script_name='" . self::$db->escape_string($this->id) ."';")->fetch_all(MYSQLI_ASSOC);

            $this->assertSame(1, count($r), 'no request data found in the db for a flush call');
            $r = $r[0];
            $this->assertSame($v['hostname'], $r['hostname'], 'hostname data was not sent correctly to the db');
            $this->assertSame(0, (int)$r['req_count'], 'req_count data was not sent correctly to the db');
            $this->assertSame($v['server_name'], $r['server_name'], 'server_name data was not sent correctly to the db');
            $this->assertSame($v['script_name'], $r['script_name'], 'script_name data was not sent correctly to the db');
            $this->assertSame($v['doc_size'], (int)$r['doc_size'], 'doc_size data was not sent correctly to the db');
            $this->assertSame((int)round($v['mem_peak_usage']/1024), (int)$r['mem_peak_usage'], 'mem_peak_usage data was not sent correctly to the db');
            $this->assertSame(1, (int)$r['timers_cnt'], 'timers data was not sent correctly to the db');
            $this->assertContains($r['schema'], array('', '<empty>'), 'schema data was not sent correctly to the db');
            $this->assertSame(2, (int)$r['tags_cnt'], 'tags data was not sent correctly to the db');
            $this->assertSame('class=FlushTest,test=testFlush', $r['tags'], 'tags data was not sent correctly to the db');

            $r = self::$db->query("SELECT t.*, g.name AS tagname, tt.value AS tagvalue FROM timer t, timertag tt, tag g, request r WHERE t.id=tt.timer_id AND tt.tag_id = g.id AND t.request_id = r.id AND r.script_name='" . self::$db->escape_string($this->id) ."';")->fetch_all(MYSQLI_ASSOC);
            $this->assertSame(1, count($r), 'no timer data found in the db for a flush call');
            $r = $r[0];
            $this->assertSame(1, (int)$r['hit_count'], 'timer hit_count was not sent correctly to the db');
            $this->assertSame('testFlush', $r['tagvalue'], 'timer tag value was not sent correctly to the db');
            $this->assertSame('timer', $r['tagname'], 'timer tag name was not sent correctly to the db');
            $this->assertSame(round($v1['value'], 3), round($r['value'], 3), 'timer value was not sent correctly to the db');
        }

        if (self::$pinba1) {
            $col = 'script_name';
        } else {
            $col = 'script';
        }
        $r = self::$db->query("SELECT * FROM report_by_script_name WHERE $col='" . self::$db->escape_string($this->id) ."';")->fetch_all(MYSQLI_ASSOC);
        $this->assertSame(1, count($r), 'no aggregate data found in the db for a flush call');
    }

    /**
     * @dataProvider listAPIPrefixesMatrix
     */
    function testFlushOnlyStoppedTimers($prefix, $pinbaEnabled)
    {
        if ($prefix == 'pinba_') {
            $this->cpf('', 'ini_set', 'pinba.enabled', $pinbaEnabled);
            $case = '0';
        } else {
            $this->cpf($prefix, 'ini_set', 'pinba.enabled', $pinbaEnabled);
            $case = '1';
        }

        $t1 = $this->cpf($prefix, 'timer_start', array('timer' => 'testFlushOnlyStoppedTimers_1_' . $case . $pinbaEnabled));
        $t2 = $this->cpf($prefix, 'timer_add', array('timer' => 'testFlushOnlyStoppedTimers_2_' . $case . $pinbaEnabled), 1);
        $this->cpf($prefix, 'flush', null, pinba::FLUSH_ONLY_STOPPED_TIMERS);

        $v1 = $this->cpf($prefix, 'timer_get_info', $t1);
        $this->assertSame(true, $v1['started'], 'timer should not have been stopped by flush call');
        $v = $this->cpf($prefix, 'timers_get');
        $this->assertSame(1, count($v), 'one timer should not have been deleted by flush call');
        $ti = $this->cpf($prefix, 'timer_get_info', $v[0]);
        $this->assertSame($ti['tags'], $v1['tags'], 'started timer should not have been deleted by flush call');
        $v = $this->cpf($prefix, 'get_info');
        $this->assertSame(1, count($v['timers']), 'one timer should not have been deleted by flush call');
        //$this->assertSame($v['timers'][0]['tags'], $v1['tags'], 'started timer should not have been deleted by flush call');
        $this->assertSame($v['timers'][0]['tags'], $v1['tags'], 'started timer should not have been deleted by flush call');

        $v = $this->cpf($prefix, 'timer_get_info', $t2);
        $this->assertNotEquals(false, $v, 'flushed timer info should still be available');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testFlushAdditiveTimers($prefix)
    {
        if (!self::$pinba1) {
            $this->markTestSkipped('Can not test flushed timers on pinba2');
        }

        $t1 = $this->cpf($prefix, 'timer_add', array('timer' => 'testFlushAdditiveTimers', 'extra' => md5($prefix)), 2);
        $t2 = $this->cpf($prefix, 'timer_add', array('extra' => md5($prefix), 'timer' => 'testFlushAdditiveTimers'), 3);
        $t3 = $this->cpf($prefix, 'timer_start', array('timer' => 'testFlushAdditiveTimers', 'extra' => md5($prefix)), array('whatever'), 2);
        usleep(100000);
        $this->cpf($prefix, 'flush');
        sleep(1);
        $r = self::$db->query("SELECT t.* FROM timer t, request r WHERE t.request_id = r.id AND r.script_name='" . self::$db->escape_string($this->id) ."';")->fetch_all(MYSQLI_ASSOC);
        $this->assertSame(1, count($r), 'no timer data found in the db for a flush call');
        $r = $r[0];
        $this->assertSame(4, (int)$r['hit_count'], 'timer hit_count was not sent correctly to the db');
        $this->assertSame(round(5.1, 1), round((float)$r['value'], 1), 'timer value was not sent correctly to the db');
    }

    /**
     * @dataProvider listAPIPrefixes
     */
    function testFlushUTF8($prefix)
    {
        // greek word 'kosme'
        $id = uniqid() . '_κόσμε';

        $this->cpf($prefix, 'flush', $id);
        sleep(2); // we can not reduce it, as we have to wait for rollup into the reports tables
        if (self::$pinba1) {
            $col = 'script_name';
        } else {
            $col = 'script';
        }
        $r = self::$db->query("SELECT * FROM report_by_script_name WHERE $col='" . self::$db->escape_string($id) ."';")->fetch_all(MYSQLI_ASSOC);

        $this->assertSame(1, count($r), 'no aggregate data found in the db for a flush call');
    }
}
