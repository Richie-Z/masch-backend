#!/bin/sh
set -e
mkdir -p /tmp/nginx
envsubst '${PORT} ${DOC_ROOT}' < /etc/nginx/nginx.conf.template > /tmp/nginx/nginx.conf
exec "$@"
