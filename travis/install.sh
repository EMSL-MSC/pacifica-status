#!/bin/bash -xe
composer update --no-interaction --no-ansi --no-progress --no-suggest --optimize-autoloader --prefer-stable
phpenv config-add travis/coverage.ini
PHP_FPM_BIN="$HOME/.phpenv/versions/$PHP_VERSION/sbin/php-fpm"

