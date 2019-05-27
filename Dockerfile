ARG COMPOSER_FLAGS="--no-interaction --no-suggest --no-progress --ansi"

###### base stage ######
FROM php:7.3-fpm-alpine as base

ARG COMPOSER_FLAGS
ARG COMPOSER_VERSION="1.8.5"
ARG PHP_FPM_HEALTHCHECK_VERSION="v0.2.0"
ARG WAIT_FOR_IT_VERSION="9995b721327eac7a88f0dce314ea074d5169634f"

# global dependencies
RUN apk add --no-cache bash fcgi postgresql-dev

# php extensions
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) pdo_pgsql \
    && apk del .phpize-deps

# local dependencies
RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION && \
    curl -fsSL https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/$PHP_FPM_HEALTHCHECK_VERSION/php-fpm-healthcheck \
         -o /usr/local/bin/php-fpm-healthcheck && chmod +x /usr/local/bin/php-fpm-healthcheck && \
    curl -fsSL https://raw.githubusercontent.com/vishnubob/wait-for-it/$WAIT_FOR_IT_VERSION/wait-for-it.sh \
         -o /usr/local/bin/wait-for && chmod +x /usr/local/bin/wait-for

# setup user
WORKDIR /app
ARG APP_UID=1000
ARG APP_GID=1000
RUN addgroup -g $APP_GID app && adduser -D -G app -u $APP_UID app && chown app:app .
USER app

# environment
ENV HOME /home/app
ENV PATH ${PATH}:${HOME}/.composer/vendor/bin:${HOME}/bin:/app/vendor/bin:/app/bin

# global composer dependencies
RUN composer global require hirak/prestissimo $COMPOSER_FLAGS

# custom php config
COPY infra/php/php.ini /usr/local/etc/php/
COPY infra/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

###### dev stage ######
FROM base as dev

ARG COMPOSER_FLAGS
ARG PHP_CS_FIXER_VERSION="v2.15.0"
ARG PHPSTAN_VERSION="0.11.6"
ARG COMPOSER_REQUIRE_CHECKER_VERSION="2.0.0"
ARG XDEBUG_ENABLER_VERSION="facd52cdc1a09fe7e82d6188bb575ed54ab2bc72"
ARG XDEBUG_VERSION="2.7.2"

# we need privileges to install dev tools
USER root

# php extensions
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install xdebug-$XDEBUG_VERSION \
    && apk del .phpize-deps

# global development deps
RUN apk add --no-cache postgresql-client && \
    curl -fsSL https://gist.githubusercontent.com/stefanotorresi/9f48f8c476b17c44d68535630522a2be/raw/$XDEBUG_ENABLER_VERSION/xdebug \
        -o /usr/local/bin/xdebug && chmod +x /usr/local/bin/xdebug

# re-drop privileges
USER app

# global composer dependencies
RUN composer global require \
      friendsofphp/php-cs-fixer:$PHP_CS_FIXER_VERSION \
      phpstan/phpstan:$PHPSTAN_VERSION \
      phpstan/phpstan-beberlei-assert \
      phpstan/phpstan-phpunit \
      maglnet/composer-require-checker:$COMPOSER_REQUIRE_CHECKER_VERSION

# project composer dependencies
COPY --chown=app:app composer.* ./
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader

# copy project sources
COPY --chown=app:app . ./

# rerun composer to trigger scripts and dump the autoloader
RUN composer install $COMPOSER_FLAGS


###### production stage ######
FROM base

ARG COMPOSER_FLAGS

# project composer dependencies
COPY --chown=app:app composer.* ./
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader --no-dev

# copy project sources cherry picking only production files
COPY --chown=app:app index.php ./
COPY --chown=app:app src ./src

# rerun composer to trigger scripts and dump the autoloader
RUN composer install $COMPOSER_FLAGS --no-dev --optimize-autoloader

HEALTHCHECK --interval=30s --timeout=2s CMD php-fpm-healthcheck
