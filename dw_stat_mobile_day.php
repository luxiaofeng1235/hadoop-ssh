<?php
error_reporting(E_ALL);

	//数据库配置文件
	$dbpmhostName='10.168.31.30';
	$dbpmuserName='admin';
	$dbpmuserPass='12345678';
	$dbpmconnPort='3306';
	$dbpmdbName='bas_mid';

	$dbBrihostName='10.168.35.22';
	$dbBriuserName='dw_r';
	$dbBriuserPass='dw_r';
	$dbBriconnPort='3306';
	$dbBridbName='dw_srclog';



//连接数据库

$link_bas_db = mysqli_connect($dbpmhostName, $dbpmuserName,$dbpmuserPass);
if (!$link_bas_db) {
    die('bas_db Could not connect: ' . mysql_error());
}
echo "bas_db Connected successfully\n";

$link_bri_db = mysqli_connect("$dbBrihostName:$dbBriconnPort", "$dbBriuserName", "$dbBriuserPass");

if (!$link_bri_db) {
    die('bri_db Could not connect: ' . mysql_error());
}
echo "bri_db Connected successfully\n";


//echo "程序开始执行";
if (isset($argv[1]) && strtotime($argv[1]) > strtotime("2011-12-28")) {
	$stdate_sp = date('Y-m-d',strtotime($argv[1]));
} else {
	$stdate_sp = date('Y-m-d',time()-24*3600);
}
//把第一个参数格式化

$stdate_sp_short = date('Ymd', strtotime($stdate_sp));

//算出一天后的时间
$stdate_today = date('Y-m-d', strtotime($stdate_sp)+1*24*60*60);
//echo "删除存在的数据项\n";
//echo $stdate_sp_short."\n";

$sql_del="delete from bas_mid.mid_mobile_day where stdate='$stdate_sp'";
//echo $sql_del."\n";
$result = mysqli_query("$sql_del",$link_bas_db);

#insert channel_id and name first
$sql_tmp="select distinct '$stdate_sp' as stdate ,channel_id as channel_id , app_name,client_name  from dw_srclog.visit_app_log_$stdate_sp_short  a";
echo $sql_tmp."\n";
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
	extract($row);
$sql_tmp="insert into bas_mid.mid_mobile_day (stdate,channel_id,app_name,client_name) values ('$stdate_sp',$channel_id,'$app_name','$client_name') ";
     mysqli_query($sql_tmp,$link_bas_db) or die("Invalid query: $sql_tmp\n");
	}


#下载次数和渠道ID
$sql_tmp="select distinct '$stdate_sp' as stdate, channel_id as channel_id,app_name as app_name,client_name as client_name  from dw_srclog.visit_app_log_$stdate_sp_short  a GROUP BY channel_id,app_name,client_name ORDER BY channel_id ";
echo $sql_tmp."\n";

$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");

while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
	extract($row);
        /*
	$sql_tmp = <<<EOT
select  count(1) as downloads from
(select distinct client_id from dw_srclog.visit_app_log_$stdate_sp_short  where stdate = '$stdate_sp'  and channel_id = $channel_id and app_name = '$app_name' and client_name='$client_name') a
left join
(select distinct client_id from dw_srclog.visit_app_log_$stdate_sp_short where stdate < '$stdate_sp' and channel_id = $channel_id and app_name = '$app_name' and client_name='$client_name') b
on a.client_id = b.client_id
where b.client_id is NULL
EOT;
         */
        $sql_tmp = <<<EOT
select count(distinct a.client_id) downloads from dw_srclog.visit_app_log_$stdate_sp_short a inner join dw_wl.app_uv_initdate i on a.client_id = i.client_id and a.visit_time = i.init_time 
where channel_id = $channel_id and app_name = '$app_name' and client_name='$client_name'
EOT;

	$resultDown = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
	if ($downloadsRow = mysqli_fetch_array($resultDown,MYSQL_ASSOC) ) {
	//if ($downloadRow) {
		extract($downloadsRow);
		$sql_tmp=" update bas_mid.mid_mobile_day set downloads = $downloads where stdate='$stdate' and channel_id = $channel_id and app_name = '$app_name' and client_name = '$client_name'";
	   	echo $sql_tmp."\n";
	   	mysqli_query($sql_tmp,$link_bas_db) or die("Invalid query: $sql_tmp\n");
	} else {
		continue;
	}


}

