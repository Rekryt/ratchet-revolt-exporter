#!/bin/bash

docker-compose stop mysql
docker-compose rm -f mysql
docker volume rm ratchet_db
docker-compose build --no-cache mysql
docker-compose up -d --force-recreate mysql
