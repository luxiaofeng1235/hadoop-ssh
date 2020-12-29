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


echo START: `date`
mkdir -p $LOG_PATH/dim_wireless_device
cat <<HERE |
android
ipad
iphone
unknown
win8
HERE
gzip -c >$LOG_PATH/dim_wireless_device/dim_wireless_device_${datemod}.gz

mkdir -p $LOG_PATH/dim_life_cycle
if [ 1 ];then
 #   for((i=1

fi| gzip -c >$LOG_PATH/dim_life_cycle/dim_life_cycle_${datemod}.gz

echo END:`date`

