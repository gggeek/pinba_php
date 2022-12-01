#!/bin/sh

set -e

# @todo test also anchorfree/pinba2
docker pull tony2001/pinba:latest
docker run tony2001/pinba &
