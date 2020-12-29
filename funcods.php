<?php

function funcdump($sttime,$etime) {
    global $gconf;
    $sumCode = 0;
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr = array();
        foreach ( array("logs","thinklasho","activity","app","wap","dim") as $s ) {
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
function funcpreload($sttime,$etime) {
    //加载数据时要写mysql数据目录
    //只在一个mysql导入比较好
    $sumCode = 0;
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $out = array();
        glog("preload:begin $stdate");
        exec("bash -x preload.sh $stdate 2>&1",$out,$ret);
        glog("preload:end $stdate");
        $sumCode += $ret;
        if ($ret >0) {
            glog("preload:bash preload.sh $stdate ret=$ret out=".print_r($out,true));
        }
    }
    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("preload done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
        //留点时间给mfs
        sleep (15);
    }
}
function funcprestat($sttime,$etime) {
    global $gconf;
    $cmdArr = array();
    $sumCode = 0;
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr []= "bash -x get_bad_client_key.sh $stdate $stdate";
    }
    glog("prestat:begin $sttime $etime");
    $ret = gprun($cmdArr,2);
    glog("prestat:end $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf("prestat:ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }

    $cmdArr = array();
    $php = $gconf['PHP_BIN'];
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr []= "$php daily_uv.php  $stdate";
        #$cmdArr []= "$php daily_app_uv.php  $stdate";
    }
    glog("prestat:begin uv $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("prestat:end uv $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf("prestat: uv ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }

    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("prestat done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}
function funcload($sttime,$etime) {
    global $gconf;
    $cmdArr = array();
    $sumCode = 0;
    $php = $gconf['PHP_BIN'];
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr []= "$php loader.php stat_www_visit_log day $stdate $stdate";
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
    foreach (array("go_buy","go_goods","go_sp","go_order","go_visit_trade_no","reg_user","user_reg_session","spe_activity") as $table) { 
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
