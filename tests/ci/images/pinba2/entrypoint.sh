#!/bin/sh

echo "[$(date)] Bootstrapping MySQL..."

clean_up() {
    # Perform program exit housekeeping
    echo "[$(date)] Stopping the service..."
    kill -s TERM "$pid"
    wait "$pid"
    echo "[$(date)] Exiting"
    exit
}

echo "[$(date)] Starting mysqld..."

trap clean_up TERM

mysqld -u mysql &
pid=$!

echo "[$(date)] Bootstrap finished"

tail -f /dev/null &
child=$!
wait "$child"
