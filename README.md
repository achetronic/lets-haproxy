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
   * ADMIN_MAIL = admin@example.com
   * SKIP_CREATION = true | false


## Fast to go
```

```

