#!/bin/bash
source main.conf

export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH;
sql_var=' -r  -quick --default-character-set=utf8  --skip-column '

db_host=$STAT_MYSQL_HOST
db_user=$STAT_MYSQL_USER
db_pass=$STAT_MYSQL_PASS
db_port=$STAT_MYSQL_PORT
db_name=bas_stat

db_main_host=$MAIN_MYSQL_HOST
db_main_user=$MAIN_MYSQL_USER
db_main_pass=$MAIN_MYSQL_PASS
db_main_port=$MAIN_MYSQL_PORT
db_main_name=stat_cache

mkdir -p $LOG_PATH/stat_goods_sales
sql="select goods_id,amount,rev_paid,gross,view_uv,consumer_paid,refund_amount from stat_goods_sales"
echo START: `date`
echo $sql
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" >$LOG_PATH/stat_goods_sales/tmp
echo END:`date`

sql="select goods_id,start_time,deadline from go_goods"
echo START: `date`
echo $sql
$MYSQL_BIN $sql_var -h$db_main_host -u$db_main_user -p$db_main_pass -P$db_main_port -D$db_main_name -e "$sql" >$LOG_PATH/stat_goods_sales/go_goods
echo END:`date`

awk '{a[$1]+=$2;b[$1]+=$3;c[$1]+=$4;d[$1]+=$5;e[$1]+=$6;f[$1]+=$7;}END{for(i in a){print i"\t"a[i]"\t"b[i]"\t"c[i]"\t"d[i]"\t"e[i]"\t"f[i];}}' $LOG_PATH/stat_goods_sales/tmp >$LOG_PATH/stat_goods_sales/rs_tmp

awk -F"\t" 'NR==FNR{a[$1]=$2"\t"$3;}NR>FNR{if($1 in a){print $0"\t"a[$1];}}' $LOG_PATH/stat_goods_sales/go_goods $LOG_PATH/stat_goods_sales/rs_tmp > $LOG_PATH/stat_goods_sales/rs

sql="drop table if exists stat_goods_sales_sum_tmp;
	CREATE TABLE stat_goods_sales_sum_tmp(
	    goods_id int(11) NOT NULL,
	    amount int(11) NOT NULL,
	    rev_paid decimal(10,2) NOT NULL,
	    gross decimal(10,2) DEFAULT NULL,
	    view_uv int(11) NOT NULL,
	    consumer_paid int(11) NOT NULL,
	    refund_amount int(11) NOT NULL,
	    start_time datetime NOT NULL,
	    deadline datetime NOT NULL,
	    PRIMARY KEY (goods_id),
	    KEY ix_sttime(start_time),
	    KEY ix_ettime(deadline)
	)ENGINE=MyISAM DEFAULT CHARSET=utf8;
"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"

cat $LOG_PATH/stat_goods_sales/rs |$MYSQL_BIN -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name --local-infile -e "load data local infile '/dev/stdin' into table stat_goods_sales_sum_tmp"

sql="rename table stat_goods_sales_sum to stat_goods_sales_sum_tmp2"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"
sql="rename table stat_goods_sales_sum_tmp to stat_goods_sales_sum"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"
sql="drop table if exists stat_goods_sales_sum_tmp2"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"
