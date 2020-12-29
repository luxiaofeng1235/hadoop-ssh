<?php
include_once("inc.php");
global $gconf;
$logroot = $gconf['LOG_PATH'];
$stdate = date('Ymd',(isset($argv[1]) ? strtotime($argv[1]):(time()-86400)));
$year = substr($stdate,0,4);

$outprefix = "$logroot/stat_trade/$year/";

