<?php
include_once("inc.php");
if ($argc != 5) {
    echo "usage: one_block 'id,visit_time....' channel_sitemap url_type_php yesterday_session\n";
    exit(1);
} 
$logcolumn = preg_split('/[,\s]+/',$argv[1]);
if (($logcolumn)<=1 ) {
    glog($logcolumn);
    exit(1);
}
// init Meta From Mysql
$dbh = ggetMysqlHandle('META','bas_entity');
//channel_sitemap loading
$siteMap = array();
$sitePatternMap = array("/[^.]*\.lashou\.com/"=>null);
$sql = "select pattern,source from ".$argv[2];
$res = mysql_query($sql);
if ($res === false) {
    glog("failed loading sitemap from ".$argv[2]);
    exit(1);
}
while (false != ($row = mysql_fetch_assoc($res))) { 
    if (preg_match('/\*/',$row['pattern'])) {
        $pstr = str_replace('.','\.',$row['pattern']);
        $pstr = str_replace('*','([^.]*)',$pstr);
        $pstr = "/".strtolower($pstr)."/";
        $sitePatternMap[$pstr] = $row['source'];
    } else {
        $siteMap[$row['pattern']] = $row['source'];
    }
}

//url_type_php loading
$url_types = array();
$sql_type="select id, cond,text1,sample,negative,text2 from  ".$argv[3]." order by id desc";
$res = mysql_query($sql_type);
if ($res === false) {
    glog("failed loading url type pattern from ".$argv[3]);
    exit(1);
}
while (false != ($r = mysql_fetch_assoc($res))) { 
		if (preg_match('/preg_match/i',$r['cond'])) {
			$url_types[$r['id']] = sprintf("if (%s) return %s;",$r['cond'],$r['id']);				
		}else{
			$url_types[$r['id']] = sprintf("if (preg_match('%s',\$u)) return %s;",$r['cond'],$r['id']);
		}
        $testfunc = create_function('$u',$url_types[$r['id']] );
        if ($r['id'] == $testfunc($r['sample'])) {
            1;
        } else {
            glog(sprintf(" ERROR cond=%s id=%s sample=%s code=%s\n",$r['cond'],$r['id'],$r['sample'],$url_types[$r['id']]));
            unset($url_types[$r['id']]);
        }
        if (empty($r['negative']) || $r['id'] != $testfunc($r['negative'])){
            1;
        } else {
            glog(sprintf(" ERROR cond=%s id=%s negative=%s code=%s\n",$r['cond'],$r['id'],$r['negative'],$url_types[$r['id']]));
            unset($url_types[$r['id']]);
        }
}
array_push($url_types,sprintf(" else return 1;"));
$typefunc = create_function('$u',implode("\n",$url_types));
$outcolumn = preg_split('/[,\s]+/',
        "id,visit_time,ip,city_id,client_key,session_id,user_id,source,ref,url,
        domain,keyword_id,union_pid,qdh,ref_type,url_type,ref_gid,url_gid,
        ref_event,url_event,endchar,page,pos,ref_id");

// load session source from yesterday
// 前1天session的最后一行日志的source值进入sessionHash初始化
// 每次计算完一个新session的source来更新
$sessionHash = array();
$sfd = popen("zcat ".$argv[4],"r");
if (!is_resource($sfd)) {
    glog("fail to init yesterday session, ignored");
} else {
    while ($line= stream_get_line($sfd,1048576,"\n")) {
        $l = explode("\t",$line);
        if (count($l) != count($outcolumn)) {
            glog("Input Error");
            continue;
        }// 5 = outcolumn里的session_id
        $sessionHash[md5($l[5],true)] = $l[7];
    }
}
// init end
$urlHash = array();
$eventHash = array();
$doingSession = "";
$sessDedup = array("",0,0,0);
//曾经有某人在主站默认模板index_screen.html里调整日志调用的位置时
//在前面加了一句visit_log却没有去掉后面的
//导致在近2个月的时间里存在日志写两遍的bug
//下面两个时间点是svn里记录的问题代码引入和修正的时间点
$dupBugStart = strtotime("2012-07-12 10:51:32");
$dupBugEnd = strtotime("2012-09-06 17:42:09");
#while ($line= stream_get_line(STDIN,1048576,"\n"))
{
$line = '4322897	2013-11-13 14:55:14	180.153.234.37	2421	1384325713w3efd7e860f900c4c3a6a8	92b3509i7cw59rcv58c9kat3c4	0	310001	http://www.0731yihao.com/	http://www.lashou.com/?qdh=310001&fc360={keyword_id}.a{creative}.u2379155.pb	www.0731yihao.com	NULL	NULL	0	1	60	NULL	NULL	NULL	NULL	8	NULL	NULL	NULL';
 #  $l = preg_split("/\t/",$line,-1,PREG_SPLIT_OFFSET_CAPTURE);
$l = explode("\t",$line);
var_dump($l);   
if (count($l) != count($logcolumn)) {
	   glog("Input Error");
       # continue;
    }
    foreach($logcolumn as $idx => $name) {
        $iname = "I$name";
        $oname = "O$name";
        $$iname = $l[$idx];
        $$oname = $l[$idx];
    }
    if ($doingSession != $Isession_id) {
        $urlHash = array();
        $doingSession = $Isession_id;
        $eventHash = array();
    }
    $MD5session_id = md5($Isession_id,true);
    $MD5ref = md5($Iref,true);
    $MD5url = md5($Iurl,true);
    $sessFlag = substr($Isession_id,10,1);

    //处理重复日志bug
    $vtime = strtotime($Ivisit_time);
    if ($vtime > $dupBugStart && $vtime < $dupBugEnd && 
            $Isource == 0 &&
            $Ivisit_time == $sessDedup[0] &&
            $MD5ref == $sessDedup[1] &&
            $MD5url == $sessDedup[2] &&
            $MD5session_id == $sessDedup[3] 
            ) {
        continue;
    }
    $sessDedup = array($Ivisit_time,$MD5ref,$MD5url,$MD5session_id);
    //有标记的是埋了qdh参数的
    $Oqdh = "NULL";
    if ($sessFlag == 'w') {
        $Oqdh = 0;
    } else if ($sessFlag == 'x') {
        $sess32 = substr($Isession_id,11,3);
        $Oqdh= gqdh_to10($sess32);
    } else {
        $Oqdh = "NULL";
    }

    if (preg_match('/http:\/\/([^\/]+).*/',$Iref,$m)) {
        $domain = substr($m[1],-64,64);
        $d = explode('.',$domain);
        $lend = count($d);
        if ($lend <=3) {
            $Odomain = $domain;
        } elseif ($lend > 3 && strlen($d[$lend-1])<=2) {
            $Odomain = implode('.',array($d[$lend-4],$d[$lend-3],$d[$lend-2],$d[$lend-1]));
        } elseif ($lend > 3 && strlen($d[$lend-1])<=3) {
            $Odomain = implode('.',array($d[$lend-3],$d[$lend-2],$d[$lend-1]));
        } else {
            $Odomain = $domain;
        }
        $Odomain = strtolower($Odomain);

    } else {
        $Odomain = "NULL";
    }

    if (preg_match('/.*yiqifa\.com.*&e=(\d+)&.*/',$Iref,$m) || 
            preg_match('/.*fc=k(\d+)\./',$Iurl,$m) ||
            preg_match('/.*fc=k(\d+)\./',$Iref,$m)) {
        $Okeyword_id = $m[1];
    } else if (array_key_exists($MD5ref,$urlHash)) {
        $Okeyword_id = $urlHash[$MD5ref][1];
    } else {
        $Okeyword_id = "NULL";
    }

    if (preg_match('/.*union_pid=(\d+).*/',$Iurl,$m) ||
            preg_match('/.*union_pid=(\d+).*/',$Iref,$m)
            ) {
        $Ounion_pid= $m[1];
    } else if (array_key_exists($MD5ref,$urlHash)) {
        $Ounion_pid = $urlHash[$MD5ref][2];
    } else {
        $Ounion_pid= "NULL";
    }

    $Oref_type = $typefunc($Iref);
    $Ourl_type = $typefunc($Iurl);

    if (preg_match('/\/(deal|detail)\/(\d+)\.html/',$Iref,$m)) {
        $Oref_gid= $m[2];
    } else {
        $Oref_gid= "NULL";
    }
    if (preg_match('/\/(deal|detail)\/(\d+)\.html/',$Iurl,$m)) {
        $Ourl_gid= $m[2];
    } else {
        $Ourl_gid= "NULL";
    }

    if (preg_match('/\/event\/([-_\w]+)\.html/',$Iref,$m)) {
        $Oref_event= $m[1];
    } else {
        $Oref_event= "NULL";
    }
    if (preg_match('/\/event\/([-_\w]+)\.html/',$Iurl,$m)) {
        $Ourl_event= $m[1];
    } else {
        $Ourl_event= "NULL";
    }

    $Oendchar = substr($Iclient_key,-1,1);

    if (preg_match('/lashou.com.*\/page(\d+)/',$Iref,$m)) {
        $Opage = $m[1];
    } else {
        $Opage = "NULL";
    }

//计算source字段的新取值
//取值的做法是预设多个不同规则的qdh值计算规则
//取优先级最高并且能计算出的站外qdh值做为最终选值
//规则和优先级顺序如下
// qdhByQdh  session_id里的预埋值
// qdhBySource 根据原source字段换算的值    
// qdhByUrl 根据本日志的url字段里的参数换算的值
// qdhByRef 根据日志的ref字段提取出的domain换算的值
// qdhByHash 根据本日志在本session内的点击顺序换算的值
// qdhBySession 本session_id在前一天日志里的识别结果
// qdhUnknown|qdhSelf  其它规则都算不出来时的默认值,能判断是站外流量的为Unknown, 否则为Self
    // begin qdhByQdh
 if ($Oqdh == "NULL" || $Oqdh == 0) {
        $qdhByQdh = null; 
    } else {
        $qdhByQdh = $Oqdh;
    }
    //begin qdhBySource
    $qdhCompatible = 10000;//主站有对部分渠道号做+10000处理
    $qdhLimit = 32768 ; //预埋渠道号的取值限制
    $qdhEDM = 1; //历史上有几个超小的渠道号，只有1值得保留
    $qdhSelf = 0;
    $qdhUnknown = 811113;
    if ($Isource > $qdhCompatible && $Isource < ($qdhCompatible + $qdhCompatible) ) {
        $qdhBySource = $Isource - $qdhCompatible;
    } else if ($Isource > $qdhLimit || $Isource == $qdhSelf) {
        $qdhBySource = null;
    } else {
        $qdhBySource = $Isource;
    }
    if ($qdhBySource < 10 && $qdhBySource != $qdhEDM) {
        $qdhBySource = null;
    }
    //存在一批20000到32768之间的source值，是无线部门随便用的,
    // 存在一批数量不定的source值, 是BI的edm邮件里随便用的
    //用pofist=作为特征值, 重置source值为1

    //begin qdhByUrl
    if (preg_match('/pofist=/',$Iurl ) && preg_match('/qdh=(\d+)/',$Iurl,$m)) {
        $qdhByQdh= 1;
        $qdhByUrl = $m[1];
    } else if (preg_match('/qdh=(\d+)/',$Iurl,$m)) {
        $qdhByUrl = $m[1];
    } else {
        $qdhByUrl = null;
    }
    //begin qdhByRef
    $qdhByRef = null;
    if ($Iref == "NULL" ||empty($Iref)) {
        $qdhByRef = null;
    } else if (array_key_exists($Odomain,$siteMap)){
        $qdhByRef = $siteMap[$Odomain];
    } else {
        $qdhByRef = null;
        foreach ($sitePatternMap as $p => $qdh) {
            if (preg_match($p,$Iref)) {
                $qdhByRef = $qdh;
                break;
            }
        }
    }

    //begin qdhByHash
    if (array_key_exists($MD5ref,$urlHash)) {
        $qdhByHash = $urlHash[$MD5ref][0];
    } else {
        $qdhByHash = null;
    }
    //begin qdhBySession
    if (array_key_exists($MD5session_id,$sessionHash)) {
        $qdhBySession = $sessionHash[$MD5session_id];
        //回调EDM session的参数
        if ($qdhBySession == 1) {
            $qdhByQdh = 1;
        }
    } else {
        $qdhBySession = null;
    }

    $Osource = null;
    foreach (array($qdhByQdh,$qdhBySource,$qdhByUrl,$qdhByRef,$qdhByHash,$qdhBySession) as $qdh) {
        if (!is_null($qdh)) {
            $Osource = $qdh;
            break;
        }
    }

    if (is_null($Osource)) {
        if ($Iref == "NULL" || empty($Iref)|| preg_match('/lashou\.com$/',$Odomain)) {
            $Osource = $qdhSelf;
        } else {
            $Osource = $qdhUnknown;
        }
    }

//source字段的取值计算结束

    $Opos = $Ipos;
    //外部网站来的并且带s参数
    //这种特征是api里标的排位数
    if (strpos($Odomain,'lashou.com') === false && preg_match('/[\?|&](s|goodPosition)=(\d+)/',$Iurl,$m)) {
        $Opage = 1;
        $Opos = $m[2];
    }
    //如果是专场点击产生的单品页, 按专场页的page 和pos 赋值给单品页
    if (array_key_exists($MD5ref,$eventHash) && $Ourl_gid != "NULL") {
        $Opage = $eventHash[$MD5ref][0];
        $Opos = $eventHash[$MD5ref][1];
    }


    if (array_key_exists($MD5ref,$urlHash)) {
        $Oref_id = $urlHash[$MD5ref][3];
    } else {
        $Oref_id = "NULL";
    }

    if (intval($Opos)>0 && $Opage == "NULL") {
        $Opage = "1";
    }

    //visit_time存下来，也许将来能用上算stay_time
    $urlHash[$MD5url] = array($Osource,$Okeyword_id,$Ounion_pid,$Iid);
    $sessionHash[$MD5session_id] = $Osource;
    if ($Ourl_event != "NULL") {
        $eventHash[$MD5url] = array($Opage,$Opos);
    }

    $oArr = array();
    foreach ($outcolumn as $name) {
        $oname = "O$name";
        $oArr []= $$oname;
    }
var_dump($oArr);
    fprintf(STDOUT,"%s\n",implode("\t",$oArr));
}

