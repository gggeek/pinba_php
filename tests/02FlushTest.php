<?php

use PinbaPhp\Polyfill\PinbaFunctions as pinba;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class FlushTest extends TestCase
{
    /** @var mysqli $db */
    protected static $db;

    protected static $pinba1 = false;

    protected $id;

    /**
     * @beforeClass
     */
    public static function setEnvUp()
    {
        pinba::ini_set('pinba.enabled', 1);
        pinba::ini_set('pinba.server', getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT'));

        self::$db = new mysqli(
            getenv('PINBA_DB_SERVER'),
            getenv('PINBA_DB_USER'),
            getenv('PINBA_DB_PASSWORD'),
            getenv('PINBA_DB_DATABASE'),
            getenv('PINBA_DB_PORT')
        );

        if (self::$db->connect_errno) {
            /// @todo find an exception existing from phpunit 4 to 8
            throw new PHPUnit_Framework_Exception("Can not connect to the Pinba DB");
        }

        // Pinba 2 does not have raw data tables
        $r = self::$db->query("SELECT table_name FROM information_schema.tables WHERE table_schema='pinba' AND table_name='request';")->fetch_row();
        if (is_array($r) && count($r)) {
            self::$pinba1 = true;
        }
        // this is required to "start" the reporting table
        self::$db->query("SELECT * FROM report_by_script_name;")->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * @afterClass
     */
    public static function shutEnvDown()
    {
        // avoid flushing on end of phpunit
        pinba::ini_set('pinba.enabled', 0);
    }

    /**
     * @before
     */
    public function setTestUp()
    {
        /// @todo delete all existing timers and pinba data in mysql (is that possible at all?)
        pinba::reset();

        // generate a unique id for the test, transparently used in flush() calls
        $this->id = uniqid();
        pinba::script_name_set($this->id);
    }

    function testFlush()
    {
        $t1 = pinba::timer_start(array('tag1' => 'testFlush'));
        pinba::flush();

        $v = pinba::timer_get_info($t1);
        $this->assertEquals(false, $v['started'], 'timer should have been stopped by flush call');

        $v = pinba::get_info();

        sleep(2); // we can not reduce it, as we have to wait for rollup into the reports tables

        if (self::$pinba1) {
            $r = self::$db->query("SELECT * FROM request WHERE script_name='" . self::$db->escape_string($this->id) ."';")->fetch_all(MYSQLI_ASSOC);

            $this->assertEquals(1, count($r), 'no data found in the db for a flush call');
            $r = $r[0];
            $this->assertEquals($v['hostname'], $r['hostname'], 'hostname data was not sent correctly to the db');
            $this->assertEquals(0, $r['req_count'], 'req_count data was not sent correctly to the db');
            $this->assertEquals($v['server_name'], $r['server_name'], 'server_name data was not sent correctly to the db');
            $this->assertEquals($v['script_name'], $r['script_name'], 'script_name data was not sent correctly to the db');
            $this->assertEquals($v['doc_size'], (int)$r['doc_size'], 'doc_size data was not sent correctly to the db');
            $this->assertEquals(round($v['mem_peak_usage']/1024), (int)$r['mem_peak_usage'], 'mem_peak_usage data was not sent correctly to the db');
            $this->assertEquals(count($v['timers']), (int)$r['timers_cnt'], 'timers data was not sent correctly to the db');
            $this->assertContains($r['schema'], array('', '<empty>'), 'schema data was not sent correctly to the db');
            if (!count($v['timers'])) {
                $this->assertEquals(0, (int)$r['tags_cnt'], 'tags data was not sent correctly to the db');
                $this->assertEquals('', $r['tags'], 'tags data was not sent correctly to the db');
            }

            /// @todo add timers and tags to the data sent, check that they are in the db

        }

        if (self::$pinba1) {
            $col = 'script_name';
        } else {
            $col = 'script';
        }
        $r = self::$db->query("SELECT * FROM report_by_script_name WHERE $col='" . self::$db->escape_string($this->id) ."';")->fetch_all(MYSQLI_ASSOC);
        $this->assertEquals(1, count($r), 'no aggregate data found in the db for a flush call');
    }

    function testFlushOnlyStoppedTimers()
    {
        $t1 = pinba::timer_start(array('tag1' => 'testFlush'));
        pinba::flush(null, pinba::FLUSH_ONLY_STOPPED_TIMERS);

        $v = pinba::timer_get_info($t1);
        $this->assertEquals(true, $v['started'], 'timer should not have been stopped by flush call');
    }
}
