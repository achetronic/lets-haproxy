#!/bin/bash

#-------------------------------------------
# Tricky: Save original IFS for later needs
#-------------------------------------------
genuineIFS="$IFS"



#---------------------------------------------
# Read ENV vars given by the user
#---------------------------------------------
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



#--------------------------------------------------
# Check all ENV vars existance for mandatory ones
#--------------------------------------------------
# Check if the user want to continue the process
if [ -z "$SKIP_CREATION" ]; then
  echo "Certificate generation in process"
else
  # Check if admin want to skip creation
  if [ $(echo "$SKIP_CREATION" | tr '[:upper:]' '[:lower:]') == "true" ]; then
    echo "Skipping certificate creation"
    exit;
  fi
fi

# Check if any domain was given
if [ -z "$DOMAINS" ]; then
  echo "Error: No domains given."
  exit;
fi


# Check if email was given
if [ -z "$ADMIN_MAIL" ]; then
  echo "Error: No admin email given"
  exit;
fi



#---------------------------------------------
# Placeholder to store all parsed information
#---------------------------------------------
domains=()
declare -A backendServers
domainsString=""



#----------------------------------------------------------
# Loop over the domains given by the user spliting them
# by delimiter '@' taking the domain name and backend servers.
# Then, put domains together separated by commas
#----------------------------------------------------------
for domain in "${DOMAINS[@]}"
do
    readarray -d @ -t strarr <<< "$domain"
    domains+=(${strarr[0]})
    backendServers[${strarr[0]}]=${strarr[1]}
    domainsString=${strarr[0]}","${domainsString}
done

# Clean last comma of the string
domainsString="${domainsString:0:-1}"



#---------------------------------------------------------------
# Check if domains string is empty (trying to detect failures)
#---------------------------------------------------------------
if [ -z "$domainsString" ]; then
  echo "Error: Domains string is empty."
  exit;
fi



#------------------------------
# Ask for certificates
# -----------------------------




#---------------------------------------------
# Check for existance of ALL certificates
#---------------------------------------------
for domain in "${domains[@]}"
do
    certpath="/etc/letsencrypt/live/${domain}"
    if [[ ! -d $certpath ]] || [[ ! -f $certpath/fullchain.pem ]] || [[ ! -f $certpath/privkey.pem ]]
    then
        echo "Error: Cert for ${domain} not found"
        exit;
    fi
done



#---------------------------------------------
# Join certs for Haproxy
#---------------------------------------------
chmod +x /root/join-certs.sh
/root/join-certs.sh



#---------------------------------------------
# Check if they where joined successfully
#---------------------------------------------
for domain in "${domains[@]}"
do
    echo ${domain}
    certpath="/etc/letsencrypt/haproxy/${domain}.pem"
    if [[ ! -f $certpath ]]
    then
        echo "Error: Process failed when parsing cert for ${domain}"
        exit;
    fi
done



#-----------------------------------------------
# Try to join all the information to configure
# Haproxy
#-----------------------------------------------
