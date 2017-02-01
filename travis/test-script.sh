#!/bin/bash -xe

#!/bin/bash -xe

if [ "$RUN_LINTS" = "true" ] ; then
  bash -xe ./travis/static-analysis.sh
else
  bash -xe ./travis/unit-tests.sh
  bash -xe ./travis/test-deploy.sh
fi

# basic syntax check against all php files
find application -name '*.php' | xargs -n 1 php -l
phpcs -n --extensions=php --ignore=*/websystem/*,*/system/*,*/migrations/*,*/libraries/*,*/logs/*,*/third_party/* --standard=pacifica_php_ruleset.xml application/
