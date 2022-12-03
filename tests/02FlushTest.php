<?php

use mysqli;
use PinbaPhp\Polyfill\Pinba as pinba;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class FlushTest extends TestCase
{
    /** @var mysqli $db */
    protected static $db;

    /**
     * @beforeClass
     */
    public static function setEnvUp()
    {
        //ini_set('pinba.enabled', 1);
        ini_set('pinba.server', getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT'));

        self::$db = new mysqli(
            getenv('PINBA_DB_SERVER'),
            getenv('PINBA_DB_USER'),
            getenv('PINBA_DB_PASSWORD'),
            getenv('PINBA_DB_DATABASE'),
            getenv('PINBA_DB_PORT')
        );
    }

    /**
     * @before
     */
    public function setTestUp()
    {
        /// @todo delete all existing timers and pinba data in mysql; generate a unique id for the test to use in flush()
    }

    function testFlush()
    {
        $id = uniqid();
        pinba::script_name_set($id);
        pinba::flush();
        $v = pinba::get_info();
        sleep(1); /// @todo reduce to 500 ms, possibly less
        $r = self::$db->query("SELECT * FROM request WHERE SCRIPT_NAME='" . self::$db->escape_string($id) ."';")->fetch_all(MYSQLI_ASSOC);

        $this->assertEquals(1, count($r), 'no data found in the db for a flush call');
        $r = $r[0];
        $this->assertEquals($v['hostname'], $r['hostname'], 'hostname data was not sent correctly to the db');
        $this->assertEquals($v['req_count'], $r['req_count'], 'script_name data was not sent correctly to the db');
        $this->assertEquals($v['server_name'], $r['server_name'], 'server_name data was not sent correctly to the db');
        $this->assertEquals($v['script_name'], $r['script_name'], 'script_name data was not sent correctly to the db');
        $this->assertEquals($v['doc_size'], (int)$r['doc_size'], 'doc_size data was not sent correctly to the db');
        $this->assertEquals(round($v['mem_peak_usage']/1024), (int)$r['mem_peak_usage'], 'mem_peak_usage data was not sent correctly to the db');
        $this->assertEquals(count($v['timers']), (int)$r['timers_cnt'], 'timers data was not sent correctly to the db');
        /// @todo check what pinba2 returns here
        $this->assertEquals('<empty>', $r['schema'], 'schema data was not sent correctly to the db');
        if (!count($v['timers'])) {
            $this->assertEquals(0, (int)$r['tags_cnt'], 'tags data was not sent correctly to the db');
            $this->assertEquals('', $r['tags'], 'tags data was not sent correctly to the db');
        }
    }
}
