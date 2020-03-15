FROM debian:buster



#### CERTIFICATE PRE-STEPS
# Install basic packages for scheduling jobs and installing python packages
RUN apt-get update && apt-get install -y -qq --force-yes nano cron python3-pip --no-install-recommends > /dev/null

# Install python packages to do the task
RUN pip3 install --upgrade setuptools > /dev/null
RUN pip3 install --upgrade wheel > /dev/null
RUN pip3 install --upgrade certbot-dns-digitalocean > /dev/null



#### CERTIFICATE OPERATIONS
# Copy the files for creation and renovation
COPY build/* /root/
RUN chown root:root /root/*.sh
RUN chmod +x /root/*.sh

# Schedule the renovation (set to daily)
touch /var/spool/cron/crontabs/root 
echo "0 4 * * * /root/renew.sh" >> /var/spool/cron/crontabs/root



#### RUNTIME SCRIPT
RUN rm -rf /init.sh && touch /init.sh
RUN echo "#!/bin/bash" >> /init.sh
RUN echo "/root/create.sh" >> /init.sh
RUN echo "/bin/bash" >> /init.sh
CMD /init.sh
