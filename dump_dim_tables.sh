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



# dump app_go_visit_log from ba
db_host=$DIM_MYSQL_HOST
db_user=$DIM_MYSQL_USER
db_pass=$DIM_MYSQL_PASS
db_port=$DIM_MYSQL_PORT
db_name=bas_entity
echo START: `date`
mkdir -p $LOG_PATH/dim_channel_id
sql="select channel_id,replace(replace(replace(channel_name,'\t',' '),'',''),'\n','') as channel_name,media_id,replace(replace(replace(media_name,'\t',' '),'',''),'\n','') as media_name, organic from v_dim_channel_id"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_channel_id/dim_channel_id_${datemod}.gz

mkdir -p $LOG_PATH/dim_city
sql="select city_id,city_name,province_id,province_name from v_dim_city"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_city/dim_city_${datemod}.gz

mkdir -p $LOG_PATH/dim_stdate
sql="select stdate,stweek,stmonth,stmonthday,stweekday,week,month,year from v_dim_stdate"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_stdate/dim_stdate_${datemod}.gz

mkdir -p $LOG_PATH/dim_url_type
sql="select id as type_id ,text1,text2,cond from url_type_php"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_url_type/dim_url_type_${datemod}.gz

mkdir -p $LOG_PATH/dim_endchar
sql="select endchar ,group1,group2 from endchar_group"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_endchar/dim_endchar_${datemod}.gz


mkdir -p $LOG_PATH/dim_pos
sql="select min_pos,replace(replace(replace(src_name,'\t',' '),'',''),'\n','') src_name,replace(replace(replace(page_name,'\t',' '),'',''),'\n','') page_name,replace(replace(replace(area_name,'\t',' '),'',''),'\n','') area_name from pos_code"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql"|awk -F"\t" 'BEGIN{OFS="\t"}
{
    for (i=0;i<100;i++) {
        printf("%s\t%s\t%s\t%s\t%s\n",$1+i, $2, $3,$4,sprintf("%s-%s{%s}",$3,$4,i+1));
    }
}' |gzip -c >$LOG_PATH/dim_pos/dim_pos_${datemod}.gz

db_host=$THINK_MYSQL_HOST
db_user=$THINK_MYSQL_USER
db_pass=$THINK_MYSQL_PASS
db_port=$THINK_MYSQL_PORT
db_name=thinklasho
mkdir -p $LOG_PATH/dim_cat
sql="select web.id as web_id, map.finance_category_id as new_cat ,web.cate_name,web.cate_pinyin ,
    lv2.cate_name,lv2.cate_pinyin as pinyin,
    lv1.cate_name,lv1.cate_pinyin as pinyin,
    web.status as web_status ,web.type as web_catetype  ,web.mealtype 
from go_category_web web inner join go_category_map map on web.id = map.web_id 
left join go_category_web lv2 on web.fid = lv2.id
left join go_category_web lv1 on lv2.fid = lv1.id
where web.level in (2,3)
"
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_cat/dim_cat_${datemod}.gz

mkdir -p $LOG_PATH/dim_zone
sql='select id as zone_id,pinyin,cityId as city_id, city as city_name ,districtId as district_id,district as district_name,
    full_name ,longitude,latitude,`range`,level,new_range from zone_info'
$MYSQL_BIN $sql_var -h$db_host -u$db_user -p$db_pass -P$db_port -D$db_name -e "$sql" |gzip -c >$LOG_PATH/dim_zone/dim_zone_${datemod}.gz
echo END:`date`

