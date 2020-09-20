#!/bin/bash

# Configure Haproxy for certbot
service haproxy stop
cp /root/haproxy-certbot.cfg /etc/haproxy/haproxy.cfg

# Start the proxy
service haproxy start

# Renew certificates
certbot renew -n

# Re-configure Haproxy again
service haproxy stop

# Prepare certs for Haproxy
/root/join-certs.sh

# Configure Haproxy with user configuration file
cp /root/haproxy.cfg /etc/haproxy/haproxy.cfg

# Start the proxy
service haproxy start