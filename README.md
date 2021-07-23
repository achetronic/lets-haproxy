# Let's Haproxy

## Bugs
---

## Introduction
I made this image because of the need of having the Haproxy's ease and Let's Encrypt certs autoconfigured for my home server.
I looked for something similar and all the alternatives were a hell.

## Who want this
Everyone that use Haproxy and Docker. This is useful because you get all Haproxy options and a good automation for getting and renewing Let's Encrypt free certificates automatically.

## How to use
```
docker run -it \
  --env ADMIN_MAIL=admin@example.com \
  --env SKIP_CREATION=false \
  --env ENVIRONMENT=staging \
  -v "./haproxy.cfg:/root/templates/haproxy.user.cfg" \
  -v "letsencrypt_logs:/var/log/letsencrypt" \
  -v "letsencrypt_data:/etc/letsencrypt" \
  achetronic/lets-haproxy:latest

```

## haproxy.cfg file
As you can see, we bind the haproxy.cfg file to the container. This is your normal configuration file, nothing new. The only thing you need to define for sure is a frontend binded to 443 port with the domains you want to cert. For example:

```
# CONFIGS APPLIED GLOBALLY
global
    maxconn 32768
    daemon

# CONFIGS APPLIED BY DEFAULT ON FRONTENDS AND BACKENDS
defaults
    mode    http
    retries 3
    timeout connect     5s
    timeout client     50s
    timeout server    450s

# FRONTENDS HTTP
frontend http-in
    bind *:80

    acl http ssl_fc,not
    http-request redirect scheme https if http

# FRONTENDS HTTP
frontend https-in
    bind *:443
    mode http

    acl host_domain hdr(host) -i domain.com
    use_backend cluster_domain if host_domain

    acl host_subdomain_domain hdr(host) -i subdomain.domain.com
    use_backend cluster_subdomain_domain if host_subdomain_domain

# BACKENDS HTTP
backend cluster_domain
    mode http
    balance roundrobin
    option forwardfor
    server node1 192.168.0.4:8020 check

backend cluster_subdomain_domain
    mode http
    balance roundrobin
    option forwardfor
    server node1 192.168.0.4:8010 check

# FRONTENDS TCP
frontend minecraft-in
    bind *:25565
    mode tcp
    use_backend cluster_minecraft

# BACKENDS TCP
backend cluster_minecraft
    mode tcp
    server node1 192.168.0.4:25565 check

```
As you can see, there is a frontend binded to the port 443 with the domains defined as you usually would do it. They will be automatically detected and certs will be craft for them. Dont worry about anything and enjoy it.

