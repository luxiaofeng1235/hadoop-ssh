<?php
function funcstat($sttime,$etime) {
    global $gconf;
    $cmdArr = array();
    $sumCode = 0;
    $php = $gconf['PHP_BIN'];
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $cmdArr []= "$php sort_session.php  $stdate";
    }
    glog("stat:begin  $sttime $etime");
    $ret = gprun($cmdArr,1);
    $charMap = str_split('0123456789abcdefghijklmnopqrstuv');
    $logroot = $gconf['LOG_PATH'];
    $logcolumn="id,visit_time,ip,city_id,client_key,session_id,user_id,source,ref,url,browser,pos";
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Y-m-d',$t);
        $stdateShort = date('Ymd',$t);
        $yesterday = date('Ymd',$t-86400);
        $yesterdayPath = date('Y/Ym',$t-86400);
        $today = date('Ymd',$t);
        $todayPath = date('Y/Ym',$t);
        $cmdArr = array();
        for($i=0;$i<count($charMap);$i++) {
            $cmd = "$php one_block.php '$logcolumn' channel_sitemap url_type_php $logroot/stat_www_visit_log/$yesterdayPath/stat_www_visit_log_${yesterday}_${charMap[$i]}.gz ";
            $cmd .= " 0<$logroot/stat_www_visit_log/$todayPath/pre_sort_go_visit_log_${stdateShort}_${charMap[$i]} ";
            $cmd .= " 2>/dev/null ";
            $cmd .= "|gzip -c >$logroot/stat_www_visit_log/$todayPath/stat_www_visit_log_${today}_${charMap[$i]}.gz";
            $cmd .= " 2>/dev/null";
            $cmdArr []= $cmd ;
        }
        $ret = gprun($cmdArr,2);
        $sumCode = 0;
        foreach ($ret as $r) {
            $sumCode += $r['retcode'];
            if ($r['retcode']>0) {
                glog(sprintf("stat:one_block ret = %s cmd = %s",$r['retcode'],$r['cmd']));
                glog($r['output']);
            }
        }
        system("rm -f $logroot/stat_www_visit_log/$todayPath/pre_sort_go_visit_log_${today}_*");
    }
    glog("stat:end  $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf("stat:ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }
    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("stat done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

function funcstattrade($sttime,$etime){
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php stat_trade.php $stdate";
    }
    glog("stattrade:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("stattrade:end $sttime $etime");
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
        glog("stattrade done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

function funcpaytrade($sttime,$etime){
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php stat_pay_trade.php $stdate";
    }
    glog("paytrade:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("paytrade:end $sttime $etime");
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
        glog("paytrade done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

//退款信息，需要当天之前的所有的trade任务完成之后运行
function funcrefundtrade($sttime,$etime){
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php stat_refund_trade.php $stdate";
    }
    glog("refundtrade:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("refundtrade:end $sttime $etime");
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
        glog("refundtrade done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

function funccityday($sttime,$etime){
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php stat_city_day.php $stdate";
    }
    glog("cityday:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("cityday:end $sttime $etime");
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
        glog("cityday done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

function funccpsstat($sttime,$etime){
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php cps_stat.php $stdate";
    }
    glog("cps_stat:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("cps_stat:end $sttime $etime");
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
        glog("cps_stat done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}

//销售数据
function funcgoodssales($sttime,$etime){
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $sumCode = 0;
    $cmdArr = array();

    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "bash -x dump_goods_tables.sh $stdate";
    }
    glog("goods_dump_sales:begin $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("goods_dump_sales:end $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf(":ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }

    $stdate = date('Y-m-d',$sttime-14*86400);
    $edate = date('Y-m-d',$etime);
    $cmdArr = array();
    foreach (array("goods_www_uv_day","goods_sales_info_day","goods_refund_info_day") as $table) { 
        $cmdArr []= "$php loader.php $table all $stdate $edate";
    }
    glog("goodsload:begin  $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("goodsload:end  $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf(":ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }

    $cmdArr = array();
    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php stat_goods_sales.php $stdate";
    }
    glog("goods_stat:begin  $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("goods_stat:end  $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf(":ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }

    $out = array();
    exec("bash -x goods_sum.sh 2>&1",$out,$ret);
    $sumCode += $ret;
    if ($ret >0) {
        glog("goods_sum.sh ret=$ret out=".print_r($out,true));
    }

    if ($sumCode>0) {
        glog("something went wrong");
    } else {
        glog("goods_dump_sales done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}
