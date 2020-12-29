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

db_host=$MAIN_MYSQL_HOST
db_user=$MAIN_MYSQL_USER
db_pass=$MAIN_MYSQL_PASS
db_port=$MAIN_MYSQL_PORT
db_name=stat_cache

#goods_www_uv_day
mkdir -p $LOG_PATH/goods_www_uv_day/$logyear
sql="select '$logdate',url_gid,count(DISTINCT client_key) from stat_www_visit_log_$logdate_short where url_gid>0 GROUP BY url_gid"
echo START: `date`
echo $sql
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/goods_www_uv_day/$logyear/goods_www_uv_day_${logdate_short}.gz
echo END:`date`

mkdir -p $LOG_PATH/goods_sales_info_day/$logyear
sql="select '$logdate',goods_id,SUM(amount),SUM(total_fee),SUM(gross),count(DISTINCT buyer_id) from go_order where pay_date='$logdate' and action_type=0 group by goods_id"
echo START: `date`
echo $sql
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/goods_sales_info_day/$logyear/goods_sales_info_day_${logdate_short}.gz
echo END:`date`

mkdir -p $LOG_PATH/goods_refund_info_day/$logyear
sql="select '$logdate',goods_id,SUM(ABS(amount)),SUM(ABS(total_fee)),count(DISTINCT buyer_id) from go_order where pay_date='$logdate' and action_type=1 group by goods_id"
echo START: `date`
echo $sql
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/goods_refund_info_day/$logyear/goods_refund_info_day_${logdate_short}.gz
echo END:`date`
