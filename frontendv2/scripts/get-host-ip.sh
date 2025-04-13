#!/bin/bash

# Get the host IP address that is accessible from the mobile device
# This will be used for the Expo QR code

# Try to get the IP address from various interfaces, prioritizing actual network interfaces over virtual ones
ip_address=$(ip a | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1' | grep -v '172.' | grep -v 'docker' | awk '{print $2}' | sed 's/addr://' | head -n 1)

# If we couldn't find a non-Docker, non-localhost IP, fall back to localhost
if [ -z "$ip_address" ]; then
  ip_address="localhost"
fi

echo "$ip_address"