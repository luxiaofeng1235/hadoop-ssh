<?php
#此脚本检测所有应该执行的任务，将任务状态由初始化状态更改为等待执行状态

include_once("inc.php");

glog("task check begin ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
$rs = system("ps aux|grep 'php $_self'|wc -l");
if($rs>4){
    glog("task check exist ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$sql = "select task_id,plan_id,basetime from etl_task where status=7 and UNIX_TIMESTAMP()>=UNIX_TIMESTAMP(basetime)";
$dbh = ggetMysqlHandle('ETL','dw_etl');
$result = mysql_query($sql,$dbh);

$plan_ids = array();
$plan_late = array();
$rs = array();

while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
    $rs[] = $row;
    $plan_ids[$row['plan_id']] = 1;
}

if(empty($rs)){
    glog("task check not exist ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$frequency = array();
$sql = "select plan_id,latency,frequency from etl_plan where plan_id in(".implode(",",array_keys($plan_ids)).")";
$result = mysql_query($sql,$dbh);
while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
    $plan_late[$row['plan_id']] = $row['latency'];
    $frequency[$row['plan_id']] = $row['frequency'];
}

$plan_ids = array();	
foreach($rs as $key => $val){
    if(time()>=(strtotime($val['basetime'])+$plan_late[$val['plan_id']])){
	$plan_ids[$val['plan_id']] = 1;
    }else{
	unset($rs[$key]);
    }
}

if(empty($rs)){
    glog("task check start not exist ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$dep_plan = array();
$sql = "select plan_id,dep_plan_id,rely from etl_dep where status=1 and plan_id in(".implode(",",array_keys($plan_ids)).")";
$result = mysql_query($sql,$dbh);
while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
    $dep_plan[$row['plan_id']][$row['dep_plan_id']] = $row['rely'];
}

$j = 0;
foreach($rs as $key => $val){
    if(isset($dep_plan[$val['plan_id']])){
	$done = true;
	foreach($dep_plan[$val['plan_id']] as $dep_plan_id => $rely){
	    $dates = array();
	    $tmp = array();
	    for($i=0;$i<=$rely;$i++){
		$dates["'".date("Y-m-d H:i:s",strtotime($val['basetime'])-$i*$frequency[$val['plan_id']])."'"] = 1;
	    }
	    $sql = "select task_id from etl_task where plan_id=$dep_plan_id and basetime in(".implode(",",array_keys($dates)).") and status=9";
    	    $result = mysql_query($sql,$dbh);
    	    while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
		$tmp[$row['task_id']] = 1;
	    }
	    if(count($tmp) != count($dates)){
		$done = false;
		break;
	    }
	}
	if(!$done){
	    continue;
	}
    }
    $sql = "update etl_task set status = 11 where task_id = ".$val['task_id'];
    mysql_query($sql,$dbh);
    $sql = "insert into etl_log(`task_id`,`time`,`before_status`,`end_status`)values(".$val['task_id'].",now(),'7','11')";
    mysql_query($sql,$dbh);
    $j++;
}

glog("task check end ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M begin $j\n");


