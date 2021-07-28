#!/usr/bin/env bash
set -e

CRONTAB_PATH="/tmp/crontab"

# Start services
service crond start >/dev/null;
service haproxy start >/dev/null;

# Schedule certificates renewal
echo "30 4 * * * lets renew" >> "${CRONTAB_PATH}" 2>&1;
crontab "${CRONTAB_PATH}";

# Create certificates
(sleep 10 && lets create >/dev/null) &

"$@"
