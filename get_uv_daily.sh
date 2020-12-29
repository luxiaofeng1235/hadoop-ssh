#!/bin/bash
source main.conf

echo START: `date`

if [ $1 ]
then
dateStr=$1
else
dateStr=`date -d last-day +%Y%m%d`
fi

yearStr=`date +%Y -d "$dateStr"`
today_uv=`zcat $LOG_PATH/daily_uv/$yearStr/daily_uv_$dateStr.gz |sort -u | wc -l`

week=`date +%w -d "$dateStr"`
if [ $week -eq '5' ]
then
mkdir -p  $LOG_PATH/weekly_uv/$yearStr/
sdate=`date +%s -d"$dateStr"`;
for ((j=0;j<7;j++))
do
((sec=sdate-j*86400))
dateString=`date +%Y%m%d -d "$sec second 1970-1-1 08:00"`
yearString=`date +%Y -d "$sec second 1970-1-1 08:00"`
zcat $LOG_PATH/daily_uv/$yearString/daily_uv_$dateString.gz
done |sort -u -T$LOG_PATH/weekly_uv/|gzip -c >$LOG_PATH/weekly_uv/$yearStr/weekly_uv_$dateStr.gz
week_uv=`zcat $LOG_PATH/weekly_uv/$yearStr/weekly_uv_$dateStr.gz | wc -l`
else
week_uv='0'
fi

tmpdate=`date +%s -d"$dateStr"`;
((tmpdate=tmpdate+86400))
month=`date +%d -d "$tmpdate second 1970-1-1 08:00"`
tmpdate=`date +%Y%m%d -d "$tmpdate second 1970-1-1 08:00"`
if [ $month -eq '01' ]
then
mkdir -p  $LOG_PATH/monthly_uv/$yearStr/
lmStr=`date +%Y%m -d "$dateStr"`
sdate=`date +%Y%m%d -d"$lmStr"01`;
ssec=`date +%s -d"$sdate"`
esec=`date +%s -d"$tmpdate"`
for((j=$ssec;j<$esec;j=$j+86400))
do
dateString=`date +%Y%m%d -d "$j second 1970-1-1 08:00"`
yearString=`date +%Y -d "$j second 1970-1-1 08:00"`
zcat $LOG_PATH/daily_uv/$yearString/daily_uv_$dateString.gz
done |sort -u -T$LOG_PATH/monthly_uv/|gzip -c >$LOG_PATH/monthly_uv/$yearStr/monthly_uv_$dateStr.gz
month_uv=`zcat $LOG_PATH/monthly_uv/$yearStr/monthly_uv_$dateStr.gz | wc -l`
else
month_uv='0'
fi
db_host=$STAT_MYSQL_HOST
db_user=$STAT_MYSQL_USER
db_pass=$STAT_MYSQL_PASS
db_port=$STAT_MYSQL_PORT
db_name=bas_stat
sdate=`date +%Y-%m-%d -d "$dateStr"`
export LD_LIBRARY_PATH=/home/bri/lib/mysql:$LD_LIBRARY_PATH
sql="replace into stat_uv_day values('$sdate',$today_uv,$week_uv,$month_uv)"
$MYSQL_BIN -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"
echo END:`date`
