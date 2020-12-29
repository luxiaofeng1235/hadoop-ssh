<?php
date_default_timezone_set("Asia/Chongqing");
require_once("./inc.php");
global $gconf;
if ($argc < 3 || $argc > 4) {
    echo "usage 1: hive_external sql_name[,sql_name] month|day \nrefresh table meta in all defined DB in main.conf\n";
    echo "usage 2: hive_external sql_name[,sql_name] month|day DB_ALIAS[,DB_ALIAS]\nrefresh table meta in DB_ALIAS only\n";
    exit(1);
}
$tablearr = explode(",",$argv[1]);
$mode = $argv[2];
$dbarr = isset($argv[3]) ?  explode(",",$argv[3]) : array_keys($gconf['HIVEDB']);
echo "prepare to refresh ",implode(',',$tablearr)," on ",implode(',',$dbarr),"\n";
echo gmemusage(),"\n";
foreach ($dbarr as $dbname) {
    if (!isset($gconf['HIVEDB'][$dbname])) {
        continue;
    }
    foreach ($tablearr as $sqlname) {
        if (!in_array($sqlname,$gconf['HIVEDB'][$dbname])) {
            continue;
        }
        $hql = gsql_tohql($sqlname,$mode);
        if (empty($hql)) {
            echo "failed get hql for $sqlname\n";
            continue;
        } 
        $cmd = "impala-shell -i dn3.h.lashou-inc.com ";
        $fd = popen($cmd,"w");
        if (!is_resource($fd)) {
            echo "failed open pipe on $cmd\n";
            continue;
        }
        fprintf($fd,"use $dbname;\n");
        fprintf($fd,"drop table if exists $sqlname;\n");
        fprintf($fd,"$hql\n");
        fprintf($fd,"quit;\n");
        pclose($fd);
        echo "done with create $sqlname on $dbname\n";
        for ($i=1;$i<=5;$i++) {
            $cmd = "impala-shell -i dn$i.h.lashou-inc.com -q 'refresh;'";
            system($cmd);
        }
    }
}
