<?php
include_once("inc.php");
global $gconf;
if ($argc < 1 || $argc > 3) {
    echo "usage 1: getter data_name [stdate] \n";
    echo "usage 2: getter data_name stdate fields\n";
    exit(1);
} 
$data_name = "";
$logroot = $gconf['LOG_PATH'];
foreach (array($argv[1],$argv[1].".sql",$argv[1].".sh",$argv[1].".php") as $getter_script) {
    $offset= strripos($getter_script,".");
    if ($offset === false ) {
        continue;
    }
        glog("checked $getter_script $offset ".$gconf['WORK_PATH']."/day_script/$getter_script");
    if (!file_exists($gconf['WORK_PATH']."/day_script/$getter_script")) {
        continue;
    }
    $data_name = substr($getter_script,0,$offset);
    $ext = substr($getter_script,$offset+1);
    $func = "compose_incmd_$ext";
    break;
}
if (empty($data_name)) {
    glog(" data not exists = ".$argv[1]);
    exit(1);
} else {
}
if (isset($argv[2])) {
    $stdate = date('Ymd',strtotime($argv[2]));
    $stdate_str= date('Y-m-d',strtotime($argv[2]));
} else {
    $stdate = date('Ymd',time()-86400);
    $stdate_str = date('Y-m-d',time()-86400);
}

glog(" data $data_name stdate = $stdate_str func=$func");
$year = substr($stdate,0,4);
$out_name= "$logroot/$data_name/$year/{$data_name}_$stdate.gz";

if (isset($argv[3]) && preg_match('/^[\d,\-\s]+$/',$argv[3])) {
    $outcmd = "cut -f ".$argv[3];
} else {
    system("mkdir -p $logroot/$data_name/$year");
    $outcmd = "gzip -c >$out_name";
}
$incmd = $func($data_name,$stdate_str);
glog(" incmd = $incmd");
glog(" outcmd = $outcmd");


$cmd = ("$incmd|$outcmd");
$output = array();
exec($cmd,$output,$ret);
glog("$stdate_str ret=$ret cmd= $cmd output=".print_r($output,true));
