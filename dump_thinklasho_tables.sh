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


# dump go_visit_log from logs
db_host=$THINK_MYSQL_HOST
db_user=$THINK_MYSQL_USER
db_pass=$THINK_MYSQL_PASS
db_port=$THINK_MYSQL_PORT
db_name=thinklasho

#go_buy for stat_trade_date
mkdir -p $LOG_PATH/go_buy/$logyear
sql="select date(buy_time) as buy_date,trade_no,buyer_id,amount,(g.price * b.amount ) as total_fee,buy_time,b.sp_id,b.goods_id, g.new_cat , g.channel,g.type,g.price, g.cost_pirce ,(b.amount*(g.price-g.cost_pirce)) as gross from go_buy b inner join go_goods g on b.goods_id = g.goods_id where b.buy_time between '$logdate 00:00:00' and '$logdate 23:59:59'"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_buy/$logyear/go_buy_${logdate_short}.gz
echo END:`date`

#go_order for stat_pay_trade_date and  stat_refund_trade_date
mkdir -p $LOG_PATH/go_order/$logyear
sql="select date(add_time) as pay_date,b.action_type,order_id,trade_no,buyer_id,b.sp_id,b.goods_id,b.city_id,amount,b.convey_fee,b.total_fee,b.payed,b.charge_pay,b.epurse_payed,b.add_time, g.new_cat , g.channel,g.type,g.price, g.cost_pirce ,(b.amount*(g.price-g.cost_pirce)) as gross, b.refund_type,b.refund_reason from go_order b inner join go_goods g on b.goods_id = g.goods_id where b.add_time between '$logdate 00:00:00' and '$logdate 23:59:59' "
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_order/$logyear/go_order_${logdate_short}.gz
echo END:`date`


mkdir -p $LOG_PATH/go_goods/
sql="select  goods_id , replace(replace(replace(product,'\t',' '),'',''),'\n','') as product ,sp_id,city_id,sales_city_id,new_cat ,type , value , price, cost_pirce as cost_price , channel ,start_time, deadline, replace(replace(replace(htnumber,'\t',' '),'^M',''),'\n','') as htnumber from go_goods"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_goods/go_goods_$datemod.gz
echo END:`date`

mkdir -p $LOG_PATH/go_sp/
sql="select  sp_id , replace(replace(replace(name,'\t',' '),'',''),'\n','') as sp_name,type , createtime from go_sp"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_sp/go_sp_$datemod.gz
echo END:`date`

#dump user from thinklasho
mkdir -p $LOG_PATH/reg_user/$logyear
echo START: `date`
sql="select id as user_id,from_unixtime(add_time) as reg_time , client from users where add_time between unix_timestamp('$logdate 00:00:00') and unix_timestamp('$logdate 23:59:59')"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/reg_user/$logyear/reg_user_${logdate_short}.gz
mkdir -p $LOG_PATH/user_info/
sql="select id ,replace(replace(replace(user_id,'\t',' '),'',''),'\n','') as user_name , replace(replace(replace(email,'\t',' '),'',''),'\n','') as email , email_state ,phone, mobile ,pwd from users "
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/user_info/user_info_${datemod}.gz
echo END:`date`


#dump go_buy_log from thinklasho, app & wap trade data
mkdir -p $LOG_PATH/go_buy_log
echo START: `date`
#352629 是go_buy_log里11年的最后一条数据id
sql="select buy_time, pay_time,trade_no,leave_sign,app_name,app_edition,client_id,client_name,channe_id as channel_id,device,os_version from go_buy_log b where id > 352629 "
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_buy_log/go_buy_log_${datemod}.gz
echo END:`date`

#dump statistice from thinklasho, app & wap reg data
mkdir -p $LOG_PATH/statistice/$logyear
echo START: `date`
sql="select user_id,channel as channel_id,from_unixtime(add_time) as reg_time , software_alias as app_name,software_edition as app_edition,equipment_id as client_id,equipment_name as client_name from statistice where add_time between unix_timestamp('$logdate 00:00:00') and unix_timestamp('$logdate 23:59:59')"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/statistice/$logyear/statistice_${logdate_short}.gz
echo END:`date`

#dump go_youhui 
mkdir -p $LOG_PATH/go_youhui
sql="select youhui_id,add_time,price,value,trade_no,goods_id,buy_trade_no,status,type from go_youhui"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_youhui/go_youhui_${datemod}.gz
echo END:`date`

#dump go_youhui_bills 
mkdir -p $LOG_PATH/go_youhui_bills
sql="select youhui_id,goods_id,buyer_id,consume_trade_no,consume_goods_id,money,type,add_time from go_youhui_bills"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_youhui_bills/go_youhui_bills_${datemod}.gz
echo END:`date`

#dump go_mycollection
mkdir -p $LOG_PATH/go_mycollection
sql="select id, goods_id ,uid,dateline from go_mycollection"
echo START: `date`
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_mycollection/go_mycollection_${datemod}.gz
echo END:`date`
