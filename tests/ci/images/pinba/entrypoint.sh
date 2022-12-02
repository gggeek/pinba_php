#!/bin/sh

echo "[$(date)] Bootstrapping MySQL..."

clean_up() {
    # Perform program exit housekeeping
    echo "[$(date)] Stopping the service..."
    pkill --signal term mysqld
    echo "[$(date)] Exiting"
    exit
}

trap clean_up TERM

# cmd line from the original image...
/local/mysql/bin/mysqld --basedir=/local/mysql --datadir=/local/mysql/data --plugin-dir=/local/mysql/lib/plugin --user=mysql --log-error=/local/mysql/var/mysqld.log --pid-file=/local/mysql/data/mysqld.pid --socket=/local/mysql/var/mysql.sock

# wait until mysql is ready to accept connections over the network before saying bootstrap is finished
which mysqladmin 2>/dev/null
if [ $? -eq 0 ]; then
    while ! mysqladmin ping -h 127.0.0.1 --silent; do
        sleep 1
    done
fi

# add a mysql user we can use from other containers
mysql -h 127.0.0.1 -e "CREATE USER 'pinba'@'%' IDENTIFIED BY 'pinba'; GRANT ALL ON *.* TO 'pinba'@'%' WITH GRANT OPTION;"

echo "[$(date)] Bootstrap finished" | tee /var/run/bootstrap_ok

tail -f /dev/null &
child=$!
wait "$child"
