<?php
#此脚本初始化所有应该执行的任务
include_once("inc.php");

glog("task init begin ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
$rs = system("ps aux|grep 'php $_self'|wc -l");
if($rs>4){
    glog("task init exist ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$php = $gconf['PHP_BIN'];
$rs = array();
$time = time()+300;
$sql = "select plan_id,meta,type,type_param,frequency from etl_plan where status=1 and $time-UNIX_TIMESTAMP(last_basetime)>=frequency";
$dbh = ggetMysqlHandle('ETL','dw_etl');
$result = mysql_query($sql,$dbh);
while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
    $rs[] = $row;
}

if(empty($rs)){
    glog("task init not found ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$i = 0;
foreach($rs as $key=>$val){
    if($val['frequency']=='86400'){
	$basetime=date("Y-m-d H:i:s", (int)((time()+3600*8)/86400)*86400-8*3600);
	$tasktime = date("Y-m-d H:i:s", strtotime($basetime)-86400);
	glog("task init basetime: $basetime ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    }else{

    }
    switch($val['type']){
	case 5:
	    $cmd = "$php getter.php ".$val['meta']." ".date("Y-m-d",strtotime($tasktime));
	    break;
	case 4:
	    $param = json_decode($val['type_param']);
	    $cmd = "$php loader.php ".$param->totb." ".$param->loadtype." ".date("Y-m-d",strtotime($tasktime))." ".date("Y-m-d",strtotime($tasktime));
	    break;
	default:
	    break;
    }
    $sql = "select task_id from etl_task where plan_id=".$val['plan_id']." and basetime='$basetime'";
    glog("task init sql: $sql ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    $result = mysql_query($sql,$dbh);
    $result = mysql_fetch_row($result);
    if(empty($result)){
    	$sql = "insert into etl_task(`plan_id`,`cmd`,`type`,`basetime`,`status`,`add_time`)values(".$val['plan_id'].",'$cmd',".$val['type'].",'$basetime','7',now())";
    	mysql_query($sql,$dbh);
   	$task_id = mysql_insert_id();
    	$sql = "insert into etl_log(`task_id`,`time`,`before_status`,`end_status`)values($task_id,now(),'6','7')";
    	mysql_query($sql,$dbh);
	$i++;
    }

    $sql = "update etl_plan set last_basetime='$basetime' where plan_id=".$val['plan_id'];
    mysql_query($sql,$dbh);
}
glog("task init end ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M add task $i\n");
