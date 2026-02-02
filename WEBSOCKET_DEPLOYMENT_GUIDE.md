# WebSocket Server Deployment Guide

This guide covers deploying and managing the WebSocket server for real-time auction updates.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation](#installation)
4. [Running the WebSocket Server](#running-the-websocket-server)
5. [Process Management](#process-management)
6. [Firewall Configuration](#firewall-configuration)
7. [Monitoring and Logging](#monitoring-and-logging)
8. [Troubleshooting](#troubleshooting)
9. [Security Considerations](#security-considerations)

---

## Overview

The WebSocket server provides real-time updates for:
- New bid notifications
- Outbid alerts
- Auction ending countdowns
- Auction completion notifications

**Technology:** Ratchet PHP WebSocket library  
**Default Port:** 8080 (configurable)  
**Authentication:** JWT tokens from main API

---

## Prerequisites

### System Requirements

- PHP 8.1 or higher
- Composer
- MySQL database (shared with main API)
- Open port for WebSocket connections (default: 8080)

### PHP Extensions

Ensure these extensions are installed:
```bash
php -m | grep -E 'sockets|pcntl|posix'
```

If missing, install them:
```bash
# Ubuntu/Debian
sudo apt-get install php8.1-sockets

# CentOS/RHEL
sudo yum install php-sockets
```

### Composer Dependencies

The WebSocket server requires Ratchet PHP:
```bash
composer require cboden/ratchet
```

---

## Installation

### 1. Clone and Setup

```bash
# Navigate to project directory
cd /path/to/auction-portal

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure environment variables
nano .env
```

### 2. Configure Environment Variables

Edit `.env` and set WebSocket configuration:

```bash
# WebSocket Configuration
WS_PORT=8080          # Port for WebSocket server
WS_HOST=0.0.0.0       # Listen on all interfaces
```

### 3. Create WebSocket Server Script

Ensure `bin/websocket-server.php` exists with proper permissions:

```bash
chmod +x bin/websocket-server.php
```

---

## Running the WebSocket Server

### Development Mode

For development and testing, run the server directly:

```bash
php bin/websocket-server.php
```

You should see:
```
WebSocket server started on 0.0.0.0:8080
Waiting for connections...
```

**Note:** This runs in the foreground. Press `Ctrl+C` to stop.

### Background Mode

To run in the background:

```bash
nohup php bin/websocket-server.php > websocket.log 2>&1 &
```

To stop:
```bash
# Find the process ID
ps aux | grep websocket-server

# Kill the process
kill <PID>
```

---

## Process Management

For production environments, use a process manager to ensure the WebSocket server stays running.

### Option 1: Systemd (Recommended for Linux)

Create a systemd service file:

```bash
sudo nano /etc/systemd/system/auction-websocket.service
```

Add the following content:

```ini
[Unit]
Description=Auction Portal WebSocket Server
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/auction-portal
ExecStart=/usr/bin/php /var/www/auction-portal/bin/websocket-server.php
Restart=always
RestartSec=10
StandardOutput=append:/var/log/auction-websocket.log
StandardError=append:/var/log/auction-websocket-error.log

[Install]
WantedBy=multi-user.target
```

**Important:** Update paths to match your installation directory.

Enable and start the service:

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable auction-websocket

# Start the service
sudo systemctl start auction-websocket

# Check status
sudo systemctl status auction-websocket
```

Manage the service:

```bash
# Stop the service
sudo systemctl stop auction-websocket

# Restart the service
sudo systemctl restart auction-websocket

# View logs
sudo journalctl -u auction-websocket -f
```

### Option 2: Supervisor

Install Supervisor:

```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

Create configuration file:

```bash
sudo nano /etc/supervisor/conf.d/auction-websocket.conf
```

Add the following:

```ini
[program:auction-websocket]
command=/usr/bin/php /var/www/auction-portal/bin/websocket-server.php
directory=/var/www/auction-portal
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/auction-websocket.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
```

Start the service:

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start the service
sudo supervisorctl start auction-websocket

# Check status
sudo supervisorctl status auction-websocket
```

Manage the service:

```bash
# Stop
sudo supervisorctl stop auction-websocket

# Restart
sudo supervisorctl restart auction-websocket

# View logs
sudo tail -f /var/log/auction-websocket.log
```

### Option 3: PM2 (Node.js Process Manager)

Install PM2:

```bash
npm install -g pm2
```

Create PM2 ecosystem file:

```bash
nano ecosystem.config.js
```

Add:

```javascript
module.exports = {
  apps: [{
    name: 'auction-websocket',
    script: '/usr/bin/php',
    args: 'bin/websocket-server.php',
    cwd: '/var/www/auction-portal',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '500M',
    error_file: './logs/websocket-error.log',
    out_file: './logs/websocket-out.log',
    log_file: './logs/websocket-combined.log',
    time: true
  }]
};
```

Start with PM2:

```bash
# Start the service
pm2 start ecosystem.config.js

# Save configuration
pm2 save

# Setup startup script
pm2 startup

# View status
pm2 status

# View logs
pm2 logs auction-websocket
```

---

## Firewall Configuration

### UFW (Ubuntu/Debian)

```bash
# Allow WebSocket port
sudo ufw allow 8080/tcp

# Reload firewall
sudo ufw reload

# Check status
sudo ufw status
```

### Firewalld (CentOS/RHEL)

```bash
# Allow WebSocket port
sudo firewall-cmd --permanent --add-port=8080/tcp

# Reload firewall
sudo firewall-cmd --reload

# Check status
sudo firewall-cmd --list-ports
```

### iptables

```bash
# Allow WebSocket port
sudo iptables -A INPUT -p tcp --dport 8080 -j ACCEPT

# Save rules
sudo iptables-save > /etc/iptables/rules.v4
```

### Cloud Provider Firewalls

**AWS Security Groups:**
- Add inbound rule: TCP port 8080 from 0.0.0.0/0 (or specific IPs)

**Google Cloud Firewall:**
```bash
gcloud compute firewall-rules create allow-websocket \
  --allow tcp:8080 \
  --source-ranges 0.0.0.0/0
```

**Azure Network Security Group:**
- Add inbound security rule: TCP port 8080

---

## Monitoring and Logging

### Log Files

**Systemd:**
```bash
# View real-time logs
sudo journalctl -u auction-websocket -f

# View last 100 lines
sudo journalctl -u auction-websocket -n 100

# View logs from today
sudo journalctl -u auction-websocket --since today
```

**Supervisor:**
```bash
# View logs
sudo tail -f /var/log/auction-websocket.log

# View error logs
sudo tail -f /var/log/auction-websocket-error.log
```

**PM2:**
```bash
# View all logs
pm2 logs auction-websocket

# View only errors
pm2 logs auction-websocket --err

# Clear logs
pm2 flush
```

### Health Monitoring

Create a monitoring script:

```bash
nano monitor-websocket.sh
```

Add:

```bash
#!/bin/bash

# Check if WebSocket server is running
if ! pgrep -f "websocket-server.php" > /dev/null; then
    echo "WebSocket server is not running!"
    # Restart the service
    systemctl restart auction-websocket
    # Send alert (optional)
    # mail -s "WebSocket Server Down" admin@example.com < /dev/null
fi
```

Make executable and add to cron:

```bash
chmod +x monitor-websocket.sh

# Add to crontab (check every 5 minutes)
crontab -e
*/5 * * * * /path/to/monitor-websocket.sh
```

### Connection Monitoring

Test WebSocket connection:

```bash
# Install wscat
npm install -g wscat

# Test connection
wscat -c ws://localhost:8080?token=YOUR_JWT_TOKEN
```

### Performance Monitoring

Monitor resource usage:

```bash
# CPU and memory usage
ps aux | grep websocket-server

# Detailed process info
top -p $(pgrep -f websocket-server.php)

# Network connections
netstat -an | grep 8080
```

---

## Troubleshooting

### Server Won't Start

**Check port availability:**
```bash
# Check if port is in use
sudo lsof -i :8080

# Kill process using the port
sudo kill -9 <PID>
```

**Check PHP extensions:**
```bash
php -m | grep sockets
```

**Check permissions:**
```bash
# Ensure script is executable
chmod +x bin/websocket-server.php

# Check file ownership
ls -la bin/websocket-server.php
```

### Connection Issues

**Test local connection:**
```bash
telnet localhost 8080
```

**Check firewall:**
```bash
# UFW
sudo ufw status

# Firewalld
sudo firewall-cmd --list-ports

# iptables
sudo iptables -L -n
```

**Verify WebSocket URL:**
- Ensure clients connect to correct host and port
- Check if using `ws://` (not `wss://` unless SSL configured)

### Authentication Failures

**Check JWT token:**
```bash
# Decode JWT token (use online tool or jwt-cli)
# Verify token hasn't expired
# Ensure JWT_SECRET matches between API and WebSocket server
```

**Check database connection:**
```bash
# Test database connectivity
php -r "new PDO('mysql:host=localhost;dbname=auction_portal', 'root', '');"
```

### High Memory Usage

**Restart service periodically:**

Add to crontab:
```bash
# Restart every day at 3 AM
0 3 * * * systemctl restart auction-websocket
```

**Limit memory in systemd:**

Edit service file:
```ini
[Service]
MemoryLimit=500M
```

### Connection Drops

**Check server logs:**
```bash
sudo journalctl -u auction-websocket -f
```

**Implement client-side reconnection:**

```javascript
function connectWebSocket() {
  const ws = new WebSocket('ws://localhost:8080?token=' + token);
  
  ws.onclose = () => {
    console.log('Connection lost. Reconnecting in 5 seconds...');
    setTimeout(connectWebSocket, 5000);
  };
  
  return ws;
}
```

---

## Security Considerations

### 1. JWT Token Security

- Use strong `JWT_SECRET` (minimum 32 characters)
- Set appropriate token expiration time
- Validate tokens on every WebSocket message

### 2. Rate Limiting

Implement connection rate limiting to prevent abuse:

```php
// In WebSocket server
private $connectionAttempts = [];

public function onOpen(ConnectionInterface $conn) {
    $ip = $conn->remoteAddress;
    
    // Check rate limit
    if ($this->isRateLimited($ip)) {
        $conn->close();
        return;
    }
    
    // Continue with authentication...
}
```

### 3. SSL/TLS (WSS)

For production, use secure WebSocket (WSS):

**Option 1: Nginx Reverse Proxy**

```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location /ws {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

**Option 2: Apache Reverse Proxy**

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    ProxyPass /ws ws://localhost:8080/
    ProxyPassReverse /ws ws://localhost:8080/
    
    RewriteEngine on
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://localhost:8080/$1" [P,L]
</VirtualHost>
```

### 4. Network Security

- Bind to specific IP if not using reverse proxy
- Use firewall rules to restrict access
- Monitor for suspicious connection patterns

### 5. Input Validation

Always validate client messages:

```php
public function onMessage(ConnectionInterface $from, $msg) {
    $data = json_decode($msg, true);
    
    // Validate message structure
    if (!isset($data['action']) || !isset($data['itemId'])) {
        $from->send(json_encode(['type' => 'error', 'message' => 'Invalid message']));
        return;
    }
    
    // Validate itemId is numeric
    if (!is_numeric($data['itemId'])) {
        $from->send(json_encode(['type' => 'error', 'message' => 'Invalid item ID']));
        return;
    }
    
    // Continue processing...
}
```

---

## Production Checklist

Before deploying to production:

- [ ] Change `JWT_SECRET` to a strong random value
- [ ] Configure process manager (systemd/supervisor/PM2)
- [ ] Set up firewall rules
- [ ] Configure SSL/TLS (WSS) via reverse proxy
- [ ] Set up log rotation
- [ ] Configure monitoring and alerts
- [ ] Test connection from client application
- [ ] Test reconnection logic
- [ ] Load test with expected concurrent users
- [ ] Document rollback procedure
- [ ] Set up backup WebSocket server (optional)

---

## Additional Resources

- [Ratchet PHP Documentation](http://socketo.me/)
- [WebSocket Protocol RFC](https://tools.ietf.org/html/rfc6455)
- [Systemd Service Documentation](https://www.freedesktop.org/software/systemd/man/systemd.service.html)
- [Supervisor Documentation](http://supervisord.org/)
- [PM2 Documentation](https://pm2.keymetrics.io/)

---

**Last Updated:** February 1, 2026  
**Version:** 1.0.0
