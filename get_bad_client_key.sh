#!/bin/bash
source main.conf
export LD_LIBRARY_PATH=/home/bri/lib/mysql
sdate=`date +%s -d"$1"`;
edate=`date +%s -d"$2"`;

#联盟上线日期是2012-3-17
#在那之前没有必要计算联盟带来的无效流量
uniondate=`date +%s -d "2012-03-17"`;
xdate=`date +%s -d "2012-12-13"`;

for ((i=sdate;i<=edate ;i+=86400));
do 
    yearString=`date +%Y -d "$i second 1970-1-1 08:00"`
    dateString=`date +%Y%m%d -d "$i second 1970-1-1 08:00"`
#里面的 uv > 50, pv>150 pv/lpv < 1.2 都是调研数据后总结的经验阈值
#这个阈值侧重准确率 
$MYSQL_BIN -A -b -u$BAGO_MYSQL_USER -p$BAGO_MYSQL_PASS -h$BAGO_MYSQL_HOST -Dlogs -P$BAGO_MYSQL_PORT --skip-column  -e '
select t2.client_key from
(SELECT ip,client_key,count(1)  pv from go_visit_log_'$dateString' group by client_key ) t2
inner join
(SELECT ip,count(DISTINCT(client_key)) uv from go_visit_log_'$dateString' group by ip ) t1
on t1.ip = t2.ip
left join
(SELECT client_key,count(1)  lpv from go_visit_log_'$dateString' where url like "%login%"  group by client_key ) t3
on t2.client_key = t3.client_key
where uv > 50 and (pv =1 or pv >150 or pv/lpv <1.2)
' 1>$LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString
if [ $? -ne 0 ];
then 
exit 1
fi

#去掉一些奇怪的刷页面行为，可能也会顺便去掉了一些拉手自己的客服和后台来的非用户流量
#500是调研得到的经验阈值
#利用了client_key预埋渠道号的升级，所以在那之前的日志不需要这步
if [ $i -gt $xdate ]
then
$MYSQL_BIN -A -b -u$BAGO_MYSQL_USER -p$BAGO_MYSQL_PASS -h$BAGO_MYSQL_HOST -Dlogs -P$BAGO_MYSQL_PORT --skip-column  -e '
select t2.client_key from
(SELECT client_key,count(1) pv from go_visit_log_'$dateString' group by client_key ) t2
inner join
(SELECT client_key,count(1) nv from go_visit_log_'$dateString' where user_id = 0 and source = 0 and client_key not like "%x%" group by client_key ) t1
on t1.client_key = t2.client_key
where t2.pv = t1.nv and t1.nv > 500 
' 1>>$LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString
if [ $? -ne 0 ];
then 
exit 1
fi
fi
#

if [ $i -gt $uniondate ]
then
$MYSQL_BIN -A -b -u$BAGO_MYSQL_USER -p$BAGO_MYSQL_PASS -h$BAGO_MYSQL_HOST -Dlogs -P$BAGO_MYSQL_PORT --skip-column  -e '
select  t1.client_key from
(select client_key,count(1) as spv from go_visit_log_'$dateString' group by client_key) t1
inner join 
(select client_key,count(1) as upv from go_visit_log_'$dateString' where url like "%union_pid=%" and url not like "%qdh=%" group by client_key) t2
on t1.client_key = t2.client_key
where t1.spv = t2.upv 
' 1>>$LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString
#这是把通过联盟链接进来但没有发生二跳的访问去掉
#主要目的是去掉种cookie类型作弊对uv的影响,会误杀一部分没作弊网站的无二跳流量，
#会让转化率虚高一点, 但因为作弊的量会远大于正常的量，去掉后流量数据总体更健康一些
if [ $? -ne 0 ];
then 
exit 1
fi
fi
size=`stat $LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString -c %s 2>/dev/null`
if [ "$size" == "" -o "$size" == "0" ]
then
rm -f $LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString
exit 1
fi
#运行过程中不清空原文件，最后才处理
mkdir -p $LOG_PATH/bad_client_key/$yearString/
cat $LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString |gzip -c >$LOG_PATH/bad_client_key/$yearString/bad_client_key_$dateString.gz
rm -f $LOG_PATH/bad_client_key/tmp.bad_client_key_$dateString 
done 
