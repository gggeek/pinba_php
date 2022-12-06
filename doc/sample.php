<?php
/**
 * A sample php file that uses the pinba php extension
 */

// Start time measurement _asap_: before autoloading even kicks in
$starttime = microtime(true);

include('vendor/autoload.php');

// This is optional - if omitted, time measurement will start during class autoloading in the line above, and be just
// a little less accurate
use \PinbaPhp\Polyfill\PinbaFunctions as pinba;
pinba::init($starttime);

// Do some stuff here ...

// Measure some operation with a specific timer: example
$connection = mysqli_connect('...');
$t = pinba_timer_start(array("group"=>"mysql", "server"=>"db1", "operation"=>"select"));
$result = mysqli_query("SELECT ... FROM ... WHERE ...", $connection);
pinba_timer_stop($t);

// That's all folks!
//
// Memory usage, execution time, timers info etc. will be automatically collected and at the end of the execution
// of this page will be flushed to the Pinba server via an udp network packet, provided you have set `pinba.enabled` and
// `pinba.server` in php.ini.
