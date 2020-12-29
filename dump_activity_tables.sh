#!/bin/bash
source main.conf

source main.conf


if [ ${#} -ne 1 ]; then
        echo "usage: $0  %Y-%m-%d"
        exit 1
fi

export LD_LIBRARY_PATH =/home/bri/lib/mysql:$LD_LIBRARY_PATH;
sql_var=' -r  -quick --default-character-set=utf8  --skip-column '
logdate_short=`date +%Y%m%d -d"$1"`;
logdate=`date +%Y-%m-%d -d"$1"`;
logyear=`date +%Y -d"$1"`;
sec=`date +%s -d"$1"`
((datemod=sec / 86400 % 30))


# dump go_visit_log from wap
db_host=$ACTIVITY_MYSQL_HOST
db_user=$ACTIVITY_MYSQL_USER
db_pass=$ACTIVITY_MYSQL_PASS
db_port=$ACTIVITY_MYSQL_PORT
db_name=lashou_activity
echo START: `date`
#极品数据要用极品办法来处理
sql="select a.id,a.pinyin,a.online_goods_id,a.isBeauty, replace(replace(replace(c.cgoods,'\t',' '),'
',''),'\n','') as cgoods from spe_activity a inner join spe_category c on a.id = c.sid where a.isOk = 2 and a.isdel = 0"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"  >$LOG_PATH/spe_activity/spe_activity_${datemod}
sql="select a.id,a.pinyin,a.online_goods_id,a.isBeauty, replace(replace(replace(t.tgoods,'\t',' '),'
',''),'\n','') as tgoods from spe_activity a inner join spe_tetui t on a.id = t.sid where a.isOk = 2 and a.isdel = 0"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"  >>$LOG_PATH/spe_activity/spe_activity_${datemod}

#做展开和去重
#去重办法是建关联数组, 后读到的覆盖前面的
cat $LOG_PATH/spe_activity/spe_activity_${datemod}|$PHP_BIN -d'error_log=/dev/stderr' -r ' 
$column = explode(",","id,pinyin,online_goods_id,isBeauty,goods");
$spe = array();
while ($l = stream_get_line(STDIN,1048576,"\n")) {
    $a = explode("\t",$l);
    if (count($a) != count($column)) {
        error_log($l);
        continue;
    }
    $g = explode(",",$a[4]);
    foreach ($g as $goods_id) {
        $spe[$a[0]][$goods_id] = sprintf("%s\t%s\t%s\t%s\t%s\n",$a[0],$a[1],$a[2],$a[3],$goods_id);
    }
}
foreach ($spe as $sid => $sline) {
    foreach ($sline as $gid =>$gline) {
        echo $gline;
    }
}
error_log(count($spe));
    ' |gzip -c >$LOG_PATH/spe_activity/spe_activity_${datemod}.gz
rm -f $LOG_PATH/spe_activity/spe_activity_${datemod}

#dump youhui_temp 
mkdir -p $LOG_PATH/youhui_temp
sql="select id,uid,valid_time,expire,allow_cat,allow_cat_name,allow_channel,kind,discount,value,summary,limit_price,limit_total_fee,add_time,theme,active from youhui_temp"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/youhui_temp/youhui_temp_${datemod}.gz

#dump lottery_users,抽奖活动信息,全量
mkdir -p $LOG_PATH/lottery_users
sql="select id,goods_id,uid,city,inviter,mobile,new_user,join_time,check_time,trade_no,sms_time,confirm_code,sms_num,error_time,error_num from lottery_users "
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/lottery_users/lottery_users_${datemod}.gz

echo END:`date`

