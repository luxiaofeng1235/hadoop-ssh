<?php
//此脚本生成重组后的无线交易数据表，分全部交易和付款后交易两部分
//依赖go_buy, go_visit_trade_no和go_buy_log
include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $stdate = date('Ymd',strtotime($argv[1]));
    $stdate_str= date('Y-m-d',strtotime($argv[1]));
} else {
    $stdate = date('Ymd',time()-86400);
    $stdate_str = date('Y-m-d',time()-86400);
}

$logroot = $gconf['LOG_PATH'];
$year = substr($stdate,0,4);
system("mkdir -p $logroot/wl_trade/$year $logroot/wl_pay_trade/$year 2>/dev/null");
$out_name1= "$logroot/wl_trade/$year/wl_trade_$stdate.gz";
$out_name2= "$logroot/wl_pay_trade/$year/wl_pay_trade_$stdate.gz";

$last_line = system("ls -tr1 $logroot/go_buy_log/",$retval);
    if ($retval != 0 || empty($last_line)) {
        glog("ret = $retval cmd = ls -tr1 $logroot/$sqlname");
        exit(1);
    }
$log_name = "$logroot/go_buy_log/$last_line";

$gobuy_name= sprintf("$logroot/go_buy/$year/go_buy_$stdate.gz $logroot/go_buy/$year/go_buy_%s.gz ",date('Ymd',strtotime($stdate)-86400));


$thash = array();
$paidhash = array();
$log_fd =  popen("zcat $log_name","r");
$buy_fd =  popen("zcat $gobuy_name 2>/dev/null","r");
$out_fd1 = popen("gzip -c  >$out_name1","w");
$out_fd2 = popen("gzip -c  >$out_name2","w");
if (!is_resource($log_fd) || !is_resource($buy_fd) || !is_resource($out_fd1) || !is_resource($out_fd2)) {
    glog("failed open $log_name or $gobuy_name");
    exit(1);
}

$logcolumn = preg_split('/[,\s]+/',"buy_time, pay_time,trade_no,leave_sign,app_name,app_edition,client_id,client_name,channel_id,device,os_version");
$buycolumn = preg_split('/[,\s]+/',"buy_date, trade_no,buyer_id,amount,total_fee,buy_time,sp_id,goods_id,new_cat,channel,type,price,cost_price,gross");

while ($line= stream_get_line($log_fd,1048576,"\n")) {
    $l = explode("\t",$line);
    foreach($logcolumn as $idx => $name) {
        $iname = "I$name";
        $$iname = $l[$idx];
    }
    list($Ibuy_date,$null) = explode(' ',$Ibuy_time,2);
    list($Ipay_date,$null) = explode(' ',$Ipay_time,2);
    if ($Iclient_name == "NULL" && $Iapp_name == 'wap') {
        $Iclient_name = $Iapp_name;
    }
    if ( $Ibuy_date == $stdate_str) {
        $thash[$Itrade_no] = array($Ibuy_time,$Ileave_sign,$Iapp_name,$Iapp_edition,$Iclient_id,$Iclient_name,$Ichannel_id,$Idevice,$Ios_version);
    }
    if (($Ibuy_date != "NULL")  && ($Ipay_time != "NULL") && $Ipay_date == $stdate_str) {
        $paidhash[$Itrade_no] = (isset($thash[$Itrade_no]) && $thash[$Itrade_no][5] != "NULL") ?
            $thash[$Itrade_no] :
            array($Ibuy_time,$Ileave_sign,$Iapp_name,$Iapp_edition,$Iclient_id,$Iclient_name,$Ichannel_id,$Idevice,$Ios_version);
        array_unshift($paidhash[$Itrade_no],$Ipay_time);
    }
}
glog(gmemusage());
glog(" trade_no ".count($thash));
glog(" paid trade_no ".count($paidhash));
pclose($log_fd);
$count_trade = 0;
$count_wl_trade = 0;
$count_wl_paid_trade = 0;
while ((count($thash) + count($paidhash) > 0) && ( $line= stream_get_line($buy_fd,1048576,"\n"))) {
    $l = explode("\t",$line);
    foreach($buycolumn as $idx => $name) {
        $iname = "I$name";
        $oname = "O$name";
        $$iname = $l[$idx];
        $$oname = $l[$idx];
    }
    $count_trade++;
    if (array_key_exists($Itrade_no,$thash)) {
        fprintf($out_fd1,"%s\t%s\n",
                implode("\t",array(date('Y-m-d',strtotime($thash[$Itrade_no][0])),$Itrade_no,$Ibuyer_id,$Iamount,$Itotal_fee,
                        $Isp_id,$Igoods_id,$Inew_cat,$Ichannel,$Itype,$Iprice,$Icost_price,$Igross)),
                implode("\t",$thash[$Itrade_no])
                );
        unset($thash[$Itrade_no]);
        $count_wl_trade++;
    }
    if (array_key_exists($Itrade_no,$paidhash)) {
        fprintf($out_fd2,"%s\t%s\n",
                implode("\t",array(date('Y-m-d',strtotime($paidhash[$Itrade_no][0])),$Itrade_no,$Ibuyer_id,$Iamount,$Itotal_fee,
                        $Isp_id,$Igoods_id,$Inew_cat,$Ichannel,$Itype,$Iprice,$Icost_price,$Igross)),
                implode("\t",$paidhash[$Itrade_no])
                );
        unset($paidhash[$Itrade_no]);
        $count_wl_paid_trade++;
    }
}
glog(gmemusage());
glog("$stdate_str count_trade=$count_trade count_wl_trade=$count_wl_trade count_wl_paid_trade=$count_wl_paid_trade");
pclose($buy_fd);
pclose($out_fd1);
pclose($out_fd2);

