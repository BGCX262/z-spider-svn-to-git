#!/bin/bash
aa=`ps aux | grep bash | grep 'zspider_updateranks.sh' | grep -v grep | wc | awk '{print $1}'`
if [ $aa -le 2 ]
then
    echo $aa
    rm /home6/jiuzhida/www/zspider_updateranks.txt
    /home6/jiuzhida/www/zspider/protected/yiic zspider updateranks > /home6/jiuzhida/www/zspider_updateranks.txt 2>&1
fi
echo end
