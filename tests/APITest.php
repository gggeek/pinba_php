<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use PinbaPhp\Polyfill\PinbaFunctions as pinba;

abstract class APITest extends TestCase
{
    /// PolyfillPinbaClass
    const PPC = 'PinbaPhp\Polyfill\PinbaFunctions::';

    /** @var mysqli $db */
    protected static $db;
    protected static $pinba1 = false;

    /**
     * "Call Pinba Function"
     * Too smart for our own good: allow to call the same function twice: once using the php extension name and once
     * the polyfill name.
     * This way, provided the extension is installed, instead of having a set of tests specifically comparing the results
     * of extension vs polyfill calls, we just run the same set of tests against the two APIs, and check that both pass,
     * to make sure the results are the same.
     * A trick on top of the trick: of the php extension is not enabled, we can still run all test twice, to check
     * that the code in `bootstrap.php` is fine (and increase code coverage).
     *
     * @param string $prefix
     * @param string $method
     * @return mixed
     */
    protected function cpf($prefix, $method)
    {
        $function = $prefix . $method;
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        return call_user_func_array($function, $args);
    }

    /**
     * For tests to be executed once against each api
     * @see self::cpf() for an explanation of what this does and why it does it this way
     * @return array[]
     */
    public function listAPIPrefixes()
    {
        $out = array(
            array(self::PPC),
            array('pinba_'),
        );
        return $out;
    }

    /**
     * For tests to be executed twice against each api - eg. once with pinba.enabled=1, once with pinba.enabled=0
     * @return array[]
     */
    public function listAPIPrefixesMatrix()
    {
        $out = array(
            array(self::PPC, 1),
            array(self::PPC, 0),
        );
        if (extension_loaded('pinba')) {
            $out[] = array('pinba_', 1);
            $out[] = array('pinba_', 0);
        }
        return $out;
    }

    public function listClientClasses()
    {
        $out = array(
            array('PinbaPhp\Polyfill\PinbaClient'),
            array('PinbaClient'),
        );
        return $out;
    }

    protected function pReset()
    {
        // delete all existing timers and tags
        pinba::reset();
        // unluckily, native pinba_reset removes tags, but not timers. Flush does remove timers though...
        if (extension_loaded('pinba')) {
            pinba_reset();
            $e = ini_get('pinba.enabled');
            ini_set('pinba.enabled', 0);
            pinba_flush();
            ini_set('pinba.enabled', $e);
        }
    }

    protected static function dbConnect()
    {
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
        } else {
            // the test db in the pinba2 container defaults to latin-1, but we create the report table using utf8
            self::$db->set_charset('utf8');
        }
        // this is required to "start" the reporting table
        self::$db->query("SELECT * FROM report_by_script_name;")->fetch_all(MYSQLI_ASSOC);
    }
}
