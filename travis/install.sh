#!/bin/bash -xe
composer update --no-interaction --no-ansi --no-progress --no-suggest --optimize-autoloader --prefer-stable
phpenv config-add travis/coverage.ini
PHP_FPM_BIN="$HOME/.phpenv/versions/$PHP_VERSION/sbin/php-fpm"
DIR=$(realpath $(dirname "$0"))
USER=$(whoami)
PHP_VERSION=$(phpenv version-name)
ROOT=$(realpath "$DIR/..")
PORT=9000
SERVER="/tmp/php.sock"
function tmpl {
    sed \
        -e "s|{DIR}|$DIR|g" \
        -e "s|{USER}|$USER|g" \
        -e "s|{PHP_VERSION}|$PHP_VERSION|g" \
        -e "s|{ROOT}|$ROOT|g" \
        -e "s|{PORT}|$PORT|g" \
        -e "s|{SERVER}|$SERVER|g" \
        < $1 > $2
}
tmpl travis/php-fpm.tmpl.conf travis/php-fpm.conf
$PHP_FPM_BIN -c travis/php-fpm.conf
