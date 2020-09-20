FROM debian:buster-slim

#### DEFINING VARS
ARG php_version=7.3



#### SYSTEM OPERATIONS
# Install basic packages
RUN apt-get update && apt-get install -y -qq --force-yes lsb-base nano certbot haproxy --no-install-recommends > /dev/null

# Install out automation friends: PHP
RUN apt-get install -y -qq --force-yes cron php${php_version}-cli php${php_version}-json --no-install-recommends > /dev/null



#### OPERATIONS
# Creating a temporary folder for our app
RUN mkdir -p /tmp/safe-haproxy

# Download the entire project
COPY . /tmp/safe-haproxy/

# Moving the app to the right place
RUN cp -r /tmp/safe-haproxy/build/* /root
RUN rm -rf /tmp/safe-haproxy

# Giving permissions to the executables
RUN chown root:root /root/*
RUN chmod +x /root/*.sh

# Schedule the renovation (set to daily)
RUN touch /var/spool/cron/crontabs/root 
RUN echo "0 4 * * * /root/renew.sh" >> /var/spool/cron/crontabs/root



#### RUNTIME SCRIPT
RUN rm -rf /init.sh && touch /init.sh
RUN echo "#!/bin/bash" >> /init.sh
RUN echo "/root/create.sh" >> /init.sh
RUN echo "/bin/bash" >> /init.sh
RUN chown root:root /init.sh
RUN chmod +x /init.sh
CMD /init.sh
