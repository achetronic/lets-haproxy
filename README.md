
## Introduction
I made this image because I needed something lighter and quicker than
Cert-manager working just with DNS challenge in Digital Ocean because DNS challenge 
is the most simple and the fastest one taking Letsencrypt's certificates.

## How to use
1. Point your domain name to Digital Ocean's DNS servers
2. Generate an API token in your Digital Ocean account
3. Just take this image from repository (in the future this will be public)
4. Bind /var/log/letsencrypt for logs
5. Bind /etc/letsencrypt/live for certificates
6. Set environment variables
   * ADMIN_MAIL = admin@example.com
   * DO_TOKEN = "s3d5f13ds5f3s5..."
   * SKIP_CREATION = true | false
   * DOMAIN_CERT_1 = "domain.com"
   * DOMAIN_CERT_2 = "sub.domain.com"
     [...]
   * DOMAIN_CERT_n = "xxx.domain.com"