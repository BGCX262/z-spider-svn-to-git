#!/bin/bash
aa=`ps aux | grep bash | grep 'zspider_rankgroup.sh' | grep -v grep | wc | awk '{print $1}'`
if [ $aa -le 2 ]
then
    echo $aa
    rm /home6/jiuzhida/www/zspider_rankgroup.txt
    /home6/jiuzhida/www/zspider/protected/yiic zspider rankgroup > /home6/jiuzhida/www/zspider_rankgroup.txt 2>&1
fi
echo end
