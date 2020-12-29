<?php
#运行参数 例 php loader.php goods_www_uv_day all stdate edate 
include_once("inc.php");
global $gconf;
global $mysql_code;
if ($argc < 5 || $argc > 6) {
    echo "usage 1: loader [MAIN.][stat_cache.]sql_name all nouse nouse [suffix]\n";
    echo "usage 2: loader [MAIN.][stat_cache.]sql_name day stdate edate [suffix]\n";
    echo "usage 3: loader [MAIN.][stat_cache.]sql_name append stdate edate date_field\n";
    exit(1);
} 
if (strpos($argv[1],'.') === false) {
    $mysql_code = 'MAIN';
    $loader_dest = 'stat_cache';
    $sqlname= $argv[1];
} else {
    $narr = explode('.',$argv[1]);
    if (count($narr) == 2) {
        list ( $loader_dest,$sqlname) = $narr;
        $mysql_code = 'MAIN';
    } else {
        list ($mysql_code,$loader_dest,$sqlname) = $narr;
    }
}
glog(" load into $mysql_code . $loader_dest . $sqlname");
# create table sql
$sqlfile = $gconf['LOG_PATH']."/meta/$sqlname.sql";
if (!file_exists($sqlfile)) {
    glog("sql not exists $sqlfile");
    exit(1);
} else {
    $sqlcontent = file_get_contents($sqlfile);
}

$sttime = strtotime($argv[3]);
$etime = strtotime($argv[4]);
#获取mysql句柄 funclib.php
$dbh = ggetMysqlHandle($mysql_code,$loader_dest);

$logroot = $gconf['LOG_PATH'];

//维度表和全量表加载的数据范围不一样
if (strpos($sqlname,'dim_') === 0 || in_array($sqlname,explode(",",$gconf['NON_INC_TABLE']))) {
    $tablename = $sqlname;
    if (isset($argv[5])) {
        $tablename = $tablename."_".$argv[5];
    }
    //维度表和全量表都需要全表载入和初始化
    $initsql = "DROP TABLE IF EXISTS $tablename";
    mysql_query($initsql);
    $initsql = sprintf($sqlcontent,$tablename,"");
    trim($initsql,";");
    mysql_query($initsql);

    $last_line = system("ls -tr1 $logroot/$sqlname",$retval);
    if ($retval != 0 || empty($last_line)) {
        glog("ret = $retval cmd = ls -tr1 $logroot/$sqlname");
        break;
    }
    $cat = "zcat $logroot/$sqlname/$last_line";
    do_import($cat,$loader_dest,$tablename);
} else if ($argv[2] == "append") {
    //这段是在一个表内增量载入增量表
    //载入前检查目标表对应日期
    $field = $argv[5];
    $tablename = $sqlname;
    $initsql = sprintf($sqlcontent,$tablename,"");
    trim($initsql,";");
    mysql_query($initsql);
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Ymd',$t);
        $datestr = date('Y-m-d',$t);
        $year = date('Y',$t);
        $yearmonth = date('Ym',$t);
        if ($sqlname == "stat_www_visit_log") {
            $filename = sprintf ("%s/%s/%s/%s/%s_%s_*.gz",
                    $logroot,$sqlname,$year,$yearmonth,$sqlname,$stdate);
        } else {
            $filename = sprintf ("%s/%s/%s/%s_%s.gz",
                    $logroot,$sqlname,$year,$sqlname,$stdate);
        }
        $stat = stat($filename);
        if ($stat !== false && $stat[7] > 20) {
            $cat = "zcat $filename";
        } else {
            glog("abnormal filename= $filename");
            continue;
        }
        $checksql = "select count($field) as c from $tablename where $field ='$datestr'";
        $res = mysql_query($checksql);
        $resline = mysql_fetch_assoc($res);
        if (!empty($resline['c'])) {
            continue;
        } else {
            do_import($cat,$loader_dest,$tablename);
        }
    }
} else if ($argv[2] == "all") {
    //这段是在一个表内载入增量表
    $tablename = $sqlname;
    if (isset($argv[5])) {
        $tablename = $tablename."_".$argv[5];
    }
    $initsql = "DROP TABLE IF EXISTS $tablename";
    mysql_query($initsql);
    $initsql = sprintf($sqlcontent,$tablename,"");
    trim($initsql,";");
    mysql_query($initsql);
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Ymd',$t);
        $year = date('Y',$t);
        $yearmonth = date('Ym',$t);
        if ($sqlname == "stat_www_visit_log") {
            $cat = sprintf ("zcat %s/%s/%s/%s/%s_%s_*.gz",
                    $logroot,$sqlname,$year,$yearmonth,$sqlname,$stdate);
        } else {
            $cat = sprintf ("zcat %s/%s/%s/%s_%s.gz",
                    $logroot,$sqlname,$year,$sqlname,$stdate);
        }
        do_import($cat,$loader_dest,$tablename);
    }
} else if ($argv[2] == "day") {
    //这段是按每天一个表载入增量表
    for($t=$sttime;$t<=$etime;$t+=86400) {
        $stdate = date('Ymd',$t);
        $year = date('Y',$t);
        $yearmonth = date('Ym',$t);
        $tablename = "${sqlname}_$stdate";
        $initsql = "DROP TABLE IF EXISTS $tablename";
        mysql_query($initsql,$dbh);
        $initsql = sprintf($sqlcontent,$sqlname,"_$stdate");
        trim($initsql,";");
        mysql_query($initsql);
        if ($sqlname == "stat_www_visit_log") {
            $cat = sprintf ("zcat %s/%s/%s/%s/%s_%s_*.gz",
                    $logroot,$sqlname,$year,$yearmonth,$sqlname,$stdate);
        } else {
            $cat = sprintf ("zcat %s/%s/%s/%s_%s.gz",
                    $logroot,$sqlname,$year,$sqlname,$stdate);
        }
        do_import($cat,$loader_dest,$tablename);
    }
} else {
    glog("unknown mode ".$argv[2]);
    exit(1);
}

function do_import ($cat ,$loader_dest,$tablename) {
    global $gconf;
    global $mysql_code;
        $mysql = sprintf('%s -h%s -P%s -u%s -p"%s" -D%s --local-infile -e "load data  local infile \'/dev/stdin\' into table %s CHARACTER SET utf8 fields terminated by \'\\t\' enclosed by \'NULL\' lines terminated by \'\\n\'"',
                $gconf['MYSQL_BIN'],
                $gconf['MYSQL'][$mysql_code]['HOST'],
                $gconf['MYSQL'][$mysql_code]['PORT'],
                $gconf['MYSQL'][$mysql_code]['USER'],
                $gconf['MYSQL'][$mysql_code]['PASS'],
                $loader_dest,
                $tablename
                );
        $output = array();
        exec("export LD_LIBRARY_PATH=/home/bri/lib/mysql:\$LD_LIBRARY_PATH  && $cat|$mysql 2>&1",$output,$ret);
        if ($ret >0) {
            glog("ret=$ret cmd= $cat|$mysql output=".print_r($output,true));
        } else {
            return;
        }
}
