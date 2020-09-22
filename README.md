# Safe Haproxy

## Author
Hello, developer!

I am Alby Hernández (alias @achetronic) and have done several projects for the cloud in my life. Hope you like it.

There is a good article (not mine) explaining some principles about containers: https://developers.redhat.com/blog/2016/02/24/10-things-to-avoid-in-docker-containers/

## Bugs
If you find some bug or something that can be improved, please, feel free to contact me at me@achetronic.com

## Source code of the project
https://gitlab.com/achetronic/safe-haproxy

## Introduction
I made this image because of the need of having the Haproxy's ease and Let's Encrypt certs autoconfigured for my home server.
I looked for something similar and Traeffik's documentation is a hell, Haproxy's Certbot LUA plugin is quite good but no time 
for that and Nginx does not have plugins for that.

## Who want this
Useful for Docker Compose and Swarm users. With this image you can have a safe home reverse proxy in about 
5 minutes. Just set your domains or bindings for the frontend, set the backend servers, the balance strategy 
(roundrobin by default) and go.

## How to use
1. Point your domain name to Digital Ocean's DNS servers
2. Generate an API token in your Digital Ocean account
3. Bind /var/log/letsencrypt for logs
4. Bind /etc/letsencrypt/live for certificates
5. Set environment variables
   * ADMIN_MAIL    = admin@example.com
   * SKIP_CREATION = true | false
   * ENVIRONMENT   = production | staging


## Fast to go
```
docker run -it \
  --env ADMIN_MAIL=admin@example.com \
  --env SKIP_CREATION=false \
  --env ENVIRONMENT=staging \
  -v "./schema.json:/root/definition/schema.json" \
  -v "letsencrypt_logs:/var/log/letsencrypt" \
  -v "letsencrypt_data:/etc/letsencrypt" \
  achetronic/safe-haproxy:latest 

```

## schema.json file
As you can see, there is a file called schema.json that is mounted into the image. You need a file with your domains (or subdomains), backend IPs and the strategy for the load balancer

```
[
    {
        "domain"  : "your-domain.com",
        "servers" : [
            "192.168.0.2:8020",
            "192.168.0.3:8030"
        ],
        "balance" : "roundrobin"
    },
    {
        "domain"  : "other-your-domain.com",
        "servers" : [
            "199.162.1.4:8010",
            "200.164.0.3:8060"
        ],
        "balance" : "roundrobin"
    },
    {
        "bind"  : 90,
        "servers" : [
            "192.168.0.2:90",
            "192.168.0.2:100"
        ],
        "balance" : "roundrobin"
    }
]

```


