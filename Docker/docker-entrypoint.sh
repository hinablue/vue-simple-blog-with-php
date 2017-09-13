#!/bin/bash

if [ ! -d "/var/www/vue-blog" ]; then
	mkdir -p -m 0755 /var/www/vue-blog
	mkdir -p -m 0755 /var/www/vue-blog/api
	mkdir -p -m 0755 /var/www/vue-blog/public
fi;

if [ ! -d "/var/run/php" ]; then
	mkdir -p -m 0755 /var/run/php
fi;

exec "$@"