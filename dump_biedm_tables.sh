#!/bin/bash
#EDM数据需要每天08:30以后运行，依赖EMD数据库点击和订单表生成时间
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
sec=`date +%s -d"$1"`
logmonth=`date +%Y%m -d"$1"`;
((datemod=sec / 86400 % 30))

db_host=$BIEDM_MYSQL_HOST
db_user=$BIEDM_MYSQL_USER
db_pass=$BIEDM_MYSQL_PASS
db_port=$BIEDM_MYSQL_PORT
db_name=BI_EDM_COREDB

#sent
mkdir -p $LOG_PATH/edm_sent/$logyear
sql="select id,task_id,email,qdh,email_server,goods,city_id,sent_time,sent_ok_time from sent_$logdate_short"
echo $sql
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/edm_sent/$logyear/edm_sent_${logdate_short}.gz
echo END:`date`

#open
mkdir -p $LOG_PATH/edm_open/$logyear
sql="select id,sent_id,open_time,encrypted_sent_id,sent_date,task_id,msp,email_server from open_$logmonth where open_time>='$logdate 00:00:00' and open_time<='$logdate 23:59:59'"
echo $sql
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/edm_open/$logyear/edm_open_${logdate_short}.gz
echo END:`date`

#click
mkdir -p $LOG_PATH/edm_click/$logyear
sql="select id,click_time,sent_id,sent_date,encrypted_sent_id,goods_id,task_id,server_ip,msp,govisit_id from click_$logmonth where click_time>='$logdate 00:00:00' and click_time<='$logdate 23:59:59'"
echo $sql
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/edm_click/$logyear/edm_click_${logdate_short}.gz
echo END:`date`

#edm_order
mkdir -p $LOG_PATH/edm_order
sql="select id,order_time,sent_id,sent_date,encrypted_sent_id,session_id,trade_no,total_fee,task_id,server_ip,msp from mail_order"
echo $sql
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/edm_order/edm_order_${datemod}.gz
echo END:`date`
