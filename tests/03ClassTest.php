<?php

use PinbaPhp\Polyfill\Pinba as pinba;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ClassTest extends APITest
{
    /**
     * @dataProvider listClientClasses
     */
    function testPinbaClient($clientClass)
    {
        $id = uniqid();
        $rusage = array(0.5, 0.6);

        /** @var PinbaClient $c */
        $c = new $clientClass(array(getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT')));

        $c->setHostname('hostname');
        $c->setRequestCount(99);
        $c->setServername('servername');
        $c->setScriptname($id);
        $c->setDocumentSize(123);
        $c->setMemoryPeak(456000);
        $c->setMemoryFootprint(321000);
        $c->setStatus(999);
        $c->setSchema('http99');
        $c->setRusage($rusage);
        $c->setTag('tag', 'gat');
        $c->addTimer(array('ClientTimer' => 'testPinbaClient'), 1, $rusage, 2);
        // this timer should replace the previous one
        $c->setTimer(array('ClientTimer' => 'testPinbaClient'), 0.1);
        // these 2 timers should get merged
        $c->addTimer(array('ClientTimer' => 'testPinbaClient', 'extra' => '1'), 2, $rusage);
        $c->addTimer(array('extra' => '1', 'ClientTimer' => 'testPinbaClient'), 3, $rusage);

        $v = $c->send();
        sleep(2); // we can not reduce it, as we have to wait for rollup into the reports tables

        if (self::$pinba1) {
            $r = self::$db->query("SELECT * FROM request WHERE script_name='" . self::$db->escape_string($id) ."';")->fetch_all(MYSQLI_ASSOC);

            $this->assertSame(1, count($r), 'no request data found in the db for a flush call');
            $r = $r[0];
            $this->assertSame('hostname', $r['hostname'], 'hostname data was not sent correctly to the db');
            $this->assertSame(99, (int)$r['req_count'], 'req_count data was not sent correctly to the db');
            $this->assertSame('servername', $r['server_name'], 'server_name data was not sent correctly to the db');
            $this->assertSame($id, $r['script_name'], 'script_name data was not sent correctly to the db');
            $this->assertSame(round(123/1204, 1), round($r['doc_size'], 1), 'doc_size data was not sent correctly to the db');
            $this->assertSame(round(456000/1024, 2), round($r['mem_peak_usage'], 2), 'mem_peak_usage data was not sent correctly to the db');
            $this->assertSame(round(321000/1024, 2), round($r['memory_footprint'], 2), 'mem_peak_usage data was not sent correctly to the db');
            $this->assertSame(999, (int)$r['status'], 'status data was not sent correctly to the db');
            $this->assertSame('http99', $r['schema'], 'schema data was not sent correctly to the db');
            $this->assertSame(round(0.5, 2), round($r['ru_utime'], 2), 'ru_utime data was not sent correctly to the db');
            $this->assertSame(round(0.6, 2), round($r['ru_stime'], 2), 'ru_stime data was not sent correctly to the db');
            $this->assertSame(1, (int)$r['tags_cnt'], 'tags data was not sent correctly to the db');
            $this->assertSame('tag=gat', $r['tags'], 'tags data was not sent correctly to the db');
            $this->assertSame(2, (int)$r['timers_cnt'], 'timers data was not sent correctly to the db');

            $r = self::$db->query("SELECT t.*, g.name AS tagname, tt.value AS tagvalue FROM timer t, timertag tt, tag g, request r WHERE t.id=tt.timer_id AND tt.tag_id = g.id AND t.request_id = r.id AND r.script_name='" . self::$db->escape_string($id) ."' ORDER BY t.id, tagname;")->fetch_all(MYSQLI_ASSOC);
            $this->assertSame(3, count($r), 'no timer data found in the db for a flush call');
            $this->assertSame(1, (int)$r[0]['hit_count'], 'timer hit_count was not sent correctly to the db');
            $this->assertSame('testPinbaClient', $r[0]['tagvalue'], 'timer tag value was not sent correctly to the db');
            $this->assertSame('ClientTimer', $r[0]['tagname'], 'timer tag name was not sent correctly to the db');
            $this->assertSame(round(0.1, 3), round($r[0]['value'], 3), 'timer value was not sent correctly to the db');
        }

        if (self::$pinba1) {
            $col = 'script_name';
        } else {
            $col = 'script';
        }
        $r = self::$db->query("SELECT * FROM report_by_script_name WHERE $col='" . self::$db->escape_string($id) ."';")->fetch_all(MYSQLI_ASSOC);
        $this->assertSame(1, count($r), 'no aggregate data found in the db for a flush call');
    }
}
