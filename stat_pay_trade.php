<?php
//此脚本生成bas_stat.stat_lashou_pay_trade,bas_stat.stat_lashou_day同时在mfs系统记录日志，按年存储
include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}

// 写入数据表
$table_stat_lashou_pay_trade = 'bas_stat.stat_lashou_pay_trade';
$table_tmp = "stat_lashou_pay_trade_".date("Ymd",strtotime($basedate));
$table_stat_lashou_day_tmp = 'stat_lashou_day_tmp'.date("Ymd",strtotime($basedate));
$table_stat_lashou_day= 'bas_stat.stat_lashou_day';

//读取数据表
$table_trade_no= 'stat_cache.go_visit_trade_no';
$table_go_order = 'stat_cache.go_order';
$table_stat_lashou_trade = 'bas_stat.stat_lashou_trade';
$table_go_log= 'stat_cache.stat_www_visit_log_'.date('Ymd',strtotime($basedate));
$table_user = 'stat_cache.reg_user';
$table_cost_day= 'bas_entity.channel_cost_day';

try{
    $maindsn = sprintf('mysql:host=%s;port=%d;dbname=stat_cache',$gconf['MYSQL']['MAIN']['HOST'],$gconf['MYSQL']['MAIN']['PORT']);
    $dbhDC= new PDO($maindsn,$gconf['MYSQL']['MAIN']['USER'],$gconf['MYSQL']['MAIN']['PASS'],array(PDO::ATTR_PERSISTENT => true));
    $statdsn = sprintf('mysql:host=%s;port=%d;dbname=bas_stat',$gconf['MYSQL']['STAT']['HOST'],$gconf['MYSQL']['STAT']['PORT']);
    $dbhBA= new PDO($statdsn,$gconf['MYSQL']['STAT']['USER'],$gconf['MYSQL']['STAT']['PASS'],array(PDO::ATTR_PERSISTENT => true));

    $tableSql = "
CREATE temporary TABLE `".$table_tmp."`(
  `trade_no` varchar(20) NOT NULL,
  PRIMARY KEY (`trade_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `stat_lashou_pay_trade` (
  `stdate` date NOT NULL,
  `trade_no` varchar(20) NOT NULL,
  `order_id` bigint(16) NOT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `client_key` varchar(32) DEFAULT NULL,
  `qdh_session` int(11) DEFAULT NULL,
  `qdh_client` int(11) DEFAULT NULL,
  `qdh_trade` int(11) DEFAULT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `channel_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `goods_id` int(11) NOT NULL,
  `new_cat` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `sp_id` int(11) NOT NULL,
  `channel` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `convey_fee` decimal(10,2) DEFAULT NULL,
  `payed` decimal(10,2) DEFAULT NULL,
  `charge_pay` decimal(10,2) DEFAULT NULL,
  `epurse_payed` decimal(10,2) DEFAULT NULL,
  `youhui_fee` decimal(10,2) DEFAULT NULL,
  `pay_time` datetime DEFAULT NULL,
  `cps_type` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`trade_no`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_channel_id` (`channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE temporary TABLE `".$table_stat_lashou_day_tmp."` (
  `stdate` date NOT NULL ,
  `channel_id` int(11) NOT NULL,
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
  `flow_cost` decimal(10,2) DEFAULT NULL,
  `avg_day_cost` decimal(10,2) DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`stdate`,`channel_id`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_channel_id` (`channel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `stat_lashou_day` (
  `stdate` date NOT NULL ,
  `channel_id` int(11) NOT NULL,
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
  `flow_cost` decimal(10,2) DEFAULT NULL,
  `avg_day_cost` decimal(10,2) DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`stdate`,`channel_id`),
  KEY `ix_stdate` (`stdate`),
  KEY `ix_channel_id` (`channel_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
        ";
    $dbhBA->exec($tableSql);

    //计算付款订单
    $orderSql = "
	select b.trade_no, b.buyer_id ,b.goods_id ,b.amount ,b.total_fee, b.gross,b.new_cat,b.order_id,b.convey_fee,b.payed,b.charge_pay,
	b.epurse_payed,b.add_time as pay_time,b.type,b.sp_id,b.channel,b.price,b.cost_price,v.session_id,v.client_key 
	from $table_go_order b 
	inner join $table_trade_no v
	on b.trade_no = v.trade_no
	where b.add_time between '$basedate 00:00:00' and '$basedate 23:59:59' and action_type=0";
    $res = $dbhDC->query($orderSql);
    $tradeArr = array();
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $tradeArr[$r['trade_no']] = $r;
    }
    glog("go_order trade_no ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");
    $sql = " delete from $table_stat_lashou_pay_trade where stdate = '$basedate'";
    $dbhBA->query($sql);

    $arr = array_chunk($tradeArr,500);
    foreach ($arr as &$part) {
        $sql = "replace into $table_tmp (trade_no) values ";
        $q = array();
        foreach ($part as &$r) {
            $str = sprintf("('%s')",$r['trade_no']);
            $q []= $str;
        }
        $sql .= implode(',',$q);
        $dbhBA->exec($sql);
    }

    $sql = "
	select s.trade_no,s.visit_id,s.channel_id,s.city_id 
	from $table_tmp t inner join $table_stat_lashou_trade s on t.trade_no = s.trade_no";
    $res = $dbhBA->query($sql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	$tradeArr[$r['trade_no']]['visit_id'] = $r['visit_id'];
	$tradeArr[$r['trade_no']]['channel_id'] = $r['channel_id'];
	$tradeArr[$r['trade_no']]['city_id'] = $r['city_id'];
    }
    glog("stat_lashou_trade ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

    $arr = array_chunk($tradeArr,500);
    foreach ($arr as &$part) {
        $sql = "replace into $table_stat_lashou_pay_trade (stdate,trade_no,channel_id,city_id,buyer_id,goods_id,new_cat,gross,amount,total_fee,pay_time,
		session_id,client_key,qdh_session,qdh_client,qdh_trade,visit_id,type,sp_id,channel,price,cost_price,order_id,convey_fee,
		payed,charge_pay,epurse_payed,youhui_fee,cps_type) values ";
        $q = array();
        foreach ($part as &$r) {
            $str = sprintf("('%s','%s',%s,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',%s,%s,%s,'%s','%s','%s','%s','%s','%s','%s','%s',
		'%s','%s','%s',%s,%s)",
                $basedate,$r['trade_no'],
                isset($r['channel_id']) ? $r['channel_id'] : "NULL",
                $r['city_id'],$r['buyer_id'],$r['goods_id'],$r['new_cat'],$r['gross'],$r['amount'],$r['total_fee'],$r['pay_time'],
		$r['session_id'],$r['client_key'],getqdhByStr($r['session_id']),getqdhByStr($r['client_key']),getqdhByStr($r['trade_no']),
		$r['visit_id'],$r['type'],$r['sp_id'],$r['channel'],$r['price'],$r['cost_price'],$r['order_id'],$r['convey_fee'],$r['payed'],
		$r['charge_pay'],$r['epurse_payed'],"NULL","NULL"
            );
            $q []= $str;
        }
        $sql .= implode(',',$q);
        $dbhBA->exec($sql);
    }

    glog("stat_lashou_pay_trade done ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

    //生成stat_lashou_day
    $sql_source="select source as channel_id,count(distinct(session_id)) as click,count(id) as pv,count(distinct(client_key)) as uv 
                from $table_go_log t group by source";
    $res = $dbhDC->query($sql_source);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $sql = sprintf("insert into $table_stat_lashou_day_tmp (stdate,channel_id,click,pv,uv) values ('%s','%s','%s','%s','%s') ",
        $basedate,$r['channel_id'],$r['click'],$r['pv'],$r['uv']);
        $dbhBA->exec($sql);
    }
    glog("pv uv done ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."\n");

    $sql_tmp="insert into $table_stat_lashou_day_tmp (stdate,channel_id)
        select '$basedate', a.channel_id from bas_entity.channel_desc a left  join  
        $table_stat_lashou_day_tmp b on a.channel_id=b.channel_id where b.channel_id is null";
    $dbhBA->exec($sql_tmp);

    $userSql = "
    select source as channel_id,count(distinct a.user_id) as num_reg  from $table_go_log a
    inner join(
        select min(id) as id from $table_go_log a where user_id <> 0 group by user_id
    ) b on a.id=b.id
    inner join(
        select user_id from $table_user where add_time between '$basedate 00:00:00' and '$basedate 23:59:59' and (client = 0 or client = 255)
    ) c on a.user_id = c.user_id
    group by source    
    ";
    $res = $dbhDC->query($userSql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $sql = sprintf("update $table_stat_lashou_day_tmp set num_reg = %s where stdate ='%s' and channel_id = '%s' ",
            $r['num_reg'],$basedate,$r['channel_id']);
        $dbhBA->exec($sql);
    }

    //stat_lashou_day全部订单信息
    $sql = "update $table_stat_lashou_day_tmp a inner join (select stdate,channel_id,count(distinct(buyer_id)) as consumer , count(trade_no) as orders, 
            sum(total_fee) as rev   
            from $table_stat_lashou_trade t where stdate = '$basedate' group by stdate, channel_id) b on a.channel_id = b.channel_id and a.stdate =  b.stdate
            set a.consumer = b.consumer , a.orders = b.orders, a.rev = b.rev ";
    $dbhBA->exec($sql);

    //stat_lashou_day付款订单信息
    $sql = "update $table_stat_lashou_day_tmp a inner join (select stdate,channel_id,count(distinct(buyer_id)) as consumer_paid , count(trade_no) as orders_paid, 
                sum(total_fee) as rev_paid, sum(gross) as gross 
                from $table_stat_lashou_pay_trade t where t.stdate = '$basedate' group by stdate,channel_id) b on a.channel_id = b.channel_id 
                and a.stdate =  b.stdate
                set a.consumer_paid = b.consumer_paid , a.orders_paid = b.orders_paid, a.rev_paid = b.rev_paid , a.gross = b.gross";
    $dbhBA->exec($sql);

    //从channel_day_cost里更新成本
    $sql = "update $table_stat_lashou_day_tmp a inner join $table_cost_day c on a.stdate = c.stdate and a.channel_id = c.channel_id set a.flow_cost = c.cost";
    $dbhBA->exec($sql);

    //从临时表更新进正式表
    $statSql = " delete from $table_stat_lashou_day where stdate = '$basedate';
        replace into $table_stat_lashou_day ( stdate,channel_id ,click,pv,uv,num_reg,consumer,consumer_paid,orders,orders_paid,rev,rev_paid,flow_cost,gross) 
        select stdate,channel_id ,click,pv,uv,num_reg,consumer,consumer_paid,orders,orders_paid,rev,rev_paid,flow_cost,gross from $table_stat_lashou_day_tmp";
    $pdo = $dbhBA->prepare($statSql);
    $pdo->execute();

} catch (PDOException $e){
    glog("DB error :".$e->getMessage()."\n");
    return false;
}
