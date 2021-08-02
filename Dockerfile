# BASE STAGE
FROM php:8.0-cli-alpine as base

# Install base packages
RUN apk update && \
    apk add  \
        bash \
        procps \
        certbot \
        haproxy

# Configure entrypoint
COPY bin/docker-entrypoint.sh /usr/local/bin/
RUN chmod 0775 /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["lets", "watch"]

# BUILDING STAGE
FROM base as build

# Generate vendor directory
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY . /usr/src/app/
RUN cd /usr/src/app && \
    composer install --no-dev

# APPLICATION STAGE
FROM base as application

# Set the timezone
ENV TZ=UTC

# Copy the application
COPY . /usr/src/app/
COPY --from=build /usr/src/app/vendor /usr/src/app/vendor/

# Prepare executable permissions
RUN chmod -R 0775 /usr/src/app/bin

# Link init scripts
RUN ln -s /usr/src/app/bin/init.d/crond /etc/init.d/crond
RUN ln -s /usr/src/app/bin/init.d/haproxy /etc/init.d/haproxy
RUN chmod -R 0775 /etc/init.d

RUN ln -s /usr/src/app/bin/service /usr/local/bin/service && \
    chmod +x /usr/local/bin/service

# Link the lets CLI
RUN ln -s /usr/src/app/bin/lets.php /usr/local/bin/lets && \
    chmod +x /usr/local/bin/lets

WORKDIR /usr/src/app
