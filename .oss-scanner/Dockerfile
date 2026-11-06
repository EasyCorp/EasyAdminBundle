# Image used by Anthropic's OSS Scanner (https://github.com/anthropics/oss-scanner).
# It's built with network access; the scan that follows runs offline, so everything
# the tests need is installed here.
FROM php:8.4-cli-bookworm
ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates git unzip make \
    && rm -rf /var/lib/apt/lists/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.13.2/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath intl pdo_sqlite zip \
    && printf 'memory_limit=-1\n' > /usr/local/etc/php/conf.d/zz-easyadmin.ini
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# scanner contract: the checkout lives inside the image, at /src
COPY . /src
WORKDIR /src

# composer.lock is not versioned, so this resolves the latest allowed versions, as CI does
RUN composer update --no-interaction --prefer-dist --no-progress

# the test apps use SQLite, so no database server is needed; failing tests don't abort the build
RUN make tests || echo "WARNING: the tests reported failures"
