<?php
function gmemusage() {
    return (memory_get_usage(true)/1024/1024)."M/".ini_get('memory_limit');
}
$GLOBALS['log_file_name'] =$dir."/logs/$_self.$_hname.log.".date('Ym');
function glog($msg) {
    error_log(date('Y-m-d H:i:s')." $msg\n",3,$GLOBALS['log_file_name']);
}
function gloadconf($fname) {
    $ret = array('MYSQL'=>array(),'HIVEDB'=>array());
    $lines = explode("\n",file_get_contents($fname));
    foreach($lines as $l) {
        if (preg_match('/(.+)=(.*)/',$l,$match)) {
            $n = $match[1];
            $v = $match[2];
        } else {
            continue;
        }
        if (strpos($n,'_MYSQL_')!==false) {
	    list($sign,$mysql,$cname) = explode('_',$n);
                array_key_exists($sign,$ret['MYSQL']) ? 
                    ($ret['MYSQL'][$sign][$cname] = $v) 
                    : ($ret['MYSQL'][$sign] = array($cname => $v));
        } else if (strpos($n,'HIVEDB_')===0) {
            $dbname = str_replace('HIVEDB_','',$n);
            $ret['HIVEDB'][$dbname]=explode(',',$v);
        } else if ($n == 'WORK_NODE'){
            $ret[$n] = explode(',',$v);
        } else {
            $ret[$n] = $v;
        }
    }
    return $ret;
}
function gprun($cmdArr,$pnum = 2) {
    //需要本机和node之间有ssh信任关系
    //node信任本机即可
    global $gconf;
    if (!isset($GLOBALS['WORK_NODE'])) {
        //每个机器加几遍
        //效果是要控制最多同时执行几个命令
        $GLOBALS['WORK_NODE'] = $gconf['WORK_NODE'];
        shuffle($GLOBALS['WORK_NODE']);
    }
    $node =array();
    shuffle($GLOBALS['WORK_NODE']);
    for($i=0;$i<$pnum;$i++) {
        foreach ($GLOBALS['WORK_NODE'] as $ip) {
            $node []= $ip;
        }
    }
    $max = count($node);
    if ($max == 0) {
        glog("no live nodes");
        exit(1);
    }
    $outArr = array();
    glog("got $max slots");
    glog(print_r($cmdArr,true));
    $todo = count($cmdArr);
    $running = array();
    $pid = posix_getpid();
    while (!empty($cmdArr)||!empty($running) ) {
        $done = glob($gconf['WORK_PATH']."/logs/ret.$pid.*");
        foreach ($done as $f) {
            $md5 = substr($f,-32,32);
            $outArr[$md5]['retcode'] = file_get_contents($f);
            $outArr[$md5]['output'] = file_get_contents(str_replace('ret','tmpout',$f));
            unlink($f);
            unlink(str_replace('ret','tmpout',$f));
            glog(sprintf(" running %s %s/%s left",count($running),count($cmdArr),$todo));
            unset($running[$md5]);
        }

        if (count($running) >= $max || empty($cmdArr)) {
            //glog(print_r($running,true));
            //glog(print_r($cmdArr,true));
            sleep(5);
            continue;
        }

        $cmd = array_shift($cmdArr);
        $ip = array_shift($node);
        $retcode = sprintf("%s/logs/ret.%s.%s_%s",$gconf['WORK_PATH'],$pid,$ip,md5($cmd));
        $tmpout= sprintf("%s/logs/tmpout.%s.%s_%s",$gconf['WORK_PATH'],$pid,$ip,md5($cmd));
        $workpath = $gconf['WORK_PATH'];
        $sshcmd = "bash -c 'ssh -o\"StrictHostKeyChecking=no\" $ip \"cd $workpath && ";
        $sshcmd .= 'export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH && ';
        $sshcmd .=  " $cmd \" && echo $? >$retcode || echo $? 1>$retcode ' 1>$tmpout 2>&1 &";
        exec($sshcmd);
        $running[md5($cmd)] = $ip;
        $outArr[md5($cmd)] = array('cmd'=>$cmd);
        array_push($node,$ip);
        sleep(3);
    }
    return $outArr;
}
function gqdh_to32($qdh) {
    $qdh = intval($qdh);
    $map= str_split('0123456789abcdefghijklmnopqrstuv');
    $str = '';
#32768是3位32进制数的最大值，正常使用的qdh现在都在20000以下
    if($qdh==0 || $qdh > 32768) {
        return "NULL";
    } else {
        $str .= $map[($qdh & 0x7C00)>>10];
        $str .= $map[($qdh & 0x3E0)>>5];
        $str .= $map[$qdh & 0x1F];
    }
    return $str;
}
function gqdh_to10($str) {
    $str=trim($str);
    $a = str_split($str);
    if (count($a) != 3)
        return "NULL";
    $qdh = 0;
    $map= '0123456789abcdefghijklmnopqrstuv';
    $qdh += strpos($map,$a[0])<<10;
    $qdh += strpos($map,$a[1])<<5;
    $qdh += strpos($map,$a[2]);
    return $qdh;
}
function ggetMysqlHandle($str,$dbname) {
    global $gconf;
    $dsn = sprintf('%s:%s',
        $gconf['MYSQL'][$str]['HOST'],
        $gconf['MYSQL'][$str]['PORT']);
    $dbh = mysql_connect($dsn,$gconf['MYSQL'][$str]['USER'],$gconf['MYSQL'][$str]['PASS']);
    mysql_select_db($dbname);
    $initsql = "SET NAMES UTF8";
    mysql_query($initsql);
    return $dbh;
}
function getqdhByStr($str){
    $qdh = "NULL";
    $strFlag = substr($str, 10, 1);
    if($strFlag == 'w'){
        $qdh = 0;
    }else if($strFlag == 'x'){
        $str32 = substr($str, 11, 3);
	$qdh = gqdh_to10($str32);
    }else{
        $qdh = "NULL";
    }
    return $qdh;
}
function gsql_tohql ($sqlname,$p_mode = "month",$is_external = true) {
    global $gconf;
    $sqlfile = $gconf['LOG_PATH']."/meta/$sqlname.sql";
    if (!file_exists($sqlfile)) {
        return "";
    }
    if (strpos($sqlname,'dim_') === 0 || in_array($sqlname,explode(",",$gconf['NON_INC_TABLE']))) {
        $partition_by = "";
        $location = sprintf("location '%s/%s/latest'",$gconf['HDFS_LOG_PATH'], $sqlname) ;
    } else if ($p_mode == "month") {
        $partition_by = "PARTITIONED BY (key_ym int)" ;
        $location = "";
    } else  if ($p_mode == "day") {
        $partition_by = "PARTITIONED BY (key_ymd int)" ;
        $location = "";
    } else {
        $partition_by = "" ;
        $location = "";
    }
        
    $sqlcontent = file_get_contents($sqlfile);
    preg_match_all('/\s*`([\w_]+)`\s+([\w_]+)[,\s\(]/',$sqlcontent,$matches,PREG_SET_ORDER);
    $arr = array();
    $typemap = array(
            'tinyint' => 'tinyint',
            'smallint' => 'smallint',
            'int' => 'int',
            'bigint' => 'bigint',
            'decimal' => 'float',
            'float' => 'float',
            'double' => 'double',
            'char' => 'string',
            'varchar' => 'string',
            'date' => 'string',
            'datetime' => 'string',
            );
    foreach ($matches as $m) {
        $key = strtolower($m[1]);
        $type_name = strtolower($m[2]);
        $value_type = isset($typemap[$type_name]) ? $typemap[$type_name] : "";
#print_r($m);
        if (empty($value_type)) {
            return "";
        } else {
            $arr []= "  $key $value_type";
        }
    }
    $table_mode = $is_external ? "external" : "";
    $hql_create = sprintf("create $table_mode table if not exists $sqlname (\n%s\n)
            $partition_by
            row format delimited fields terminated by '\\t' 
            stored  as textfile
            $location ;",
            implode(",\n",$arr)
            );
    $hql_create .= "\n";
    return $hql_create;
    //print_r($hql_create);

}
