#!/bin/bash
sdate=`date +%s -d"$1"`;
edate=`date +%s -d"$2"`;
export LD_LIBRARY_PATH=/home/bri/lib/mysql
set -x
ip=`hostname -i`
for ((i=sdate;i<=edate ;i+=86400));
do 
    dateString=`date +%Y%m%d -d "$i second 1970-1-1 08:00"`
    monthString=`date +%Y%m -d "$i second 1970-1-1 08:00"`
    zcat /home/lashouser/logroot/go_visit_log/go_visit_log_$dateString.gz|awk -F"\t" '{print $5}' |uniq >>Uv.$dateString
done  2>error.out.$ip
