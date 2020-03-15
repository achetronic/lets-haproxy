#!/bin/bash

# Create the fake file for DO credentials
rm -rf /tmp/dot.ini
#mkfifo /root/dot.ini
touch /tmp/dot.ini
chmod 600 /tmp/dot.ini
echo -e "dns_digitalocean_token=$(printenv DO_TOKEN)" >> /tmp/dot.ini &

