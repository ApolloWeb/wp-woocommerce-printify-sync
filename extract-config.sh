#!/bin/bash

# This script extracts Apache configuration files from the running container

mkdir -p ./apache-configs

# Get container ID
CONTAINER_ID=$(docker ps | grep wordpress | awk '{print $1}')

if [ -z "$CONTAINER_ID" ]; then
  echo "WordPress container not found. Make sure it's running."
  exit 1
fi

# Extract config files
echo "Extracting Apache configuration files..."
docker cp $CONTAINER_ID:/opt/bitnami/apache/conf/httpd.conf ./apache-configs/
docker cp $CONTAINER_ID:/opt/bitnami/apache/conf/vhosts ./apache-configs/

# Fix permissions
echo "Setting permissions..."
chmod -R 644 ./apache-configs/
find ./apache-configs/ -type d -exec chmod 755 {} \;

echo "Configuration files extracted to ./apache-configs/"
echo "You can now edit these files and selectively mount them in your docker-compose.yml"
