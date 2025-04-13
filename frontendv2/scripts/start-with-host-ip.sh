#!/bin/bash

# This script detects the host IP and starts the Expo server with the correct IP
# This ensures that the QR code will work on mobile devices on the same network

# Get the host machine's IP address
HOST_IP=$(./scripts/get-host-ip.sh)
echo "Detected host IP: $HOST_IP"

# Store the host IP for the container to use
echo "$HOST_IP" > ./scripts/host-ip.txt

echo "Starting Expo with host IP: $HOST_IP"
echo "This IP will be used in the QR code for mobile devices"

# Export the HOST_IP as an environment variable for docker-compose
export HOST_IP

# Start the docker container
docker compose down
docker compose up