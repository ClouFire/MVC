#!/bin/sh
set -eu

mkdir -p /var/www/tmp
touch /var/www/tmp/error.log
chgrp www-data /var/www/tmp /var/www/tmp/error.log
chmod 2775 /var/www/tmp
chmod 0664 /var/www/tmp/error.log

exec docker-php-entrypoint "$@"
