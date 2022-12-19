#!/bin/bash
docker container rm -f shop-web
docker container rm -f shop-db
docker-compose build
docker-compose up -d
./postgres/filldb.sh