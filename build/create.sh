#!/bin/bash

#### ENVIRONMENT VARS
# Save natural status of IFS
genuineIFS="$IFS"

# Read the flag for skiping this step
SKIP_CREATION="$(printenv SKIP_CREATION)"

# Read domains from environment and sort them length-ascendant
DOMAINS="$(printenv | egrep -o 'DOMAIN_CERT_([0-9]+)' | xargs)"
IFS=' ' read -ra DOMAINS <<< "$DOMAINS"
DOMAINS="$(printenv  ${DOMAINS[@]} | xargs)"
IFS=' ' read -ra DOMAINS <<< "$DOMAINS"
IFS=$'\n' GLOBIGNORE='*' DOMAINS=($(printf '%s\n' ${DOMAINS[@]} | awk '{ print length($0) " " $0; }' | sort -n | cut -d ' ' -f 2-))

# Read admin mail address from environment
ADMIN_MAIL="$(printenv ADMIN_MAIL)"

# Read DigitalOcean token (env DO_TOKEN) from a file created from environment
chmod +x /root/dotini.sh && /root/dotini.sh

#### CHECKINGS
# Check if admin want to skip creation
if [ $(echo "$SKIP_CREATION" | tr '[:upper:]' '[:lower:]') == "true" ]; then
  echo "Skipping certificate creation"
  exit;
fi

# Check if any domain was given
if [ -z "$DOMAINS" ]; then
  echo "No domains given to Certbot"
  exit;
fi

# Check if email was given
if [ -z "$ADMIN_MAIL" ]; then
  echo "No admin email given to Certbot"
  exit;
fi

# Check if token was given
if [ -z "$DO_TOKEN" ]; then
  echo "No DigitalOcean API token given"
  exit;
fi

# Placeholder to store all domains together
domains=""

# put all domains together for certbot (separated by commas)
for domain in "${DOMAINS[@]}"
do
    domains+="$domain "
done
IFS=' ' read -ra domains <<< "$domains"
domains=$( IFS=, ; echo "${domains[*]}" )

# Check if domains string is empty (trying to detect failures)
if [ -z "$domains" ]; then
  echo "Domains could not be given to Certbot"
  exit;
fi

# Obtain certificate
certbot certonly \
  --dns-digitalocean \
  --dns-digitalocean-credentials /tmp/dot.ini \
  --server https://acme-staging-v02.api.letsencrypt.org/directory \
  -d "$domains" \
  -m $(echo "$ADMIN_MAIL") \
  --agree-tos \
  --expand \
  -n

