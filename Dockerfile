FROM debian:buster-slim

#### DEFINING VARS
ARG php_version=7.3



#### SYSTEM OPERATIONS
# Install basic packages
RUN apt-get update && apt-get install -y -qq --force-yes lsb-base nano certbot haproxy --no-install-recommends > /dev/null

# Install out automation friends: PHP
RUN apt-get install -y -qq --force-yes cron php${php_version}-cli php${php_version}-xml --no-install-recommends > /dev/null

# Installing temporary packages
RUN apt-get install -y -qq --force-yes composer git zip unzip php${php_version}-zip --no-install-recommends > /dev/null



#### OPERATIONS
# Creating a temporary folder for our app
RUN mkdir -p /tmp/safe-haproxy

# Download the entire project
COPY . /tmp/safe-haproxy/

# Defining which packages Composer will install
RUN cp /tmp/safe-haproxy/build/composer.lock /root/composer.lock
RUN cp /tmp/safe-haproxy/build/composer.json /root/composer.json

# Please, Composer, install them
RUN composer install -d /root --no-dev --no-scripts

# Moving the app to the right place
RUN cp -r /tmp/safe-haproxy/build/* /root
RUN rm -rf /tmp/safe-haproxy

# Deleting system temporary packages
RUN apt-get purge -y -qq --force-yes composer git zip unzip php${php_version}-zip > /dev/null

# Cleaning the system
RUN apt-get -y -qq --force-yes autoremove > /dev/null

# Giving permissions to the executables
RUN chown root:root /root/*
RUN find /root -type f -exec chmod 644 {} \;
RUN find /root -type d -exec chmod 755 {} \;
RUN chmod +x /root/*



# ENTRYPOINT
RUN rm -rf /entrypoint.sh && touch /entrypoint.sh
RUN echo "#!/bin/bash" >> /entrypoint.sh
RUN echo "service cron start" >> /entrypoint.sh
RUN echo "(crontab -l; echo '0 4 * * * php /root/renew-certs.php >> /dev/null 2>&1';) | crontab -" >> /entrypoint.sh
RUN echo "touch /etc/crontab /etc/cron.*/*" >> /entrypoint.sh
RUN echo 'exec "$@"' >> /entrypoint.sh

RUN chown root:root /entrypoint.sh
RUN chmod +x /entrypoint.sh

# CMD
RUN rm -rf /init.sh && touch /init.sh
RUN echo "#!/bin/bash" >> /init.sh
RUN echo "php /root/create-certs.php" >> /init.sh
RUN echo "/bin/bash" >> /init.sh

RUN chown root:root /init.sh
RUN chmod +x /init.sh

# GAINING COMFORT
WORKDIR "/root"

# EXECUTING START SCRIPT
ENTRYPOINT ["/entrypoint.sh"]
CMD /init.sh
