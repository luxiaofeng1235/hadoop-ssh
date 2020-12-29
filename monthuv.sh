#!/bin/bash
source main.conf
stmonth=`date +%m -d"$1"|sed 's/^0//'`
etmonth=`date +%m -d"$2"|sed 's/^0//'`
year=`date +%Y -d "$2"`
for ((i=stmonth;i<=etmonth ;i++));
do 

    for ((j=1;j<=31;j++))
    do
    dateString=`date +%Y%m%d -d "$year-$i-$j" 2>/dev/null`
    if [ "$dateString" == "" ]
    then
    break
    fi
    zcat $LOG_PATH/daily_uv/$year/daily_uv_$dateString.gz 2>/dev/null
    done | sort -u -T$LOG_PATH/monthly_uv|gzip -c >$LOG_PATH/monthly_uv/$year/monthly_uv_$year$i.gz
done 
