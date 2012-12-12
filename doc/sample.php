<?php
/**
* A sample php file that uses the pinba php extension
*/

// start time measurement asap
$starttime = microtime(true);

include('prtbfr.php');
include('pinba.php');
include('pinbafunctions.php');

// this is optional - if omitted time measurement will start when pinba.php file is included
pinba::init($starttime);

// do some stuff here ...

// measure some operation
$t = pinba_timer_start(array("group"=>"mysql", "server"=>"dbs2", "operation"=>"select"));
$result = mysql_query("SELECT ...", $connection);
pinba_timer_stop($t);

// that's all folks!

?>