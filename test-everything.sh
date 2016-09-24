#!/bin/bash -xe

# basic syntax check against all php files
find application -name '*.php' | xargs -n 1 php -l
find application/config application/controllers -name '*.php' | xargs phpcs --standard=pacifica_php_ruleset.xml