//echo $stdate_sp_short."\n";
//注册人数
$stampBegin = ("$stdate_sp 00:00:00");
$stampEnd = ("$stdate_sp 23:59:59");
$sql_tmp = <<<EOT
select distinct user_id from dw_wl.statistice a
where a.reg_time between '$stampBegin' and '$stampEnd'
EOT;

echo $sql_tmp,"\n";
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
$newReg = array();
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
{
	extract($row);
	$newReg []= $user_id;
}

echo "found ".count($newReg)." new users\n";
$userStr = count($newReg) > 0 ? "a.user_id in (".implode(",",$newReg).")" : 1;
$sql_tmp=<<<EOT
select  channel_id as channel_id,app_name,client_name,count(distinct user_id) as num_reg  from  dw_srclog.visit_app_log_$stdate_sp_short  a
where  $userStr
group by channel_id,app_name,client_name
EOT;

//echo $sql_tmp."\n";
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
{
	extract($row);
  $sql_tmp="update bas_mid.mid_mobile_day set num_reg = $num_reg where stdate='$stdate_sp' and channel_id = $channel_id and app_name ='$app_name' and client_name = '$client_name'" ;
  //echo $sql_tmp."\n";
  mysqli_query("$sql_tmp",$link_bas_db) or die("Invalid query: $sql_tmp\n");
}

//查找打开次数

$sql_down="select count(url) as opens,app_name,channel_id as channel_id,client_name as client_name FROM dw_srclog.visit_app_log_$stdate_sp_short WHERE url like '%/version/utfversion%' group by channel_id,app_name,client_name";


$result = mysqli_query($sql_down,$link_bri_db) or die("Invalid query: $sql_down\n");

//解析数据
while($row=mysql_fetch_assoc($result)){
	extract($row);

	$sql="update bas_mid.mid_mobile_day set opens={$opens} WHERE stdate='$stdate_sp' and channel_id = $channel_id and app_name = '$app_name' and client_name = '$client_name'";
	//echo $sql."\n";
	mysqli_query($sql,$link_bas_db) or die("Invalid query: $sql\n");


}


//不考虑付款状态的订单数和订单金额
$sql_tmp=<<<EOT
select a.channel_id as channel_id,a.app_name,a.client_name,count(distinct trade_no) as orders,sum(total_fee) as rev  from  dw_srclog.visit_app_log_$stdate_sp_short  a INNER  JOIN(
select min(id) as id from  dw_srclog.visit_app_log_$stdate_sp_short  where user_id <> 0  group by user_id) b on a.id=b.id
LEFT JOIN dw_wl.wl_trade c
on a.user_id=c.buyer_id
where c.buy_time  between '$stdate_sp 00:00:00' and '$stdate_sp 23:59:59'
group by a.channel_id,a.app_name,a.client_name
EOT;
//echo $sql_tmp;
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
{
	extract($row);
  $sql_tmp="update bas_mid.mid_mobile_day set orders=$orders,rev=$rev  where stdate='$stdate_sp' and channel_id=$channel_id  and app_name = '$app_name' and client_name='$client_name'" ;
  //echo $sql_tmp."\n";
  mysqli_query("$sql_tmp",$link_bas_db) or die("Invalid query: $sql_tmp\n");
}

//不考虑付款状态的下单人数
$sql_tmp=<<<EOT
select a.channel_id as channel_id,a.app_name,a.client_name,count(distinct user_id) as consumer  from  dw_srclog.visit_app_log_$stdate_sp_short  a INNER  JOIN(
select min(id) as id from  dw_srclog.visit_app_log_$stdate_sp_short  where user_id <> 0  group by user_id) b on a.id=b.id
LEFT JOIN dw_wl.wl_trade c
on a.user_id=c.buyer_id
where  c.buy_time  between '$stdate_sp 00:00:00' and '$stdate_sp 23:59:59'
group by a.channel_id,a.app_name,a.client_name
EOT;
//echo $sql_tmp;
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
{
	extract($row);
  $sql_tmp="update bas_mid.mid_mobile_day set consumer=$consumer where stdate='$stdate_sp' and channel_id=$channel_id  and app_name = '$app_name' and client_name='$client_name'" ;
  //echo $sql_tmp."\n";
  mysqli_query("$sql_tmp",$link_bas_db) or die("Invalid query: $sql_tmp\n");
}

