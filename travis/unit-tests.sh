#!/bin/bash -xe

docker-compose up -d
MAX_TRIES=60
HTTP_CODE=$(curl -sL -w "%{http_code}\\n" localhost:8121/keys -o /dev/null || true)
while [[ $HTTP_CODE != 200 && $MAX_TRIES > 0 ]] ; do
  sleep 1
  HTTP_CODE=$(curl -sL -w "%{http_code}\\n" localhost:8121/keys -o /dev/null || true)
  MAX_TRIES=$(( MAX_TRIES - 1 ))
done
docker run -it --rm --net=pacificauploadstatus_default -e METADATA_URL=http://metadataserver:8121 -e PYTHONPATH=/usr/src/app pacifica/metadata python test_files/loadit.py
docker-compose stop uploadstatus
echo "doing unit tests"
cp vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/phpunit_coverage.php .
./vendor/bin/phpunit --coverage-text tests
cat /tmp/selenium-server.log
HTTP_CODE=$(curl -sL -w "%{http_code}\\n" -u dmlb2001:1234 localhost:8192/status_api/overview -o /dev/null || true)
if [[ $HTTP_CODE != 200 ]] ; then
  curl -u dmlb2001:1234 localhost:8192/status_api/overview
  cat travis/error.log || true
  cat travis/php-error.log || true
  cat travis/php-fpm.conf
  exit -1
fi
