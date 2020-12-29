<?php
error_reporting(E_ALL);
$dir = dirname(__FILE__);
$_self = str_replace('.php','',basename($_SERVER['SCRIPT_FILENAME']));
$_hname =  php_uname('n');
ini_set("memory_limit","2048M");
date_default_timezone_set("Asia/Chongqing");
require_once($dir."/funclib.php");
require_once($dir."/funcods.php");
require_once($dir."/funcstat.php");
require_once($dir."/funcwireless.php");
require_once($dir."/funcetl.php");
require_once($dir."/funcgetter.php");
//require_once($dir."/TFT.php");
global $gconf;
$gconf = gloadconf("$dir/main.conf");