//已付款订单数和销售额
$sql_tmp=<<<EOT
select a.channel_id as channel_id,a.app_name,a.client_name,count(distinct trade_no) as orders_paid , sum(total_fee) as rev_paid from  dw_srclog.visit_app_log_$stdate_sp_short  a INNER  JOIN(
select min(id) as id from  dw_srclog.visit_app_log_$stdate_sp_short  where user_id <> 0  group by user_id) b on a.id=b.id
LEFT JOIN dw_wl.wl_pay_trade c
on a.user_id=c.buyer_id
where  c.pay_time  between '$stdate_sp 00:00:00' and '$stdate_sp 23:59:59'
group by a.channel_id,a.app_name,a.client_name
EOT;

//echo $sql_tmp."\n";
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
{
	extract($row);

$sql_tmp=<<<EOT
update bas_mid.mid_mobile_day
set orders_paid=$orders_paid,rev_paid=$rev_paid
where stdate='$stdate_sp' and channel_id=$channel_id and app_name = '$app_name' and client_name = '$client_name'
EOT;
  //echo $sql_tmp."\n";
  mysqli_query("$sql_tmp",$link_bas_db) or die("Invalid query: $sql_tmp\n");
}
//已付款订单下单人数
$sql_tmp=<<<EOT
select a.channel_id as channel_id,a.app_name,a.client_name,count(distinct user_id) as consumer_paid  from  dw_srclog.visit_app_log_$stdate_sp_short  a INNER  JOIN(
select min(id) as id from  dw_srclog.visit_app_log_$stdate_sp_short  where user_id <> 0  group by user_id) b on a.id=b.id
LEFT JOIN dw_wl.wl_pay_trade c
on a.user_id=c.buyer_id
where  c.pay_time  between '$stdate_sp 00:00:00' and '$stdate_sp 23:59:59'
group by a.channel_id,a.app_name,a.client_name
EOT;

//echo $sql_tmp."\n";
$result = mysqli_query("$sql_tmp",$link_bri_db) or die("Invalid query: $sql_tmp\n");
while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
{
	extract($row);

$sql_tmp=<<<EOT
update bas_mid.mid_mobile_day
set consumer_paid=$consumer_paid
where stdate='$stdate_sp' and channel_id=$channel_id and app_name = '$app_name' and client_name='$client_name'
EOT;
  //echo $sql_tmp."\n";
  mysqli_query("$sql_tmp",$link_bas_db) or die("Invalid query: $sql_tmp\n");
}
echo "更新计算值\n";
#$sql_tmp=<<<EOT
#update bas_mid.mid_mobile_day
# set pvuv=pv/uv,
#rev_order=rev/orders,
#rev_order_paid=rev_paid/orders_paid,
#order_rate=consumer/uv,
#couver_rate=orders/click,
#where stdate='$stdate_sp'
#EOT;

$sql="delete from bas_stat.stat_mobile_day where stdate='{$stdate_sp}'";

mysqli_query($sql,$link_bas_db);

$sql_tmp="insert into bas_stat.stat_mobile_day(opens,downloads,stdate,channel_id,app_name,client_name,num_reg,consumer,consumer_paid,orders,orders_paid,rev,rev_order,rev_paid,rev_order_paid,order_rate,couver_rate,roi)
	select opens,downloads,stdate,channel_id,app_name,client_name,num_reg,consumer,consumer_paid,orders,orders_paid,rev,rev_order,rev_paid,rev_order_paid,order_rate,couver_rate,roi  from bas_mid.mid_mobile_day where stdate='$stdate_sp'";
mysqli_query("$sql_tmp",$link_bas_db) or die("Invalid query: $sql_tmp\n");
//echo $sql_tmp."\n";

//echo $sql_del."\n";
$sql_del="delete from bas_mid.mid_mobile_day where stdate='$stdate_sp'";
$result = mysqli_query("$sql_del",$link_bas_db);

mysql_close($link_bas_db);
mysql_close($link_bri_db);
//echo "程序执行结束\n";

?>
