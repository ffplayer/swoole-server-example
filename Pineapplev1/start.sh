#!/bin/sh
#<?php die();?>
source /etc/profile
umask 002
if [[ $# -ne 3 ]];then
   echo "ERR"
   exit 256
else
   sid=$1
   isRel=$2
   port=$3
fi

####main
#sh start.sh 110 0 6620
cd $(cd "$(dirname "$0")";pwd)
basedir="$(pwd)"
php7_exe="/usr/local/php7/bin/php"
php="${basedir}/PineappleServer.php ${sid} ${isRel} ${port}"
run="${php7_exe} $php"

$(ps -eaf |grep "${php}" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
$(ps -eaf |grep "${php}" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
$(ps -eaf |grep "${php}" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
ulimit -c unlimited
${run} 