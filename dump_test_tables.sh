#!/bin/bash
source main.conf

export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH;
sql_var=' -r  -quick --default-character-set=utf8  --skip-column '
logdate_short=`date +%Y%m%d -d"$1"`;
logdate=`date +%Y-%m-%d -d"$1"`;
logyear=`date +%Y -d"$1"`;
sec=`date +%s -d"$1"`
((datemod=sec / 86400 % 30))


# dump go_visit_log from logs
db_host=$EXBA_MYSQL_HOST
db_user=$EXBA_MYSQL_USER
db_pass=$EXBA_MYSQL_PASS
db_port=$EXBA_MYSQL_PORT
db_name=thinklasho

#go_buy for stat_trade_date
mkdir -p $LOG_PATH/go_buy_log_test/$logyear
sql="select id,buy_date,trade_no,buyer_id,sp_id,goods_id,amount,total_fee,pay_fee,charge_pay,payed,epurse_payed,status,buy_time,pay_time,type,mobile,city_id,leave_sign from go_buy_log"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_buy_log_test/$logyear/go_buy_log_test_${logdate_short}.gz
echo END:`date`


