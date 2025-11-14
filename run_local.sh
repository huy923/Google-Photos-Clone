#!/bin/bash

# Exit on error
set -e

# Install backend dependencies
if [ -d "backend/vendor" ]; then
    echo "✅ Backend dependencies already installed."
else
    echo "📦 Installing backend dependencies..."
    cd backend
    composer install
    cp .env.example .env
    php artisan key:generate`
    php artisan migrate --force
    php artisan storage:link || true
    cd ..
fi

# Install frontend dependencies
if [ -d "frontend/node_modules" ]; then
    echo "✅ Frontend dependencies already installed."
else
    echo "📦 Installing frontend dependencies..."
    cd frontend
    cp .env.example .env.local
    yarn install
    cd ..
fi

# Detect IP address safely across platforms
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

# Update configuration files
echo "🔧 Configuring with IP: $IP"

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

# Add additional environment variables for CORS
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s|FRONTEND_URL=.*|FRONTEND_URL=http://$IP:3000|" backend/.env || echo "FRONTEND_URL=http://$IP:3000" >> backend/.env
else
    sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=http://$IP:3000|" backend/.env || echo "FRONTEND_URL=http://$IP:3000" >> backend/.env
fi

echo ""
echo "✅ Configuration complete!"
echo "🚀 Your API URL is: http://$IP:8000"
echo "🌐 Open browser on same Wi-Fi: http://$IP:3000"
echo ""

# Kill any existing processes on ports 8000 and 3000
lsof -ti:8000 | xargs kill -9 2>/dev/null || true
lsof -ti:3000 | xargs kill -9 2>/dev/null || true

# Run backend server
cd backend
php artisan serve --host="$IP" --port=8000 &
BACKEND_PID=$!
echo "Backend PID: $BACKEND_PID"

# Wait for backend to start
sleep 3

# Run frontend server
cd ../frontend
yarn run dev &
FRONTEND_PID=$!
echo "Frontend PID: $FRONTEND_PID"

# Trap to kill both processes on exit
trap "kill $BACKEND_PID $FRONTEND_PID 2>/dev/null" EXIT

wait