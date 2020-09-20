FROM debian:buster



#### PRE-STEPS

# Install basic packages for scheduling jobs and installing python packages
RUN apt-get update && apt-get install -y -qq --force-yes nano cron python3-pip --no-install-recommends > /dev/null

# Install python packages to do the task
RUN pip3 install --upgrade setuptools > /dev/null
RUN pip3 install --upgrade wheel > /dev/null
RUN pip3 install --upgrade certbot-dns-digitalocean > /dev/null



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
