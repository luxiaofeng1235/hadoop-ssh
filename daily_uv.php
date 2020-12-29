<?php
include_once("inc.php");
global $gconf;
$logroot = $gconf['LOG_PATH'];
$stdate = date('Ymd',(isset($argv[1]) ? strtotime($argv[1]):(time()-86400)));
$year = substr($stdate,0,4);

$logname= "$logroot/go_visit_log/$year/go_visit_log_$stdate.gz";
$bad_key_name= "$logroot/bad_client_key/$year/bad_client_key_$stdate.gz";
$outprefix = "$logroot/daily_uv/$year/";
$auprefix = "$logroot/active_user/$year/";
$chash = array();
if (!file_exists($logname) || !file_exists($bad_key_name)) {
    glog(" log or bad_key not exists");
    exit(1);
}
$infd = popen("zcat $logname","r");
if (!is_resource($infd)) {
    glog("open $logname failed");
    exit(1);
}

$cfd = popen("zcat $bad_key_name","r");
if (!is_resource($cfd)) {
    glog("open $bad_key_name failed");
    exit(1);
}


system("mkdir -p $outprefix $auprefix 2>/dev/null");
while ($key = stream_get_line($cfd,1048576,"\n")) {
    $chash[md5($key,true)] = 1;
}
pclose($cfd);
glog(count(array_keys($chash))." keys loaded");
glog(gmemusage());


$uv = array();
$au = array();
while ($line= stream_get_line($infd,1048576,"\n")) {
    $a = explode("\t",$line);
    // a6 is user_id
    if ($a[6] > 0 ) {
        $au[intval($a[6])] = null;
    }
        // a4 is client_key
    if (array_key_exists(md5($a[4],true),$chash)) {
        continue;
    } else {
        $uv[$a[4]] = null;
    }
}

glog(count(array_keys($uv))." uv");
glog(count(array_keys($au))." au");
glog(gmemusage());
$outfd = popen("gzip -c >$outprefix/daily_uv_$stdate.gz","w");
if (!is_resource($outfd)) {
    glog("open pipe $outprefix/daily_uv_$stdate.gz failed");
    exit;
}
foreach ($uv as $client_key => $nouse) {
    fprintf($outfd,"%s\n",$client_key);
}
pclose($outfd);

$outfd2 = popen("gzip -c >$auprefix/active_user_$stdate.gz","w");
if (!is_resource($outfd2)) {
    glog("open pipe $auprefix/active_user_$stdate.gz failed");
    exit;
}
foreach ($au as $user_id => $nouse) {
    fprintf($outfd2,"%s\n",$user_id);
}
pclose($outfd2);
