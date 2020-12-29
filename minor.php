<?php
include_once("inc.php");
global $gconf;
if ($argc < 2 || $argc >4) {
    echo "usage: minor.php MODE [STDATE] [EDATE]\n";
    echo " MODE = all 或者 (dumptrade ) 这几个值的组合,逗号分隔\n";
    echo " STDATE = 统计中使用数据的基准日期\n";
    echo " EDATE = 多天运行统计中使用数据的结束基准日期\n";
    echo " $argc\n";
    exit(1);
}

if ($argv[1] == "all") {
    $mode = array("dumptrade");
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

function funcdumptrade($sttime,$etime){
    global $gconf;
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "bash -x dump_trade_tables.sh $stdate";
    }
    glog("dumptrade:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("dumptrade:end $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf(":ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }
    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("dumptrade done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

