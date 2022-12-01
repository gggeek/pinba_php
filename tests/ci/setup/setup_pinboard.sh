#!/bin/sh

set -e

git clone https://github.com/intaro/pinboard.git
cd pinboard
composer install

# @todo setup vhosts, create db schema
