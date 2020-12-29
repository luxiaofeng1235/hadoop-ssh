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

# dump go_visit_log from logs
db_host=$LOGS_MYSQL_HOST
db_user=$LOGS_MYSQL_USER
db_pass=$LOGS_MYSQL_PASS
db_port=$LOGS_MYSQL_PORT
db_name=logs
echo START: `date`
mkdir -p $LOG_PATH/go_visit_log/$logyear
sql="select id,visit_time,ip,city_id,replace(replace(replace(client_key,'\t',' '),'
',''),'\n','') as client_key,replace(replace(replace(session_id,'\t',' '),'
',''),'\n','') as session_id,user_id,source,ref,url,replace(replace(replace(browser,'\t',' '),'
',''),'\n','') as browser ,pos from go_visit_log${logdate_short}"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_visit_log/$logyear/go_visit_log_${logdate_short}.gz
echo END:`date`

# dump go_visit_trade_no from logs
sql="select client_key,session_id,trade_no,add_time from go_visit_trade_no where add_time between '$logdate 00:00:00' and '$logdate 23:59:59' group by session_id,trade_no"
echo START: `date`
mkdir -p $LOG_PATH/go_visit_trade_no/$logyear
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/go_visit_trade_no/$logyear/go_visit_trade_no_${logdate_short}.gz
echo END:`date`

echo START: `date`
#mkdir -p $LOG_PATH/goods_show_log/$logyear
#sql="select id,date(visit_time) as visit_date,goods_string from go_visit_log${logdate_short}"
#$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |$PHP_BIN -d'error_log=/dev/stderr' -r ' 
#while ($l = stream_get_line(STDIN,1048576,"\n")) {
#    $a = explode("\t",$l,3);
#    if ($a[2] == "NULL") {
#        continue;
#    }
#    $g = preg_split("/[,\s]+/",$a[2]);
#    foreach ($g as $show_pos=>$goods_id) {
#        fprintf(STDOUT,"%s\t%s\t%s\t%s\n",$a[0],$a[1],$show_pos+1,$goods_id);
#    }
#}
#'|gzip -c >$LOG_PATH/goods_show_log/$logyear/goods_show_log_${logdate_short}.gz


# dump user_reg_session from logs
sql="select uid,client_key,session_id,add_time from user_reg_session where add_time between '$logdate 00:00:00' and '$logdate 23:59:59'"
echo START: `date`
mkdir -p $LOG_PATH/user_reg_session/$logyear
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/user_reg_session/$logyear/user_reg_session_${logdate_short}.gz
echo END:`date`

# dump set_mobile_log from logs
sql="select uid,old_mobile,mobile,update_time,client from set_mobile_logs where update_time between '$logdate 00:00:00' and '$logdate 23:59:59'"
echo START: `date`
mkdir -p $LOG_PATH/set_mobile_logs/$logyear
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/set_mobile_logs/$logyear/set_mobile_logs_${logdate_short}.gz
echo END:`date`
