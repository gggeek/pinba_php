<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class APITest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setEnvUp()
    {
        ini_set('pinba.enabled', 1);
        ini_set('pinba.server', getenv('PINBA_HOST') . ':' . getenv('PINBA_PORT'));
    }

    function testGetInfo()
    {
        $v = pinba_get_info();
        $this->assertGreaterThan(0, $v['req_time'], 'Request time should be bigger than 0');
        $this->assertGreaterThan(1024000, $v['mem_peak_usage'], 'Used mem time should be bigger than 1MB');
        $this->assertSame('./vendor/bin/phpunit', $v['script_name'], 'Script name should match phpunit runner');
        $this->assertSame(0, $v['req_count'], 'Req count should be 0 for cli scripts');
    }
}
