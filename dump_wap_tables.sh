#!/bin/bash
source main.conf

if [ ${#} -ne 1 ]; then
        echo "usage: $0  %Y-%m-%d"
        exit 1
fi

export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH;
sql_var=' -r  -quick --default-character-set=utf8  --skip-column '
logdate_short=`date +%Y%m%d -d"$1"`;
logdate=`date +%Y-%m-%d -d"$1"`;
logyear=`date +%Y -d"$1"`;
workpoint=`date +%s -d"$1"`;
divpoint=`date +%s -d"2013-03-22"`;



# dump go_visit_log from wap
db_host=$WAP_MYSQL_HOST
db_user=$WAP_MYSQL_USER
db_pass=$WAP_MYSQL_PASS
db_port=$WAP_MYSQL_PORT
db_name=wap
#db_host=10.168.31.30
#db_user=read_only
#db_pass=read_only
#db_port=3307
#db_name=logs
echo START: `date`
mkdir -p $LOG_PATH/visit_wap_log/$logyear
if [ $workpoint -lt $divpoint ] ;then
  sql="select id,visit_time,ip,cdn_node,city_id,client_key,session_id,user_id,source as channel_id,ref,url,replace(browser,'\t',' ') as browser,sem,NULL as screenWidth, NULL as screenHeight, NULL as devicePixelRatio, NULL as deviceWidth, NULL as deviceHeight from go_visit_log${logdate_short}"
else
  sql="select id,visit_time,ip,cdn_node,city_id,client_key,session_id,user_id,source as channel_id,ref,url,replace(browser,'\t',' ') as browser,sem , screenWidth, screenHeight ,devicePixelRatio , deviceWidth, deviceHeight from go_visit_log${logdate_short}"
fi
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/visit_wap_log/$logyear/visit_wap_log_${logdate_short}.gz
echo END:`date`

