#!/bin/bash
sdate=`date +%s -d"$1"`;
edate=`date +%s -d"$2"`;
export LD_LIBRARY_PATH=/home/bri/lib/mysql
ip=`/sbin/ifconfig eth1|grep 'inet addr'|cut -d: -f2|cut -d' ' -f1`
for ((i=sdate;i<=edate ;i+=86400));
do 
set -x
VISIT_COLUMN_ORDER="id visit_time ip city_id client_key session_id user_id source ref url browser goods_string"
    dateString=`date +%Y%m%d -d "$i second 1970-1-1 08:00"`
    /home/bri/bin/mysql -h127.0.0.1 -uroot -Dlogs -e 'drop table if exists go_visit_log_'${dateString}';
        CREATE TABLE go_visit_log_'${dateString}' (
        id int(11) DEFAULT NULL,
        visit_time datetime,
        ip varchar(15) DEFAULT NULL,
        city_id int(11) DEFAULT NULL,
        client_key varchar(32) DEFAULT NULL,
        session_id varchar(64) DEFAULT NULL,
        user_id int(11) DEFAULT NULL,
        source int(11) DEFAULT NULL,
        ref varchar(500) DEFAULT NULL,
        url varchar(500) DEFAULT NULL,
        browser varchar(500) DEFAULT NULL,
        pos int(11) DEFAULT NULL
        ) ENGINE=BRIGHTHOUSE DEFAULT CHARSET=utf8;' 
    zcat /home/lashouser/logroot/go_visit_log/go_visit_log_$dateString.gz|/home/bri/bin/mysql -h127.0.0.1 -uroot -Dlogs --local-infile -e "load data  local infile '/dev/stdin' into table go_visit_log_$dateString CHARACTER SET utf8  fields terminated by  '\t' enclosed by 'NULL'"
done  1>>$ip.log 2>&1 &
