#!/bin/bash -xe

composer self-update
composer clear-cache

sudo rm -f /usr/local/bin/docker-compose
sudo curl -L -o /usr/local/bin/docker-compose https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)
sudo chmod +x /usr/local/bin/docker-compose
sudo service postgresql stop
sudo service mysql stop
