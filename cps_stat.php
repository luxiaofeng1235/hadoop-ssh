<?php
//此脚本汇总stat_lashou_pay_trade数据表中cps_type字段
include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}

foreach ($basedate as $key => $value) {
    if($value){
        continue;
    }
    $data  =array_key_exists($data,input);
    if(!empty($data)){
        
    }
    $res[]=$data; 
}

// 写入数据表
$table_stat_lashou_pay_trade = 'bas_stat.stat_lashou_pay_trade';

//读取数据表
$table_baidu_cps_post = 'stats.baidu_cps_post';
$table_cps_360 = 'stats.cps_360';

try{
    $maindsn = sprintf('mysql:host=%s;port=%d;dbname=stats',$gconf['MYSQL']['THINK']['HOST'],$gconf['MYSQL']['THINK']['PORT']);
    $dbhDC= new PDO($maindsn,$gconf['MYSQL']['THINK']['USER'],$gconf['MYSQL']['THINK']['PASS'],array(PDO::ATTR_PERSISTENT => true));
    $statdsn = sprintf('mysql:host=%s;port=%d;dbname=bas_stat',$gconf['MYSQL']['STAT']['HOST'],$gconf['MYSQL']['STAT']['PORT']);
    $dbhBA= new PDO($statdsn,$gconf['MYSQL']['STAT']['USER'],$gconf['MYSQL']['STAT']['PASS'],array(PDO::ATTR_PERSISTENT => true));

    $cpsDate = array('baidu' => array(),'360' => array());

    //获取当天付款订单
    $orderSql = "select trade_no from $table_stat_lashou_pay_trade where stdate = '$basedate'";
echo $orderSql;    
$res = $dbhBA->query($orderSql);
    $tradeArr = array();
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $tradeArr[$r['trade_no']] = $r;
    }
    glog("stat_lashou_pay_trade ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

    $arr = array_chunk($tradeArr,500);
    foreach ($arr as &$part) {
        $trade_nos = array();
        foreach ($part as &$r) {
            $trade_nos []= "'".$r['trade_no']."'";
        }
	if($trade_nos){
	    $sql = "select order_id trade_no from $table_baidu_cps_post where order_id in(".implode(",",$trade_nos).")"; 
	    $res = $dbhDC->query($sql);
	    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
		$cpsDate['baidu']["'".$r['trade_no']."'"] = 1;
	    }
	    $sql = "select trade_no from $table_cps_360 where trade_no in(".implode(",",$trade_nos).")";
	    $res = $dbhDC->query($sql);
	    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
		$cpsDate['360']["'".$r['trade_no']."'"] = 1;
	    }
	}
    }
    glog("stat_lashou_pay_trade cps done ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

    foreach($cpsDate as $cpstype => $val){
	$arr = array_chunk(array_keys($val),500);
	foreach ($arr as &$part) {
	    $sql = "update $table_stat_lashou_pay_trade set cps_type = '$cpstype' where trade_no in(".implode(",",$part).")";
echo $sql;echo "\n";
   $dbhBA->query($sql);
	}	
    }

    //load to mfs
    $basedate_short = date('Ymd',strtotime($basedate));
    $logyear = date("Y",strtotime($basedate_short));
    $LOG_PATH = $gconf['LOG_PATH'];
    $MYSQL_BIN = $gconf['MYSQL_BIN'];
    $sql_var=' -r  -quick --default-character-set=utf8  --skip-column ';
    system("mkdir -p $LOG_PATH/stat_lashou_pay_trade/$logyear");
    $sql = "select stdate,trade_no,order_id,session_id,client_key,qdh_session,qdh_client,qdh_trade,visit_id,channel_id,city_id,buyer_id,goods_id,
		new_cat,type,sp_id,channel,price,cost_price,gross,amount,total_fee,convey_fee,payed,charge_pay,epurse_payed,youhui_fee,pay_time,
		cps_type from $table_stat_lashou_pay_trade where stdate='$basedate'";
    system("$MYSQL_BIN $sql_var -h".$gconf['MYSQL']['STAT']['HOST']." -u".$gconf['MYSQL']['STAT']['USER']." -p".$gconf['MYSQL']['STAT']['PASS']." -P".
                $gconf['MYSQL']['STAT']['PORT']." -Dbas_stat -e \"$sql\" |gzip -c >$LOG_PATH/stat_lashou_pay_trade/$logyear/stat_lashou_pay_trade_$basedate_short.gz");      
    glog("load done ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

    glog("done ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

} catch (PDOException $e){
    glog("DB error :".$e->getMessage()."\n");
    return false;
}
