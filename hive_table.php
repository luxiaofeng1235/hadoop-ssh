<?php
date_default_timezone_set("Asia/Chongqing");
require_once("./inc.php");
global $gconf;
if ($argc < 3 || $argc > 5) {
    echo "usage 1: hive_table sql_name month|day [db_name]\n";
    echo "usage 2: hive_table sql_name month|day db_name [renew]\n";
    exit(1);
}
$sqlname = $argv[1];
$mode = $argv[2];
$dbname = isset($argv[3]) ?  $argv[3] : "testdb";
$is_renew = isset($argv[4]) ? true : false;
$hql = gsql_tohql($sqlname,$mode,false);
        if (empty($hql)) {
            echo "failed get hql for $sqlname\n";
            exit;
        } 
$cmd = "impala-shell -i dn3.h.lashou-inc.com ";
$fd = popen($cmd,"w");
if (!is_resource($fd)) {
    echo "failed open pipe on $cmd\n";
    continue;
}
fprintf($fd,"use $dbname;\n");
if ($is_renew === true) {
    fprintf($fd,"drop table if exists $sqlname;\n");
}
        fprintf($fd,"$hql\n");
        fprintf($fd,"quit;\n");
        pclose($fd);
echo "done with create $sqlname on $dbname\n";
$dn_num = 5;
        for ($i=1;$i<=$dn_num;$i++) {
            $cmd = "impala-shell -i dn$i.h.lashou-inc.com -q 'refresh;'";
            system($cmd);
        }
echo "done with refresh on all $dn_num dn\n";
