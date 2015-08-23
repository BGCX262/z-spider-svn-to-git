#!/bin/bash
aa=`ps aux | grep bash | grep 'zspider_unusedbfs.sh' | grep -v grep | wc | awk '{print $1}'`
if [ $aa -le 2 ]
then
    echo $aa
    rm /home6/jiuzhida/www/zspider_unusedbfs.txt
    /home6/jiuzhida/www/zspider/protected/yiic zspider deleteunusedbfs > /home6/jiuzhida/www/zspider_unusedbfs.txt 2>&1
    /home6/jiuzhida/www/zspider/protected/yiic zspider checkbackusedbfs >> /home6/jiuzhida/www/zspider_unusedbfs.txt 2>&1
fi
echo end
