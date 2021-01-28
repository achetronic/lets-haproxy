#!/bin/sh
if [ $(ps -ef | grep -v grep | grep '/usr/sbin/haproxy' | wc -l) -lt 1 ]; then
  exit 1
else
  exit 0
fi