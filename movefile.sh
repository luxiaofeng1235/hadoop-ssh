#/bin/sh

srcdir='/home/lashouser/logroot/goods_refund_info_day_sum/2012'
destdir='/home/lashouser/logroot/goods_refund_info_day/2012'

mkdir -p $destdir
cd $srcdir
for files in `ls *`
do
destfiles=${files/\_sum/}
mv $srcdir/$files $destdir/$destfiles
done
