#!/bin/sh
nohup php /root/renew-certs.php &>/dev/null &
echo "sh /root/runtime/takeover.sh" | at midnight
