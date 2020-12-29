<?php
include_once("inc.php");
global $gconf;
if ($argc < 2 || $argc >4) {
    echo "usage: main.php MODE [STDATE] [EDATE]\n";
    echo " MODE = all 或者 ( dump,load ) 这几个值的组合,逗号分隔\n";
    echo " STDATE = 统计中使用数据的基准日期\n";
    echo " EDATE = 多天运行统计中使用数据的结束基准日期\n";
    echo " $argc\n";
    exit(1);
}

if ($argv[1] == "all") {
    $mode = array("dump","load");
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
function funcdump($sttime,$etime) {
    global $gconf;
    $sumCode = 0;
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr = array();
        foreach ( array("wap") as $s ) {
            $cmdArr []= "bash -x dump_${s}_tables.sh $stdate";
        }
        glog("dump:begin $stdate");
        $ret = gprun($cmdArr);
        foreach ($ret as $r) {
            $sumCode += $r['retcode'];
            if ($r['retcode']>0) {
                glog(sprintf("dump:ret = %s cmd = %s",$r['retcode'],$r['cmd'])); 
                glog($r['output']);
            }
        }
        glog("dump:end $stdate");
    }
    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("dump done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }

}

function funcload($sttime,$etime) {
    global $gconf;
    $cmdArr = array();
    $sumCode = 0;
    $php = $gconf['PHP_BIN'];
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr []= "$php loader.php stat_wap_visit_log day $stdate $stdate";
    }
    glog("load:begin  $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("load:end  $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf("load:ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }
    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("load done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}


function funcpostload($sttime,$etime) {
    global $gconf;
    $cmdArr = array();
    $sumCode = 0;
    $php = $gconf['PHP_BIN'];
    $stdate = date('Y-m-d',$sttime-14*86400);
    $edate = date('Y-m-d',$etime);
    foreach (array("go_buy","go_goods","go_order","go_visit_trade_no","reg_user","user_reg_session","spe_activity") as $table) { 
        $cmdArr []= "$php loader.php $table all $stdate $edate";
    }
    glog("postload:begin  $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("postload:end  $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf("load:ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }
    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("postload done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}
