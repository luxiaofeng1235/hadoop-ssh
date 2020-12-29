#!/bin/bash
source main.conf
export LD_LIBRARY_PATH=/home/bri/lib/mysql
dateString=`date +%Y%m%d -d "$1"`
yearString=`date +%Y -d "$1"`
#这个脚本必须在BAGO_MYSQL_HOST机器上运行
    $MYSQL_BIN -h127.0.0.1 -uroot -Dlogs -e 'drop table if exists go_visit_log_'${dateString}';
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
    zcat $LOG_PATH/go_visit_log/$yearString/go_visit_log_$dateString.gz|$MYSQL_BIN -h127.0.0.1 -uroot -Dlogs --local-infile -e "load data  local infile '/dev/stdin' into table go_visit_log_$dateString CHARACTER SET utf8  fields terminated by  '\t' enclosed by 'NULL'"
