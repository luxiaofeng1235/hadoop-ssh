<?php
include_once("inc.php");
global $gconf;
if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}

if (isset($argv[2])) {
    $event = $argv[2];
} else {
    echo "event error \n";
    return ;
}

//读取数据表
$table_go_log= 'stat_cache.stat_www_visit_log_'.date('Ymd',strtotime($basedate));
$table_go_buy= 'stat_cache.go_buy';
$table_trade_no= 'stat_cache.go_visit_trade_no';
$table_user_reg_session = 'stat_cache.user_reg_session';
$table_go_order = 'stat_cache.go_order';

$data = array();

try{
    $maindsn = sprintf('mysql:host=%s;port=%d;dbname=stat_cache',$gconf['MYSQL']['MAIN']['HOST'],$gconf['MYSQL']['MAIN']['PORT']);
    $dbhDC= new PDO($maindsn,$gconf['MYSQL']['MAIN']['USER'],$gconf['MYSQL']['MAIN']['PASS'],array(PDO::ATTR_PERSISTENT => true));

    //pv,uv,click
    $sql = "
	select count(a.id) as pv,count(distinct a.session_id) as click,count(distinct a.client_key) as uv
	from $table_go_log a
	where a.url_event = '$event' or a.ref_event = '$event'
    ";    
    $res = $dbhDC->query($sql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	$data = $r;
    }

    //num_reg
    $sql = "
	select count(distinct b.user_id) as num_reg 
	from $table_go_log a
	inner join
	$table_user_reg_session b
	on a.session_id = b.session_id
	where (a.url_event = '$event' or a.ref_event = '$event') and b.add_time >= '$basedate 00:00:00' and b.add_time <= '$basedate 23:59:59'
    ";
    $res = $dbhDC->query($sql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	$data['num_reg'] = $r['num_reg'];
    }
    //order
    $sql = "
	select count(distinct a.buyer_id) as buyer,count(a.trade_no) as orders,sum(a.total_fee) as total_fee 
	from 
	$table_go_buy a
	inner join(
	    select distinct b.trade_no
	    from $table_go_log a
	    inner join 
	    $table_trade_no b
	    on a.session_id = b.session_id
	    where a.url_event = '$event' or a.ref_event = '$event'
	)b
	on a.trade_no = b.trade_no
	where a.buy_time >= '$basedate 00:00:00' and a.buy_time <= '$basedate 23:59:59'
    ";
    $res = $dbhDC->query($sql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	$data['buyer'] = $r['buyer'];
	$data['orders'] = $r['orders'];
	$data['total_fee'] = $r['total_fee'];
    }

    $sql = "
	select count(distinct a.buyer_id) as buyer_paid,count(distinct a.trade_no) as orders_paid,sum(a.total_fee) as total_fee_paid 
	from 
	$table_go_order a
	inner join(
	    select distinct b.trade_no
	    from $table_go_log a
	    inner join 
	    $table_trade_no b
	    on a.session_id = b.session_id
	    where a.url_event = '$event' or a.ref_event = '$event'
	)b
	on a.trade_no = b.trade_no
	where a.action_type=0 and (a.add_time between '$basedate 00:00:00' and '$basedate 23:59:59')
    ";
    $res = $dbhDC->query($sql);
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	$data['buyer_paid'] = $r['buyer_paid'];
	$data['orders_paid'] = $r['orders_paid'];
	$data['total_fee_paid'] = $r['total_fee_paid'];
    }

    $data['rev_order_paid'] = $data['total_fee_paid']/$data['orders_paid'];
    $data['order_rate'] = $data['buyer']/$data['uv'];
    $data['conver_rate'] = $data['orders']/$data['click'];
    $data['rev_order'] = $data['total_fee']/$data['orders'];
    return $data;
 
} catch (PDOException $e){
    glog("DB error :".$e->getMessage()."\n");
    return false;
}
