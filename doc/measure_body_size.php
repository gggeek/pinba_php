<?php
/**
 * A sample php file demoing use of the pinba php extension: measuring body size
 */

// Start time measurement _asap_: before anything else
$startTime = microtime(true);

// Start output buffering
ob_start();

include('vendor/autoload.php');

use PinbaPhp\Polyfill\Pinba;
use PinbaPhp\Polyfill\PinbaClient as PC;

// Disable automatic flushing of pinba data (can be done either here or ini php.ini)
Pinba::ini_set('pinba.auto_flush', '0');
// Create a PinbaClient object, which will auto-flush upon being destroyed
$pc = new PC(array(Pinba::ini_get('pinba.server')), PINBA_AUTO_FLUSH);
$pc->setRequestTime($startTime);

// Do some stuff here
// ...
echo "hello world";
// ...
// Finally:

// Display the page's contents to the end user, while measuring its length
$size = ob_get_length();
ob_end_flush();

$pc->setDocumentSize($size);
