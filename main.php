<?php
include_once("inc.php");
global $gconf;
if ($argc < 2 || $argc >4) {
    echo "usage: main.php MODE [STDATE] [EDATE]\n";
    echo " MODE = all 或者 ( dump,preload,prestat,stat,load,postload,stattrade,paytrade,refundtrade,cityday,cpsstat,goodssales ) 这几个值的组合,逗号分隔\n";
    echo " STDATE = 统计中使用数据的基准日期\n";
    echo " EDATE = 多天运行统计中使用数据的结束基准日期\n";
    echo " $argc\n";
    exit(1);
}

if ($argv[1] == "all") {
    $mode = array("dump","preload","prestat","stat","load","postload","stattrade","paytrade","refundtrade","cityday","cpsstat","goodssales","wirelessbuylog");
} else if ($argv[1] == "arpu") {
    $mode = array("dump","preload","prestat","stat","load","postload","stattrade","paytrade","cityday","goodssales");
} else if ($argv[1] == "wireless") {
    $mode = array ("wirelessbuylog");
}else if($argv[1] == "etl"){
    $mode = array ("etldump");
} else {
    $mode = explode(',',$argv[1]);
}
if (!isset($argv[2]) || $argv[2] == "yesterday") {
    $sttime = strtotime(date('Y-m-d',time()-86400));
} else if ($argv[2] == "today") {
    $sttime = strtotime(date('Y-m-d'));
} else {
    $sttime = strtotime($argv[2]);
}
$etime= isset($argv[3]) ? strtotime($argv[3]) : $sttime;

foreach ($mode as $step) {
    $func="func$step";
    $func($sttime,$etime);
    glog("done $func\n");
}


