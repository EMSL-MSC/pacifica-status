#!/bin/bash -xe

echo "doing unit tests"
./vendor/bin/phpunit --coverage-text
