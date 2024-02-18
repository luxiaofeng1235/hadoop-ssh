#!/bin/bash
source main.conf

if [ ${#} -ne 1 ]; then
        echo "usage: $0  %Y-%m-%d"
        exit 1
fi
#1122
export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH;
sql_var=' -r  -quick --default-character-set=utf8  --skip-column '
logdate_short=`date +%Y%m%d -d"$1"`;
logdate=`date +%Y-%m-%d -d"$1"`;
logyear=`date +%Y -d"$1"`;
sec=`date +%s -d"$1"`
((datemod=sec / 86400 % 30))

db_host=$DATAMINING_MYSQL_HOST
db_user=$DATAMINING_MYSQL_USER
db_pass=$DATAMINING_MYSQL_PASS
db_port=$DATAMINING_MYSQL_PORT
db_name=datamining

#searchlog
mkdir -p $LOG_PATH/search_log/$logyear
logmonth=`date +%Y%m -d"$1"`;
sql="select id,search_time,display_id,client_key,user_id,city_id,type,query_str,online_num,ret_num,limit_beg,limit_num,time_cost from search_log$logmonth
	where search_time>='$logdate 00:00:00' and search_time<='$logdate 23:59:59'"
echo $sql
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/search_log/$logyear/search_log_${logdate_short}.gz
echo END:`date`

