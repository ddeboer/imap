FROM php:8.4

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions @composer iconv imap pcov

ADD --chmod=0755 https://github.com/ergebnis/composer-normalize/releases/latest/download/composer-normalize.phar /usr/local/bin/composer-normalize
