<?php
include_once("inc.php");
function compose_incmd_sh ($name,$date_str) {
    global $gconf;
    $stdate = date('Ymd',strtotime($date_str));
    $stdate_str= date('Y-m-d',strtotime($date_str));
    return "bash ".$gconf['WORK_PATH']."/day_script/$name.sh $stdate_str";
}
function compose_incmd_sql ($name,$date_str) {
    global $gconf;
    $stdate = date('Ymd',strtotime($date_str));
    $stdate_str= date('Y-m-d',strtotime($date_str));
    $script = file_get_contents($gconf['WORK_PATH']."/day_script/$name.sql");
    $content = sprintf($script,$stdate,$stdate_str);
    $mysql_code = 'DWH';
    $mysql = sprintf("%s -h%s -P%s -u%s -p'%s' -A -r  --quick --default-character-set=utf8  --skip-column  -e '%s'",
                $gconf['MYSQL_BIN'],
                $gconf['MYSQL'][$mysql_code]['HOST'],
                $gconf['MYSQL'][$mysql_code]['PORT'],
                $gconf['MYSQL'][$mysql_code]['USER'],
                $gconf['MYSQL'][$mysql_code]['PASS'],
                $content
                );
    return $mysql;
}
function compose_incmd_php ($name,$date_str) {
    global $gconf;
    $stdate = date('Ymd',strtotime($date_str));
    $stdate_str= date('Y-m-d',strtotime($date_str));
    return $gconf['PHP_BIN']." ".$gconf['WORK_PATH']."/day_script/$name.php $stdate_str";
}
