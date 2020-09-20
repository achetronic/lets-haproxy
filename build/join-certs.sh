#!/bin/sh

CERTBOTPATH=""
LIVECERTSPATH=${CERTBOTPATH}"/etc/letsencrypt/live"
CERTSPATH=${CERTBOTPATH}"/etc/letsencrypt/haproxy"


# Create a folder for these certs
mkdir -p ${CERTSPATH}

#
#shopt -s globstar

for f in ${LIVECERTSPATH}/**; do

    # Check if this path is a directory
    if [ -d "$f" ]
    then

        # Look for right files
        if [[ -f $f/fullchain.pem ]] && [[ -f $f/privkey.pem ]]
        then
            echo "fullchain.pem and privkey.pem found on $f"
            echo "Joining them on ${CERTSPATH}/$(basename $f).pem"
            bash -c "cat $f/fullchain.pem $f/privkey.pem > ${CERTSPATH}/$(basename $f).pem"
        fi
    fi
done
