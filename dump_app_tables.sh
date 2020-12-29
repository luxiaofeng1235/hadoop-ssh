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


# dump app_go_visit_log from ba
db_host=$APP_MYSQL_HOST
db_user=$APP_MYSQL_USER
db_pass=$APP_MYSQL_PASS
db_port=$APP_MYSQL_PORT
db_name=wireless_groupbuy
echo START: `date`
mkdir -p $LOG_PATH/visit_app_log/$logyear
sql="select id,app_name,app_edition,client_id,client_name,channe_id,user_id,city_id,device,os_version,url,visit_time,replace(replace(replace(post_parm,'\t',' '),'
',''),'\n','') as post_parm,memo,client_key from visit_app_log${logdate_short}"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/visit_app_log/$logyear/visit_app_log_${logdate_short}.gz
echo END:`date