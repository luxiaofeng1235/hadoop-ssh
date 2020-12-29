<?php

function funcwirelessbuylog ($sttime,$etime) {
    global $gconf;
    $php = $gconf['PHP_BIN'];
    $cmdArr = array();
    $sumCode = 0;
    glog("wirelessgobuylog:begin  $sttime $etime");
    for($i=$sttime;$i<=$etime;$i=$i+86400){
        $stdate = date('Y-m-d',$i);
        $cmdArr []= "$php wireless_go_buy_log.php $stdate";
    }
    $ret = gprun($cmdArr,1);
    glog("wirelessgobuylog:end $sttime $etime");
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
        glog("wirelessgobuylog done ".date('Y-m-d',$sttime)."  ".date('Y-m-d',$etime));
    }
}
