#!/bin/bash
#aa=`ps aux | grep yii | grep zspider | grep -v grep | wc | awk '{print $1}'`
aa=`ps aux | grep bash | grep 'zspider_search.sh' | grep -v grep | wc | awk '{print $1}'`
if [ $aa -le 2 ]
then
    echo $aa
    rm /home6/jiuzhida/www/zspider.txt
    /home6/jiuzhida/www/zspider/protected/yiic zspider > /home6/jiuzhida/www/zspider.txt 2>&1
fi
echo end
