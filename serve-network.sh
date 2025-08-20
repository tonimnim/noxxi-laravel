#!/bin/bash

# Clear any cached config
php artisan config:clear
php artisan cache:clear

# Get local IP address
LOCAL_IP=$(ip addr show | grep -E "inet.*192\.|inet.*10\.|inet.*172\." | grep -v "127.0.0.1" | head -1 | awk '{print $2}' | cut -d'/' -f1)

echo "================================================"
echo "Starting Laravel API Server for Network Access"
echo "================================================"
echo ""
echo "Your API will be accessible at:"
echo "  http://$LOCAL_IP:8000"
echo ""
echo "Configure your Flutter app to use:"
echo "  Base URL: http://$LOCAL_IP:8000/api"
echo ""
echo "Example endpoints:"
echo "  Login:    http://$LOCAL_IP:8000/api/login"
echo "  Register: http://$LOCAL_IP:8000/api/register"
echo "  Events:   http://$LOCAL_IP:8000/api/events"
echo ""
echo "Press Ctrl+C to stop the server"
echo "================================================"
echo ""

# Start the server on all interfaces
php artisan serve --host=0.0.0.0 --port=8000