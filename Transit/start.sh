#!/bin/sh
#<?php die();?>
source /etc/profile
umask 002
if [[ $# -ne 3 ]];then
   echo "ERR"
   exit 256
else
   isRel=$1
   port=$2
fi

####main
#sh start.sh 0 6001
cd $(cd "$(dirname "$0")";pwd)
basedir="$(pwd)"
php7_exe="/usr/local/php7/bin/php"
php="${basedir}/TransitServer.php ${isRel} ${port} ${port}"
run="${php7_exe} $php"

$(ps -eaf |grep "${php}" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
$(ps -eaf |grep "${php}" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
$(ps -eaf |grep "${php}" | grep -v "grep"| awk '{print $2}'|xargs kill -9)
ulimit -c unlimited
${run} >/dev/null 2>&1 &