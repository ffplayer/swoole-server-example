#!/bin/sh
#<?php die();?>
source /etc/profile
umask 002
ulimit -c unlimited
base_dir=$(cd $(dirname $0); pwd)
php7_exe="/usr/local/php7/bin/php"

if [ $# -ne 3 ];then
   echo -e "`date` Argument Error,Usage: /bin/sh $0 ProJectID Num(0|1) Port" >> /tmp/.php_ssicbo.log
   exit 256
else
   project_id=$1
   project_env=$2
   project_port=$3
fi

####main
php="${base_dir}/src/SicboServer.php ${project_id} ${project_env} ${project_port}"
run="${php7_exe} $php"
count=2
for((i=1;i<=5;i++));do 
    count=`ps -fe |grep "$run" | grep -v "grep" | wc -l`
    if [ $count -eq 2 ]; then
        break
    fi
    sleep 0.1
done
ret=0
if [ $count -lt 2 ]; then
    $(ps -eaf |grep "$php" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
    $(ps -eaf |grep "$php" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
    $(ps -eaf |grep "$php" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
    sleep 2
    ulimit -c unlimited

    #业务残留数据清理
    if [ -d "${base_dir}/data" ]
    then
	    rm -fr "${base_dir}/data/*"
	fi
    
    $run >/dev/null 2>&1 &
    ret=2

    #${php7_exe} "${base_dir}/tool/updateOnlineUserCount.php" ${project_id} ${project_env} ${project_port}
else
    ret=1
fi
${php7_exe} ${base_dir}/include/Crontab.php ${project_id} ${ret} ${project_port}

