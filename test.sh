#/bin/bash
source main.conf
echo $MYSQL_BIN

export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH;
echo $LD_LIBRARY_PATH
sql_var=' -r -quick --default-character-set=utf8 --skip-column '
logdate_short=`date +%Y%m%d -d "$1"`
logdate=`date +%Y-%m-%d -d "$1"`;
logyear=`date +%Y -d "$1"`;
sec=`date +%s -d "$1"`
((datemod=sec/86400 %30 ))
db_host=$ACTIVITY_MYSQL_HOST
db_user=$ACTIVITY_MYSQL_USER
db_pass=$ACTIVITY_MYSQL_PASS
db_port=$ACTIVITY_MYSQL_PORT

db_name=lashou_activity
#sql="select *  from spe_activity  limit 10";
sql="select a.id,a.pinyin,a.online_goods_id,a.isBeauty, replace(replace(replace(c.cgoods,'\t',' '),'',''),'\n','') as cgoods from spe_activity a inner join spe_category c on a.id = c.sid where a.isOk = 2 and a.isdel = 0"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" > $LOG_PATH/spe_activity/spe_activity2_${datemod}
#echo "$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" > $LOG_PATH/spe_activity/spe_activity2_${datemod}"
cat $LOG_PATH/spe_activity/spe_activity2_${datemod} | $PHP_BIN -d 'error_log=/dev/stderr' -r '
	$colum = explode(",","id,pinyin,online_goods_id,isBeauty.goods");
	$spe = array();
	while()
	{


	}

'
