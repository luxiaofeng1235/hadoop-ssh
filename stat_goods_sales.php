<?php
//此脚本生成bas_stat.stat_goods_sales数据表
include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}

include_once('inc.php');
global $gconfg;

$table = 'base_stat.stat_goods_salges';
// 写入数据表
$table_stat_goods_sales = 'bas_stat.stat_goods_sales';

//读取数据表
$tabl_v_type2_goods_sales = 'stat_cache.v_type2_goods_sales';
try{
    $maindsn = sprintf('mysql:host=%s;port=%d;dbname=stat_cache',$gconf['MYSQL']['MAIN']['HOST'],$gconf['MYSQL']['MAIN']['PORT']);
    $dbhDC= new PDO($maindsn,$gconf['MYSQL']['MAIN']['USER'],$gconf['MYSQL']['MAIN']['PASS'],array(PDO::ATTR_PERSISTENT => true));
    $statdsn = sprintf('mysql:host=%s;port=%d;dbname=bas_stat',$gconf['MYSQL']['STAT']['HOST'],$gconf['MYSQL']['STAT']['PORT']);
    $dbhBA= new PDO($statdsn,$gconf['MYSQL']['STAT']['USER'],$gconf['MYSQL']['STAT']['PASS'],array(PDO::ATTR_PERSISTENT => true));

    $tableSql = "
CREATE TABLE IF NOT EXISTS `stat_goods_sales` (
  `stdate` date DEFAULT NULL,
  `goods_id` int(11) NOT NULL,
  `product` varchar(200) NOT NULL,
  `new_cat` int(11) DEFAULT NULL,
  `view_uv` int(11) NOT NULL,
  `unit_cost_price` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `unit_gross` decimal(10,2) NOT NULL,
  `unit_gross_rate` decimal(10,2) NOT NULL,
  `amount` int(11) NOT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  `consumer_paid` int(11) NOT NULL,
  `rev_paid` decimal(10,2) NOT NULL,
  `refund_amount` int(11) NOT NULL,
  `refund_rev_paid` decimal(10,2) NOT NULL,
  `refund_consumer_paid` int(11) NOT NULL,
  PRIMARY KEY (`stdate`,`goods_id`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_goods_id` (`goods_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
    $dbhBA->exec($tableSql);

    $sql = "set names utf8";
    $res = $dbhDC->query($sql);
    $dbhBA->query($sql);

    $sql = "
	select stdate,goods_id,product,new_cat,view_uv,unit_cost_price,unit_price,unit_gross,unit_gross_rate,amount,gross,consumer_paid,rev_paid,refund_amount,refund_consumer_paid,refund_rev_paid
	from $tabl_v_type2_goods_sales
	where stdate = '$basedate'
    ";
    $res = $dbhDC->query($sql);
    $goods = array();
    glog("data select ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."--$basedate\n");
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	$goods[$r['goods_id']] = $r;        
    }
    if(!$goods){
	return false;	
    }    
	
    $sql = " delete from $table_stat_goods_sales where stdate = '$basedate'";
    $dbhBA->query($sql);

    $q = array();
    $i = 0;
    foreach($goods as $key => $val){
	$i++;
	$str = sprintf("('%s','%d','%s','%d','%f','%f','%f','%f','%d','%f','%d','%f','%d','%d','%f','%d')",
                $basedate,$val['goods_id'],addslashes($val['product']),$val['view_uv'],$val['unit_cost_price'],$val['unit_price'],$val['unit_gross'],$val['unit_gross_rate'],
		$val['amount'],$val['gross'],$val['consumer_paid'],$val['rev_paid'],$val['refund_amount'],$val['refund_consumer_paid'],$val['refund_rev_paid'],$val['new_cat']);
	$q[] = $str;
	if($i>=500){
    		$sql = "insert into $table_stat_goods_sales(`stdate`,`goods_id`,`product`,`view_uv`,`unit_cost_price`,`unit_price`,`unit_gross`,`unit_gross_rate`,`amount`,`gross`,
			`consumer_paid`,`rev_paid`,`refund_amount`,`refund_consumer_paid`,`refund_rev_paid`,`new_cat`) values ".implode(",",$q);
 		$rs = $dbhBA->query($sql);
		$i = 0;
		$q = array();
	}
    }
    if($i>0){
    	$sql = "insert into $table_stat_goods_sales(`stdate`,`goods_id`,`product`,`view_uv`,`unit_cost_price`,`unit_price`,`unit_gross`,`unit_gross_rate`,`amount`,`gross`,
			`consumer_paid`,`rev_paid`,`refund_amount`,`refund_consumer_paid`,`refund_rev_paid`,`new_cat`) values ".implode(",",$q);
 	$rs = $dbhBA->query($sql);
    }

} catch (PDOException $e){
    glog("DB error :".$e->getMessage()."$basedate\n");
    return false;
}
