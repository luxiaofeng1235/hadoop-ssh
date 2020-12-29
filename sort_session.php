<?php
include_once("inc.php");
global $gconf;
$logroot = $gconf['LOG_PATH'];
$stdate = date('Ymd',(isset($argv[1]) ? strtotime($argv[1]):(time()-86400)));
$year = substr($stdate,0,4);
$yearmonth = substr($stdate,0,6);
$logname= "$logroot/go_visit_log/$year/go_visit_log_$stdate.gz";
$bad_key_name= "$logroot/bad_client_key/$year/bad_client_key_$stdate.gz";
$outprefix = "$logroot/stat_www_visit_log/$year/$yearmonth/";
$yesterdayPrefix= sprintf("$logroot/stat_www_visit_log/%s/%s/",
        date('Y',strtotime($stdate)-86400),
        date('Ym',strtotime($stdate)-86400)
        );
$chash = array();
$infd = popen("zcat $logname","r");
if (!file_exists($logname) || !is_resource($infd)) {
    glog("open $logname failed");
    exit(1);
}

$cfd = popen("zcat $bad_key_name","r");
if (!file_exists($bad_key_name) || !is_resource($cfd)) {
    glog("open $bad_key_name failed");
    exit(1);
}
while ($key = stream_get_line($cfd,1048576,"\n")) {
    $chash[md5($key,true)] = 1;
}
pclose($cfd);
glog(count(array_keys($chash))." keys loaded");
glog(gmemusage());

$tmpdir =("./tmp/".php_uname('n'));
//$tmpdir = "/dev/shm/".posix_getpid();
system("mkdir -p $outprefix $tmpdir 2>/dev/null");
$charMap = str_split('0123456789abcdefghijklmnopqrstuv');
$pipes = array();
$phpbin = $gconf['PHP_BIN'];
$logcolumn="id,visit_time,ip,city_id,client_key,session_id,user_id,source,ref,url,browser,pos";

for($i=0;$i<count($charMap);$i++) {
    //6 is session_id
    //1 is id
    $yesterday = date('Ymd',strtotime($stdate)-86400);
    $cmd = "sort -t'\t' -s -T$tmpdir/ -k6,6 -k1,1n ";
    //$cmd .= "$phpbin one_block.php '$logcolumn' channel_sitemap url_type_php $yesterdayPrefix/stat_www_visit_log_${yesterday}_${charMap[$i]}.gz|";
    //$cmd .= "gzip -c >${outprefix}/stat_www_visit_log_${stdate}_${charMap[$i]}.gz";
    $cmd .= " >${outprefix}/pre_sort_go_visit_log_${stdate}_${charMap[$i]}";
    $fh = popen($cmd,"w");
    if (is_resource($fh)) {
        $pipes[$charMap[$i]] = $fh;
        glog("opened  pipe $i = $cmd");
    } else {
        glog("open pipe $i $cmd failed");
        exit;
    }
}
glog(gmemusage());
$goodcount = 0;
$badcount = 0;
while ($line= stream_get_line($infd,1048576,"\n")) {
    $a = explode("\t",$line);
    if (array_key_exists(md5($a[4],true),$chash)) {
        $badcount++;
        continue;
    }
    $goodcount++;
    // 5 is session_id
    $headchar = substr($a[5],1,1);
    if (in_array($headchar,$charMap)) {
        fprintf($pipes[$headchar],"%s\n",implode("\t",$a));
    } else  {
        $pos = ord($headchar) % count($charMap);
        fprintf($pipes[$charMap[$pos]],"%s\n",implode("\t",$a));
    }
    /*
    glog(print_r($charMap,true));
    glog(print_r($a,true));
    if ($goodcount >=200000)
        break;
    exit;
    */

}
glog("good line $goodcount bad line $badcount");
pclose($infd);
foreach ($pipes as $p) {
    pclose($p);
}


