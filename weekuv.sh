#!/bin/bash
source main.conf
sdate=`date +%s -d"$1"`;
edate=`date +%s -d"$2"`;
for ((i=sdate;i<=edate ;i+=86400*7));
do 

suf=`date +%Y%m%d_%W -d "$i second 1970-1-1 08:00"`
year=`date +%Y -d "$i second 1970-1-1 08:00"`
mkdir -p  $LOG_PATH/weekly_uv/$year/
    for ((j=0;j<7;j++))
    do
    ((sec=i+j*86400))
    dateString=`date +%Y%m%d -d "$sec second 1970-1-1 08:00"`
    zcat $LOG_PATH/daily_uv/$year/daily_uv_$dateString.gz
    done |sort -u -T$LOG_PATH/weekly_uv/|gzip -c >$LOG_PATH/weekly_uv/$year/weekly_uv_$suf.gz
done 
