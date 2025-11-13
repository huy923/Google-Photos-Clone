#!/bin/bash

set -e
if command -v ip >/dev/null 2>&1; then
    IP=$(ip route get 1.1.1.1 2>/dev/null | awk '{print $7; exit}')
elif command -v hostname >/dev/null 2>&1; then
    IP=$(hostname -I 2>/dev/null | awk '{print $1}')
    if [ -z "$IP" ]; then
        IP=$(ipconfig getifaddr en0 2>/dev/null) # macOS WiFi
        if [ -z "$IP" ]; then
            IP=$(ipconfig getifaddr en1 2>/dev/null) # macOS Ethernet
        fi
    fi
fi

# Fallback to localhost if detection fails
if [ -z "$IP" ]; then
    echo "⚠️  Cannot detect IP address, using localhost"
    IP="127.0.0.1"
fi

case "$OSTYPE" in
  darwin*) 
    echo "Detected macOS"
    # macOS uses different sed syntax (-i '')
    sed -i '' "s|APP_URL=.*|APP_URL=http://$IP:8000|" backend/.env
    sed -i '' "s|DB_HOST=.*|DB_HOST=127.0.0.1|" backend/.env
    sed -i '' "s|DB_USERNAME=.*|DB_USERNAME=root|" backend/.env
    sed -i '' "s|DB_PASSWORD=.*|DB_PASSWORD=|" backend/.env
    sed -i '' "s|NEXT_PUBLIC_API_URL=.*|NEXT_PUBLIC_API_URL=http://$IP:8000|" frontend/.env.local
    ;;
    
  linux*) 
    echo "Detected Linux"
    sed -i "s|APP_URL=.*|APP_URL=http://$IP:8000|" backend/.env 
    sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" backend/.env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=root|" backend/.env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=|" backend/.env
    sed -i "s|NEXT_PUBLIC_API_URL=.*|NEXT_PUBLIC_API_URL=http://$IP:8000|" frontend/.env.local
    ;;

  msys*|cygwin*|mingw*) 
    echo "Detected Windows (Git Bash/Cygwin/MSYS)"
    # On Windows sed behaves like GNU sed (no need for '')
    sed -i "s|APP_URL=.*|APP_URL=http://$IP:8000|" backend/.env 
    sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" backend/.env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=root|" backend/.env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=|" backend/.env
    sed -i "s|NEXT_PUBLIC_API_URL=.*|NEXT_PUBLIC_API_URL=http://$IP:8000|" frontend/.env.local
    ;;

  *)
    echo "Unknown OS type: $OSTYPE"
    exit 1
    ;;
esac