#!/bin/bash -xe

# basic syntax check against all php files
find application -name '*.php' | xargs -n 1 php -l
phpcs -n --extensions=php --ignore=*/websystem/*,*/system/*,*/migrations/*,*/libraries/*,*/logs/* --standard=myemsl_ruleset.xml application/
