<?php

ini_set('memory_limit','4000M');
$config =  include("phpdbconf.php");
extract($config);

if (isset($argv[1])) {
    $basedate = date('Y-m-d',strtotime($argv[1]));
} else {
    $basedate = date('Y-m-d',time()-86400);
}
$basemonth = date('Ymd',strtotime($basedate));

$deal_num = 0;//单品数字标示
$event_num = 0;//专场数字标示
$deal_nums = array();
$event_nums = array();

// 写入数据表
$table_url_type= 'bas_entity.url_type_php';
$table_stat_bounce= "stat_bounce_$basemonth";
$table_stat_goods_bounce= "stat_deal_bounce_$basemonth";
$table_stat_event_bounce= "stat_event_bounce_$basemonth";
$go_log_name= 'datacenter.stat_go_visit_log'.date('Ymd',strtotime($basedate));
echo "begin on ".date('Y-m-d H:i:s')."\n";
try {
	// 建立连接
	$dbhDC= new PDO($dsn1,$user1,$pass1,array(PDO::ATTR_PERSISTENT => true));
	$dbhBA= new PDO($dsn2,$user2,$pass2,array(PDO::ATTR_PERSISTENT => true));
	
        $tableSql = "
                DROP TABLE IF EXISTS `$table_stat_bounce`;
		CREATE TABLE `$table_stat_bounce` (
			`id` int(11) NOT NULL  AUTO_INCREMENT,
			`stdate` date NOT NULL,
			`ref_type` int(11) NOT NULL,
			`url_type` int(11) NOT NULL,
			`city_id` int(11) NOT NULL,
			`endchar` char(1) NOT NULL,
			`pv` int(11) DEFAULT NULL,
			PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
                DROP TABLE IF EXISTS `$table_stat_goods_bounce`;
		CREATE TABLE `$table_stat_goods_bounce` (
			`id` int(11) NOT NULL  AUTO_INCREMENT,
			`stdate` date NOT NULL,
			`ref_type` int(11) NOT NULL,
			`url_type` int(11) NOT NULL,
			`city_id` int(11) NOT NULL,
			`endchar` char(1) NOT NULL,
			`ref_id` int(11) DEFAULT NULL,
			`url_id` int(11) DEFAULT NULL,
                        `ref_param` char(10) DEFAULT NULL,
                        `url_param` char(10) DEFAULT NULL,
                        `pv` int(11) DEFAULT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;		
                DROP TABLE IF EXISTS `$table_stat_event_bounce`;
                CREATE TABLE `$table_stat_event_bounce` (
			`id` int(11) NOT NULL  AUTO_INCREMENT,
			`stdate` date NOT NULL,
			`ref_type` int(11) NOT NULL,
			`url_type` int(11) NOT NULL,
			`city_id` int(11) NOT NULL,
			`endchar` char(1) NOT NULL,
			`ref_event` char(82) DEFAULT NULL,
			`url_event` char(82) DEFAULT NULL,
                        `pv` int(11) DEFAULT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;		
	";
	$dbhBA->exec($tableSql);
	
        $sql = "set names utf8";
        $dbhBA->exec($sql);

        $url_types = array();
	$sql_type="select id, cond,text1,sample,negative,text2 from  $table_url_type order by id desc";
	echo date('Y-m-d H:i:s')," $sql_type\n";
	$res = $dbhBA->query($sql_type);
	while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	    //有的链接形式用单个正则表达式不好定义, 需要用多个正则条件组合
	    //数据库保存的是组合后的PHP条件原型, 实际查询时要拼装成真实有效的语句
		if (preg_match('/preg_match/i',$r['cond'])) {
			$url_types[$r['id']] = sprintf("if (%s) return %s;",$r['cond'],$r['id']);				
		}else{
			$url_types[$r['id']] = sprintf("if (preg_match('%s',\$u)) return %s;",$r['cond'],$r['id']);
		}
		if("/^http:\/\/[^\.]*\.lashou\.com\/deal\/[0-9]+\.html(\?.*)?$/" == $r['cond']){
			$deal_num = $r['id'];
		}
		if("/^http:\/\/[^\.]*\.lashou\.com\/event\/(.*)?$/" == $r['cond']){
			$event_num = $r['id'];
                }
                $testfunc = create_function('$u',$url_types[$r['id']] );
                if ($r['id'] == $testfunc($r['sample'])) {
                    1;
                } else {
                    echo date('Y-m-d H:i:s'), sprintf(" ERROR cond=%s id=%s sample=%s code=%s\n",$r['cond'],$r['id'],$r['sample'],$url_types[$r['id']]);
                    unset($url_types[$r['id']]);
                }
                if (empty($r['negative']) || $r['id'] != $testfunc($r['negative'])){
                    1;
                } else {
                    echo date('Y-m-d H:i:s'), sprintf(" ERROR cond=%s id=%s negative=%s code=%s\n",$r['cond'],$r['id'],$r['negative'],$url_types[$r['id']]);
                    unset($url_types[$r['id']]);
                }
                if($r['text2'] == '单品'){
                    $deal_nums[$r['id']] = 1;
                }
                if($r['text2'] == '广告和专场'){
                    $event_nums[$r['id']] = 1;
                }
	}
	$url_types[max(array_keys($url_types))+1] = sprintf(" else return 1;");
        
        //按uv特征过滤可能的爬虫ip
        $ipSql = "
		select t.ip as ip , count(distinct t.client_key) as uv from $go_log_name t
		left join ( select distinct ip from $go_log_name where trade_no != 0 ) f on t.ip = f.ip
		where f.ip is null group by t.ip having uv > 50
	";
	echo date('Y-m-d H:i:s')," $ipSql\n";
	$crawler = array();
	$res= $dbhDC->query($ipSql);
	while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
	    $crawler [$r['ip']] = 1;
        }
	
	$useMem = memory_get_usage(true)/1024/1024;
	$maxMem = $useMem;
	echo 'memcheck......1......'.$useMem."M\n";
	echo date('Y-m-d H:i:s')," \n";	

	$newfunc = create_function('$u',implode("\n",$url_types));
	
	$sql_stat = "select right(client_key,1) as endchar,city_id,url,ref,ip from $go_log_name";
	echo date('Y-m-d H:i:s')," $sql_stat\n";
	$stat = array();
	$stat_deal = array();
	$stat_event = array();
	$offset = 0;
        $limit = 1000000;
	$pdo = $dbhDC->prepare($sql_stat." limit $offset,$limit");
	$pdo->execute();
	$rowCount = $pdo->rowCount();
	while ($rowCount > 0) {
		while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
			if(isset($crawler[$row['ip']])){
				continue;
			}
			
                        $ref_id = "NULL";
                        $url_id = "NULL";
                        $ref_param = "NULL";
                        $url_param = "NULL";
                        $ref_event = "NULL";
                        $url_event = "NULL";
			
			$ref_num = $newfunc($row['ref']);
			$url_num = $newfunc($row['url']);
			
                        if(isset($deal_nums[$ref_num]) || isset($deal_nums[$url_num])){
                            $ref_id = (preg_match("/deal\/([0-9]+)\.html/",$row['ref'],$rs))?$rs[1]:"NULL";
                            $url_id = (preg_match("/deal\/([0-9]+)\.html/",$row['url'],$rs))?$rs[1]:"NULL";
                            $ref_param = (preg_match("/deal\/[0-9]+\.html\?([^\=]+)\=/",$row['ref'],$rs))?strtolower($rs[1]):"NULL";
                            $url_param = (preg_match("/deal\/[0-9]+\.html\?([^\=]+)\=/",$row['url'],$rs))?strtolower($rs[1]):"NULL";
                            if($ref_id!="NULL" || $url_id!="NULL"){
			        $deal_key = sprintf("%s,%s,%s,%s,%s,%s,%s,%s",$ref_num,$url_num,$row['city_id'],strtolower($row['endchar']),
                                            $ref_id,$url_id,$ref_param,$url_param);
			        $stat_deal[$deal_key] = isset($stat_deal[$deal_key])?$stat_deal[$deal_key]+1:1;
                            }
                        }

                        if(isset($event_nums[$ref_num])||isset($event_nums[$url_num])){
                            $ref_event = (preg_match("/event\/([^\.]+)\.html/",$row['ref'],$rs))?strtolower($rs[1]):"NULL";
                            $url_event = (preg_match("/event\/([^\.]+)\.html/",$row['url'],$rs))?strtolower($rs[1]):"NULL";
                            if($ref_event!="NULL"||$url_event!="NULL"){
                                $event_key = sprintf("%s,%s,%s,%s,%s,%s",$ref_num,$url_num,$row['city_id'],strtolower($row['endchar']),
                                            $ref_event,$url_event);
			        $stat_event[$event_key] = isset($stat_event[$event_key])?$stat_event[$event_key]+1:1;				
                            }
                        }
			
			$key = sprintf("%s,%s,%s,%s",$ref_num,$url_num,$row['city_id'],strtolower($row['endchar']));
			$stat[$key] = isset($stat[$key])?$stat[$key]+1:1;
                }
		$offset += $limit;
                echo date('Y-m-d H:i:s')," offset $offset done\n";
		$pdo = $dbhDC->prepare($sql_stat." limit $offset,$limit");
		$pdo->execute();
                $rowCount = $pdo->rowCount();		
	}
	$useMem = memory_get_usage(true)/1024/1024;
	$maxMem = ($useMem > $maxMem) ? $useMem : $maxMem;
	echo 'memcheck......2......'.$useMem."M\n";
	echo date('Y-m-d H:i:s')," \n";	

	$sql = " select f.ip , right(f.client_key,1) as endchar , f.url as ref , f.city_id 
    from $go_log_name f
    inner join 
    ( select max(id) as id from $go_log_name group by session_id ) i
    on f.id = i.id ";
	$res= $dbhDC->query($sql);
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if(isset($crawler[$row['ip']])){
			continue;
		}

                $ref_id = "NULL";
                $ref_param = "NULL";
                $ref_event = "NULL";
		
		$ref_num = $newfunc($row['ref']);
		
                if(isset($event_nums[$ref_num])){
                    $ref_event = (preg_match("/event\/([^\.]+)\.html/",$row['ref'],$rs))?strtolower($rs[1]):"NULL";
                    if($ref_event!="NULL"){
                        $event_key = sprintf("%s,%s,%s,%s,%s,%s",$ref_num,1,$row['city_id'],strtolower($row['endchar']),$ref_event,"NULL");
		        $stat_event[$event_key] = isset($stat_event[$event_key])?$stat_event[$event_key]+1:1;
                    }
                }

                if(isset($deal_nums[$ref_num])){
                    $ref_id = (preg_match("/deal\/([0-9]+)\.html/",$row['ref'],$rs))?$rs[1]:"NULL";
                    $ref_param = (preg_match("/deal\/[0-9]+\.html\?([^\=]+)\=/",$row['ref'],$rs))?strtolower($rs[1]):"NULL";
                    if($ref_id!="NULL"){
                        $deal_key = sprintf("%s,%s,%s,%s,%s,%s,%s,%s",$ref_num,1,$row['city_id'],strtolower($row['endchar']),
                                    $ref_id,"NULL",$ref_param,"NULL");
		        $stat_deal[$deal_key] = isset($stat_deal[$deal_key])?$stat_deal[$deal_key]+1:1;
                    }
                }
		
		$key = sprintf("%s,%s,%s,%s",$ref_num,1,$row['city_id'],strtolower($row['endchar']));
		$stat[$key] = isset($stat[$key])?$stat[$key]+1:1;		
	}

	$useMem = memory_get_usage(true)/1024/1024;
	$maxMem = ($useMem > $maxMem) ? $useMem : $maxMem;
	echo 'memcheck......3......'.$useMem."M\n";
	echo date('Y-m-d H:i:s')," \n";	
        
        //主站数据插入start
	$values = array();
	foreach ($stat as $key => $pv) {
	    $stdate = $basedate;
	    list($ref_type,$url_type,$city_id,$endchar) = explode(",",$key);
	    $line = sprintf("('%s','%s','%s','%s','%s','%s')",
	        $stdate,
	        $ref_type,
	        $url_type,
	        $city_id,
	        $endchar,
	        $pv
	    );
	    if($ref_type == '1' && $url_type == '1'){
	    	continue;
	    }
	    $values[]=$line;
	}
	
	$sql = "delete from $table_stat_bounce where stdate='".$stdate."'";
	$res = $dbhBA->exec($sql);
	
	$sqlHead = "insert into $table_stat_bounce (stdate,ref_type,url_type,city_id,endchar,pv) values ";
	$arr = array_chunk($values,10000);
	foreach ($arr as $v) {
	    $sql = $sqlHead . implode(",",$v); 
	    $res = $dbhBA->exec($sql);
	}
        //主站数据插入end
	echo date('Y-m-d H:i:s')," \n";	
	//单品数据插入start
	$values = array();
	foreach ($stat_deal as $key => $pv) {
		$stdate = $basedate;
		list($ref_type,$url_type,$city_id,$endchar,$ref_id,$url_id,$ref_param,$url_param) = explode(",",$key);
		$line = sprintf("('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
				$stdate,
				$ref_type,
				$url_type,
				$city_id,
				$endchar,
                                $ref_id,
                                $url_id,
                                $ref_param,
                                $url_param,
				$pv
		);
		if($ref_type == '1' && $url_type == '1'){
			continue;
		}
	        $values[]=str_replace("'NULL'",'NULL',$line);
        }
	$sql = "delete from $table_stat_goods_bounce where stdate='".$stdate."'";
	$res = $dbhBA->exec($sql);
	
	$sqlHead = "insert into $table_stat_goods_bounce (stdate,ref_type,url_type,city_id,endchar,ref_id,url_id,ref_param,url_param,pv) values ";
	$arr = array_chunk($values,10000);
	foreach ($arr as $v) {
		$sql = $sqlHead . implode(",",$v);
		$res = $dbhBA->exec($sql);
	}	
	//单品数据插入end
	echo date('Y-m-d H:i:s')," \n";	
	
	//专题数据插入start
	$values = array();
	foreach ($stat_event as $key => $pv) {
		$stdate = $basedate;
		list($ref_type,$url_type,$city_id,$endchar,$ref_event,$url_event) = explode(",",$key);
		$line = sprintf("('%s','%s','%s','%s','%s','%s','%s','%s')",
				$stdate,
				$ref_type,
				$url_type,
				$city_id,
				$endchar,
                                $ref_event,
                                $url_event,
				$pv
		);
		if($ref_type == '1' && $url_type == '1'){
			continue;
		}
		$values[]=str_replace("'NULL'",'NULL',$line);
	}
	
	$sql = "delete from $table_stat_event_bounce where stdate='".$stdate."'";
	$res = $dbhBA->exec($sql);
	
	$sqlHead = "insert into $table_stat_event_bounce (stdate,ref_type,url_type,city_id,endchar,ref_event,url_event,pv) values ";
	$arr = array_chunk($values,10000);
	foreach ($arr as $v) {
		$sql = $sqlHead . implode(",",$v);
		$res = $dbhBA->exec($sql);
	}
	//专题数据插入end	
	echo date('Y-m-d H:i:s')," \n";	
	
	$useMem = memory_get_usage(true)/1024/1024;
	$maxMem = ($useMem > $maxMem) ? $useMem : $maxMem;
	echo 'memcheck......4......'.$useMem."M\n";
	echo 'max...........'.$maxMem."M\n";	
	echo "end on ".date('Y-m-d H:i:s')."\n";

} catch (PDOException $e){
    print "DB error :".$e->getMessage()."\n";
    return false;
}

return true;
?>
