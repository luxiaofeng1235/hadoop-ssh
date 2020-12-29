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
sec=`date +%s -d"$1"`
((datemod=sec / 86400 % 30))

db_host=$THINK_MYSQL_HOST
db_user=$THINK_MYSQL_USER
db_pass=$THINK_MYSQL_PASS
db_port=$THINK_MYSQL_PORT
db_name=stats

#360
mkdir -p $LOG_PATH/cps_360/$logyear
sql="
    select a.trade_no,a.bid,a.qihoo_id,a.qid,a.qmail,a.qname,a.ext,a.buyer_id,a.total_fee,a.goods_id,a.buy_time,a.amount,a.cost_fee,a.ext2,b.wker
    from cps_360 a inner join operater_buy_log b
    on a.trade_no=b.trade_no
    where a.buy_time >='$logdate 00:00:00' and a.buy_time <='$logdate 23:59:59'
"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/cps_360/$logyear/cps_360_${logdate_short}.gz
echo END:`date`

#baidu
mkdir -p $LOG_PATH/cps_baidu/$logyear
sql="
    select order_id,access_token,price,goods_num,sum_price,from_unixtime(expire),uid,tn,baiduid,bonus,add_time from baidu_cps_post 
    where add_time >='$logdate 00:00:00' and add_time <='$logdate 23:59:59'
"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/cps_baidu/$logyear/cps_baidu_${logdate_short}.gz
echo END:`date`
