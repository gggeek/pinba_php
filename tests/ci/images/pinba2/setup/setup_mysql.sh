#!/bin/bash

set -e

# Let the original code set up mysql, but not leave it running
sed -i '$ d' /usr/local/bin/docker-entrypoint.sh

if fgrep -q MYSQL_INIT_PATH /usr/local/bin/docker-entrypoint.sh >/dev/null 2>/dev/null; then
    export MYSQL_INIT_PATH=/root/build/mysql_init.sql
    /usr/local/bin/docker-entrypoint.sh mysqld
else
    /usr/local/bin/docker-entrypoint.sh mysqld

    # Now add our own stuff: start mysql again
    mysqld --skip-networking -umysql &
    # legen.... wait for it (original comment ;-)
    for i in {10..0}; do
        if echo 'SELECT 1' | mysql &>/dev/null; then
            break
        fi
        #echo 'MySQL init process in progress...'
        sleep 1
    done
    if [ "$i" = 0 ]; then
        echo >&2 'MySQL init process failed.'
        exit 1
    fi

    # Execute extra sql
    mysql --protocol=socket -uroot < /root/build/mysql_init.sql

    # Shut it down
    pkill --signal term mysqld
fi
