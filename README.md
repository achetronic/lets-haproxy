# Author

Hello, developer!

I am Alby Hernández (alias @achetronic) and have done several projects for the cloud in my life. Hope you like it.

There is a good article (not mine) explaining some principles about containers: https://developers.redhat.com/blog/2016/02/24/10-things-to-avoid-in-docker-containers/

# Bugs

If you find some bug or something that can be improved, please, feel free to contact me at me@achetronic.com

## Source code of the project
https://gitlab.com/achetronic/do-certbot

## Introduction
I made this image because I needed something light and quick working with DNS challenge 
in Digital Ocean cloud service. That type of challenge is the most simple and the fastest one getting 
Letsencrypt's certificates.

## Who want this
Useful for Docker Compose and Swarm users. With this image you can bind mount two volumes 
and automate the task of getting and renewing LE certificates in about 30 seconds.
This container is always running in background and tries to renew all certificates once a day in the night.
In the mounted volumes you can find only logs and certificates. No more hard work needed 
with Digital Ocean as provider

## How to use
1. Point your domain name to Digital Ocean's DNS servers
2. Generate an API token in your Digital Ocean account
3. Bind /var/log/letsencrypt for logs
4. Bind /etc/letsencrypt/live for certificates
5. Set environment variables
   * ADMIN_MAIL = admin@example.com
   * DO_TOKEN = "s3d5f13ds5f3s5..."
   * SKIP_CREATION = true | false
   * DOMAIN_CERT_1 = "domain.com"
   * DOMAIN_CERT_2 = "sub.domain.com"
   
     [...]

   * DOMAIN_CERT_n = "xxx.domain.com"


## Fast to go
```
docker run -it \
  --env ADMIN_MAIL=admin@example.com \
  --env DO_TOKEN=678b1f7e3bb30231... \
  --env DOMAIN_CERT_1=example.com \
  --env DOMAIN_CERT_2="*.example.com" \
  --env SKIP_CREATION=false \
  -v "$(pwd)"/letsencrypt/log:/var/log/letsencrypt/ \
  -v "$(pwd)"/letsencrypt/live:/etc/letsencrypt/live \
  achetronic/do-certbot:latest 
```

