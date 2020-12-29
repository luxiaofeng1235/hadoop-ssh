<?php
#此脚本执行任务
include_once("inc.php");

if (isset($argv[1])) {
    $task_type = $argv[1];
}else{
    echo "usage 1: task_run task_type\n";
    exit(1);
}

$rs = system("ps aux|grep 'php $_self.php $task_type'|wc -l");
if($rs>4){
    glog("task run $task_type exist ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$task_limit = count($gconf['WORK_NODE']);

glog("task run $task_type begin ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");

$sql = "select task_id,cmd from etl_task where status=11 and type=$task_type limit $task_limit";
$dbh = ggetMysqlHandle('ETL','dw_etl');
$result = mysql_query($sql,$dbh);
$rs = array();
$cmdArr = array();
$cmdTask = array();
while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
    $rs[] = $row;
    $cmdArr[] = $row['cmd'];
    $cmdTask[$row['cmd']] = $row['task_id'];
    $sql = "update etl_task set status=8,start_time=now() where task_id=".$row['task_id'];
    mysql_query($sql,$dbh);
    $sql = "insert into etl_log(`task_id`,`time`,`before_status`,`end_status`)values(".$row['task_id'].",now(),'11','8')";
    mysql_query($sql,$dbh);
}

if(empty($rs)){
    glog("task run $task_type empty ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
    return ;
}

$ret = gprun($cmdArr,1);    
foreach ($ret as $r) {
    if ($r['retcode']>0) {
        glog(sprintf("load:ret = %s cmd = %s",$r['retcode'],$r['cmd']));
        glog($r['output']);
	$status = 10;
    }else{
	$status = 9;
    }
    $task = $cmdTask[$r['cmd']];
    $sql = "update etl_task set status = $status,end_time=now() where task_id=$task";
    mysql_query($sql,$dbh);
    $sql = "insert into etl_log(`task_id`,`time`,`before_status`,`end_status`)values($task,now(),'8',$status)";
    mysql_query($sql,$dbh);
}

glog("task run $task_type end ".date('Y-m-d H:i:s')." ".(memory_get_usage(true)/1024/1024)."M\n");
