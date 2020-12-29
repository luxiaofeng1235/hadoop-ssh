<?php
function funcGetTaskByType($type){
    $sql = "select task_id,plan_id,cmd,basetime from etl_task where type=$type and status=7 and UNIX_TIMESTAMP()>=UNIX_TIMESTAMP(basetime)";
    $dbh = ggetMysqlHandle('ETL','dw_etl');
    $result = mysql_query($sql,$dbh);
    $plan_ids = array();
    $plan_late = array();
    $data = array();
    while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
        $rs[] = $row;
	$plan_ids[$row['plan_id']] = 1;
    }
    if(empty($rs)){
	return array();
    }

    $meta = array();
    $frequency = array();
    $sql = "select plan_id,latency,meta,frequency from etl_plan where plan_id in(".implode(",",array_keys($plan_ids)).")";
    $result = mysql_query($sql,$dbh);
    while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
    	$plan_late[$row['plan_id']] = $row['latency'];
	$meta[$row['plan_id']] = $row['meta'];
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
	return array();
    }

    $dep_plan = array();
    $sql = "select plan_id,dep_plan_id,rely from etl_dep where status=1 and plan_id in(".implode(",",array_keys($plan_ids)).")";
    $result = mysql_query($sql,$dbh);
    while($row= mysql_fetch_array($result,MYSQL_ASSOC)){
	$dep_plan[$row['plan_id']][$row['dep_plan_id']] = $row['rely'];
    }
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
	$data[] = array('meta'=>$meta[$val['plan_id']])+$val;
    }
    return $data;
}

function funcEtlTaskRun($cmd){
    $cmdArr = array();
    foreach($cmd as $key => $val){
	$cmdArr[] = $val['cmd'];
    }
return $cmdArr;
    $sumCode = 0;
    $php = $gconf['PHP_BIN'];
    $stdate = date('Y-m-d',$sttime-14*86400);
    $edate = date('Y-m-d',$etime);
    foreach (array("go_buy","go_goods","go_sp","go_order","go_visit_trade_no","reg_user","user_reg_session","spe_activity") as $table) { 
        $cmdArr []= "$php loader.php $table all $stdate $edate";
    }
    glog("postload:begin  $sttime $etime");
    $ret = gprun($cmdArr,1);
    glog("postload:end  $sttime $etime");
    foreach ($ret as $r) {
        $sumCode += $r['retcode'];
        if ($r['retcode']>0) {
            glog(sprintf("load:ret = %s cmd = %s",$r['retcode'],$r['cmd']));
            glog($r['output']);
        }
    }
}

