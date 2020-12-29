<?php
//此脚本生成stat_lashou_city_day

include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}

$table_city_day = 'bas_stat.stat_lashou_city_day';
$table_stat_lashou_trade = 'bas_stat.stat_lashou_trade';
$table_go_log= 'stat_cache.stat_www_visit_log_'.date('Ymd',strtotime($basedate));
$table_user = 'stat_cache.reg_user';
$table_stat_lashou_pay_trade = 'bas_stat.stat_lashou_pay_trade';

try{
    $maindsn = sprintf('mysql:host=%s;port=%d;dbname=stat_cache',$gconf['MYSQL']['MAIN']['HOST'],$gconf['MYSQL']['MAIN']['PORT']);
    $dbhDC= new PDO($maindsn,$gconf['MYSQL']['MAIN']['USER'],$gconf['MYSQL']['MAIN']['PASS'],array(PDO::ATTR_PERSISTENT => true));
    $statdsn = sprintf('mysql:host=%s;port=%d;dbname=bas_stat',$gconf['MYSQL']['STAT']['HOST'],$gconf['MYSQL']['STAT']['PORT']);
    $dbhBA= new PDO($statdsn,$gconf['MYSQL']['STAT']['USER'],$gconf['MYSQL']['STAT']['PASS'],array(PDO::ATTR_PERSISTENT => true));

    $tableSql = " 
CREATE temporary TABLE `stat_lashou_city_day_tmp` (
  `stdate` date NOT NULL ,
  `channel_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `click` int(20) DEFAULT NULL,
  `pv` int(20) DEFAULT NULL,
  `uv` int(20) DEFAULT NULL,
  `num_reg` int(11) DEFAULT NULL,
  `consumer` int(11) DEFAULT NULL ,
  `consumer_paid` int(11) DEFAULT NULL,
  `orders` int(11) DEFAULT NULL ,
  `orders_paid` int(11) DEFAULT NULL,
  `rev` decimal(10,2) DEFAULT NULL,
  `rev_paid` decimal(10,2) DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`stdate`,`channel_id`,`city_id`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_city_id` (`city_id`),
  KEY `ix_channel_id` (`channel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `stat_lashou_city_day` (
  `stdate` date NOT NULL ,
  `channel_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `click` int(20) DEFAULT NULL,
  `pv` int(20) DEFAULT NULL,
  `uv` int(20) DEFAULT NULL,
  `num_reg` int(11) DEFAULT NULL,
  `consumer` int(11) DEFAULT NULL ,
  `consumer_paid` int(11) DEFAULT NULL,
  `orders` int(11) DEFAULT NULL ,
  `orders_paid` int(11) DEFAULT NULL,
  `rev` decimal(10,2) DEFAULT NULL,
  `rev_paid` decimal(10,2) DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`stdate`,`channel_id`,`city_id`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_channel_id` (`channel_id`),
  KEY `ix_city_id` (`city_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
    ";
    $dbhBA->exec($tableSql);

    $sql_source="select source channel_id,city_id,count(distinct(session_id)) click,count(id) pv,count(distinct(client_key)) uv from 
		$table_go_log group by source,city_id";
    glog(date('Y-m-d H:i:s')." $sql_source\n");    
    $res = $dbhDC->query($sql_source);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $sql = sprintf("insert into stat_lashou_city_day_tmp(stdate,channel_id,city_id,click,pv,uv) values ('%s','%s','%s','%s','%s','%s') ",
        $basedate,$r['channel_id'],$r['city_id'],$r['click'],$r['pv'],$r['uv']);
        $dbhBA->exec($sql);
    }

    $userSql = "
        select source as channel_id, a.city_id as city_id , count(distinct a.user_id) as num_reg  from $table_go_log a
        inner join(
            select min(id) as id from $table_go_log a where user_id <> 0 group by user_id
        ) b on a.id=b.id
        inner join(
	    select user_id from $table_user where add_time between '$basedate 00:00:00' and '$basedate 23:59:59' and (client = 0 or client = 255)
	) c on a.user_id = c.user_id
    	group by source,city_id    
    ";
    glog(date('Y-m-d H:i:s')." $userSql\n");
    $res = $dbhDC->query($userSql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $sql = sprintf("update stat_lashou_city_day_tmp  set num_reg = %s where stdate ='%s' and channel_id = '%s' and city_id = '%s'",
            $r['num_reg'],$basedate,$r['channel_id'],$r['city_id']);
        $dbhBA->exec($sql);
    }

    //全部数据
    $sql = "update stat_lashou_city_day_tmp a inner join (select stdate,channel_id,city_id,count(distinct(buyer_id)) as consumer , 
	    count(trade_no) as orders, sum(total_fee) as rev from $table_stat_lashou_trade t 
	    where stdate = '$basedate' group by stdate, channel_id,city_id) b on a.channel_id = b.channel_id and a.stdate =  b.stdate 
	    and a.city_id = b.city_id
            set a.consumer = b.consumer , a.orders = b.orders, a.rev = b.rev ";
    glog(date('Y-m-d H:i:s')." $sql\n");      
    $dbhBA->exec($sql);   

    //付款数据
    $sql = "update stat_lashou_city_day_tmp a inner join (select stdate,channel_id,city_id,count(distinct(buyer_id)) as consumer_paid , 
	    count(trade_no) as orders_paid, sum(total_fee) as rev_paid, sum(gross) as gross from $table_stat_lashou_pay_trade t 
	    where t.stdate = '$basedate' group by stdate,channel_id,city_id) b on a.channel_id = b.channel_id and a.stdate =  b.stdate 
	    and a.city_id = b.city_id
            set a.consumer_paid = b.consumer_paid , a.orders_paid = b.orders_paid, a.rev_paid = b.rev_paid , a.gross = b.gross";
    glog(date('Y-m-d H:i:s')." $sql\n");      
    $dbhBA->exec($sql);

    $statSql = " delete from $table_city_day where stdate = '$basedate';
        replace into $table_city_day ( stdate,channel_id ,city_id,click,pv,uv,num_reg,consumer,consumer_paid,orders,orders_paid,rev,rev_paid,gross) 
        select stdate,channel_id ,city_id,click,pv,uv,num_reg,consumer,consumer_paid,orders,orders_paid,rev,rev_paid,gross from stat_lashou_city_day_tmp";
    $pdo = $dbhBA->prepare($statSql);
    glog(date('Y-m-d H:i:s')."$statSql\n");   
    $pdo->execute();

} catch (PDOException $e){
    glog("DB error :".$e->getMessage()."\n");
    return false;
} 
