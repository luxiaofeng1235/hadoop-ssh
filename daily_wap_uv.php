<?php
include_once("inc.php");
global $gconf;
$logroot = $gconf['LOG_PATH'];
$stdate = date('Ymd',(isset($argv[1]) ? strtotime($argv[1]):(time()-86400)));
$year = substr($stdate,0,4);

$logname= "$logroot/visit_wap_log/$year/visit_wap_log_$stdate.gz";
$outprefix = "$logroot/daily_wap_uv/$year/";
$chash = array();
if (!file_exists($logname) ) {
    glog(" app log not exists");
    exit(1);
}
$infd = popen("zcat $logname","r");
if (!is_resource($infd)) {
    glog("open $logname failed");
    exit(1);
}

/*
$cfd = popen("zcat $bad_key_name","r");
if (!is_resource($cfd)) {
    glog("open $bad_key_name failed");
    exit(1);
}

*/
system("mkdir -p $outprefix 2>/dev/null");
/*
while ($key = stream_get_line($cfd,1048576,"\n")) {
    $chash[md5($key,true)] = 1;
}
pclose($cfd);
glog(count(array_keys($chash))." keys loaded");
glog(gmemusage());
*/

$uv = array();
$au = array();
$outfd = popen("gzip -c >$outprefix/daily_wap_uv_$stdate.gz","w");
if (!is_resource($outfd)) {
    glog("open pipe $outprefix/daily_wap_uv_$stdate.gz failed");
    exit;
}
while ($line= stream_get_line($infd,1048576,"\n")) {
    $a = explode("\t",$line);
    // a6 is user_id
        // a4 is client_key
    if (array_key_exists(md5($a[3],true),$chash)) {
        continue;
    } else {
        $chash[md5($a[3],true)] = null;
        fprintf($outfd,"%s\t%s\t%s\n",date('Y-m-d',strtotime($a[11])),$a[11],$a[3]);
    }
}

glog(count(array_keys($chash))." uv");
//glog(count(array_keys($au))." au");
glog(gmemusage());
pclose($outfd);

