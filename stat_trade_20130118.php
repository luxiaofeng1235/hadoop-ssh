<?php
//此脚本生成bas_stat.stat_lashou_trade数据表，同时在mfs系统记录日志，按年存储
include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}

// 写入数据表
$table_stat_lashou_trade= 'bas_stat.stat_lashou_trade';

//读取数据表
$table_go_log= 'stat_cache.stat_www_visit_log_'.date('Ymd',strtotime($basedate));
$table_go_buy= 'stat_cache.go_buy';
$table_trade_no= 'stat_cache.go_visit_trade_no';

try{
    $maindsn = sprintf('mysql:host=%s;port=%d;dbname=stat_cache',$gconf['MYSQL']['MAIN']['HOST'],$gconf['MYSQL']['MAIN']['PORT']);
    $dbhDC= new PDO($maindsn,$gconf['MYSQL']['MAIN']['USER'],$gconf['MYSQL']['MAIN']['PASS'],array(PDO::ATTR_PERSISTENT => true));
    $statdsn = sprintf('mysql:host=%s;port=%d;dbname=bas_stat',$gconf['MYSQL']['STAT']['HOST'],$gconf['MYSQL']['STAT']['PORT']);
    $dbhBA= new PDO($statdsn,$gconf['MYSQL']['STAT']['USER'],$gconf['MYSQL']['STAT']['PASS'],array(PDO::ATTR_PERSISTENT => true));

    $tableSql = "
CREATE TABLE IF NOT EXISTS `stat_lashou_trade` (
  `stdate` date DEFAULT NULL,
  `trade_no` varchar(20) DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `client_key` varchar(32) DEFAULT NULL,
  `qdh_session` int(11) DEFAULT NULL,
  `qdh_client` int(11) DEFAULT NULL,
  `qdh_trade` int(11) DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `goods_id` int(11) DEFAULT NULL,
  `new_cat` int(11) DEFAULT NULL,
  `type` tinyint(4) NOT NULL,
  `sp_id` int(11) NOT NULL,
  `channel` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `buy_time` datetime DEFAULT NULL,
  PRIMARY KEY (`trade_no`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_channel_id` (`channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
    $dbhBA->exec($tableSql);

    $orderSql = "
	select date(buy_time) as stdate , b.trade_no, b.buyer_id ,b.goods_id ,b.amount ,b.total_fee, b.buy_time, b.gross,b.new_cat,
	b.type,b.sp_id,b.channel,b.price,b.cost_price,v.session_id,v.client_key 
	from $table_go_buy b 
	inner join $table_trade_no v
	on b.trade_no = v.trade_no
	where b.buy_time between '$basedate 00:00:00' and '$basedate 23:59:59'";
    $res = $dbhDC->query($orderSql);
    $tradeArr = array();
    glog("go_buy trade_no ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $tradeArr[$r['trade_no']] = $r;
    }
    $sql = " delete from $table_stat_lashou_trade where stdate = '$basedate'";
    $dbhBA->query($sql);

    $sql_tmp = "select visit_time, session_id, source, city_id, user_id,id visit_id from $table_go_log where user_id > 0 and 
		url_type in (58,62,63)  ";
    $res = $dbhDC->query($sql_tmp);
    $sessArr = array();
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        if (isset($sessArr[$r['session_id']])) {
           $sessArr[$r['session_id']] []= $r;
        } else {
           $sessArr[$r['session_id']] = array( 0 => $r);
        }
    }
    foreach ($tradeArr as $trade_no => &$r) {
        if (!isset($sessArr[$r['session_id']])) {
            unset($tradeArr[$trade_no]);
            continue;
        }
        $tmp = $sessArr[$r['session_id']];
        if (count($tmp) == 1 && $tmp[0]['user_id'] == $r['buyer_id']) {
            $r['channel_id'] = $tmp[0]['source'];
            $r['city_id'] = $tmp[0]['city_id'];
	    $r['visit_id'] = $tmp[0]['visit_id'];
            continue;
        } else if (count($tmp) > 1) {
            usort($tmp,create_function('$a,$b',
                '$base = '.strtotime($r['buy_time']).';
            $aa = abs($base - strtotime($a["visit_time"])); 
            $bb = abs($base - strtotime($b["visit_time"]));
            if ($aa == $bb) return 0; 
            return ($aa < $bb) ? -1 : 1;'));
            $r['channel_id'] = $tmp[0]['source'];
            $r['city_id'] = $tmp[0]['city_id'];
	    $r['visit_id'] = $tmp[0]['visit_id'];
        } else {
            continue;
        }
    }
    glog("session ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");
    $arr = array_chunk($tradeArr,500);
    foreach ($arr as &$part) {
        $sql = "replace into $table_stat_lashou_trade (stdate,trade_no,channel_id,city_id,buyer_id,goods_id,new_cat,gross,amount,total_fee,buy_time,
		session_id,client_key,qdh_session,qdh_client,qdh_trade,visit_id,type,sp_id,channel,price,cost_price) values ";
        $q = array();
        foreach ($part as &$r) {
            $str = sprintf("('%s','%s',%s,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',%s,%s,%s,'%s','%s','%s','%s','%s','%s')",
                $basedate,$r['trade_no'],
                isset($r['channel_id']) ? $r['channel_id'] : "NULL",
                $r['city_id'],$r['buyer_id'],$r['goods_id'],$r['new_cat'],$r['gross'],$r['amount'],$r['total_fee'],$r['buy_time'],
		$r['session_id'],$r['client_key'],getqdhByStr($r['session_id']),getqdhByStr($r['client_key']),getqdhByStr($r['trade_no']),
		$r['visit_id'],$r['type'],$r['sp_id'],$r['channel'],$r['price'],$r['cost_price']
            );
            $q []= $str;
        }
        $sql .= implode(',',$q);
        $dbhBA->exec($sql);
    }
    $basedate_short = date('Ymd',strtotime($basedate));
    $logyear = date("Y",strtotime($basedate_short));
    $LOG_PATH = $gconf['LOG_PATH'];
    $MYSQL_BIN = $gconf['MYSQL_BIN'];
    $sql_var=' -r  -quick --default-character-set=utf8  --skip-column ';
    system("mkdir -p $LOG_PATH/stat_lashou_trade/$logyear");
    $sql = "select * from $table_stat_lashou_trade where stdate='$basedate'";
    system("$MYSQL_BIN $sql_var -h".$gconf['MYSQL']['STAT']['HOST']." -u".$gconf['MYSQL']['STAT']['USER']." -p".$gconf['MYSQL']['STAT']['PASS']." -P".
		$gconf['MYSQL']['STAT']['PORT']." -Dbas_stat -e \"$sql\" |gzip -c >$LOG_PATH/stat_lashou_trade/$logyear/stat_lashou_trade_$basedate_short.gz");
 
} catch (PDOException $e){
    glog("DB error :".$e->getMessage()."\n");
    return false;
}
