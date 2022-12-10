<?php

include (__DIR__ . '/../vendor/autoload.php');

use PinbaPhp\Polyfill\PinbaFunctions as pinba;

const ITERATIONS = 1000;
const PAUSEUSECS = 0;
$TAGS = array('t' => '1', 'longLongTag' => "This is possibly too long for its own good: lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.");

function wait($usecs)
{
    /*if ($usecs === 0) {
        return;
    }
    usleep($usecs);*/
}

$m = memory_get_usage();
$time = microtime(true);
for($i = 0; $i < ITERATIONS; $i++) {
    wait(PAUSEUSECS);
}
$time = microtime(true) - $time;
$m = memory_get_usage() - $m;

if (extension_loaded('pinba'))
{
    ini_set('pinba.enanled', 1);
    ini_set('pinba.server', getenv('PINBA_SERVER') . ':' . getenv('PINBA_PORT'));

    $m1 = memory_get_usage();
    $time1 = microtime(true);
    for($i = 0; $i < ITERATIONS; $i++) {
        $t = pinba_timer_start($TAGS);
        wait(PAUSEUSECS);
        pinba_timer_stop($t);
    }
    $time1 = microtime(true) - $time1;
    $m1 = memory_get_usage() - $m1;

    ini_set('pinba.enanled', 0);
    pinba_flush();
    pinba_reset();
}

$m2 = memory_get_usage();
$time2 = microtime(true);
for($i = 0; $i < ITERATIONS; $i++) {
    $t = pinba::timer_start($TAGS);
    wait(PAUSEUSECS);
    pinba::timer_stop($t);
}
$time2 = microtime(true) - $time2;
$m2 = memory_get_peak_usage() - $m2;

echo "Tested execution of " . ITERATIONS . " empty function calls in a loop:\n";
echo "No timing:      " . sprintf("%.5f", $time) . " secs, " . sprintf("%7d", $m) . " bytes used\n";
echo "Pinba-timed:    " . sprintf("%.5f", $time1) . " secs, " . sprintf("%7d", $m1) . " bytes used\n";
echo "PHPPinba timed: " . sprintf("%.5f", $time2) . " secs, " . sprintf("%7d", $m2) . " bytes used\n";
