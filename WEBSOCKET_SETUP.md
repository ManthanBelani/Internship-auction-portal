# WebSocket Server Setup Guide

## Overview
This guide explains how to set up a WebSocket server for real-time bid updates and notifications in the BidOrbit auction app.

---

## Option 1: PHP Ratchet WebSocket Server (Recommended)

### Prerequisites
```bash
composer require cboden/ratchet
```

### Create WebSocket Server

Create `websocket_server.php` in your project root:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class AuctionWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        $type = $data['type'] ?? null;

        switch ($type) {
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;
            case 'unsubscribe':
                $this->handleUnsubscribe($from, $data);
                break;
            case 'ping':
                $from->send(json_encode(['type' => 'pong']));
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->removeSubscriptions($conn);
        echo "Connection closed: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleSubscribe(ConnectionInterface $conn, array $data) {
        $channel = $data['channel'] ?? null;
        $itemId = $data['itemId'] ?? null;

        if ($channel === 'item' && $itemId) {
            $key = "item_{$itemId}";
            if (!isset($this->subscriptions[$key])) {
                $this->subscriptions[$key] = new \SplObjectStorage;
            }
            $this->subscriptions[$key]->attach($conn);
            echo "Client {$conn->resourceId} subscribed to item {$itemId}\n";
        } elseif ($channel === 'notifications') {
            $userId = $data['userId'] ?? $conn->resourceId;
            $key = "notifications_{$userId}";
            if (!isset($this->subscriptions[$key])) {
                $this->subscriptions[$key] = new \SplObjectStorage;
            }
            $this->subscriptions[$key]->attach($conn);
            echo "Client {$conn->resourceId} subscribed to notifications\n";
        }
    }

    private function handleUnsubscribe(ConnectionInterface $conn, array $data) {
        $channel = $data['channel'] ?? null;
        $itemId = $data['itemId'] ?? null;

        if ($channel === 'item' && $itemId) {
            $key = "item_{$itemId}";
            if (isset($this->subscriptions[$key])) {
                $this->subscriptions[$key]->detach($conn);
            }
        }
    }

    private function removeSubscriptions(ConnectionInterface $conn) {
        foreach ($this->subscriptions as $key => $clients) {
            if ($clients->contains($conn)) {
                $clients->detach($conn);
            }
        }
    }

    // Public method to broadcast bid updates
    public function broadcastBidUpdate($itemId, $bidData) {
        $key = "item_{$itemId}";
        if (isset($this->subscriptions[$key])) {
            $message = json_encode([
                'type' => 'bid_update',
                'itemId' => $itemId,
                'amount' => $bidData['amount'],
                'bidderId' => $bidData['bidderId'],
                'bidderName' => $bidData['bidderName'] ?? 'Anonymous',
                'timestamp' => date('c'),
            ]);

            foreach ($this->subscriptions[$key] as $client) {
                $client->send($message);
            }
        }
    }

    // Public method to broadcast notifications
    public function broadcastNotification($userId, $notificationData) {
        $key = "notifications_{$userId}";
        if (isset($this->subscriptions[$key])) {
            $message = json_encode([
                'type' => 'notification',
                'id' => $notificationData['id'],
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'itemId' => $notificationData['itemId'] ?? null,
                'notificationType' => $notificationData['type'],
                'isRead' => false,
                'timestamp' => date('c'),
            ]);

            foreach ($this->subscriptions[$key] as $client) {
                $client->send($message);
            }
        }
    }
}

// Start WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new AuctionWebSocket()
        )
    ),
    8081 // WebSocket port
);

echo "WebSocket server started on port 8081\n";
$server->run();
```

### Run WebSocket Server

```bash
php websocket_server.php
```

Keep this running in a separate terminal.

---

## Option 2: Node.js WebSocket Server (Alternative)

If you prefer Node.js, create `websocket_server.js`:

```javascript
const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 8081 });

const subscriptions = new Map();

wss.on('connection', (ws) => {
    console.log('New client connected');

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            
            switch (data.type) {
                case 'subscribe':
                    handleSubscribe(ws, data);
                    break;
                case 'unsubscribe':
                    handleUnsubscribe(ws, data);
                    break;
                case 'ping':
                    ws.send(JSON.stringify({ type: 'pong' }));
                    break;
            }
        } catch (error) {
            console.error('Error parsing message:', error);
        }
    });

    ws.on('close', () => {
        console.log('Client disconnected');
        removeSubscriptions(ws);
    });
});

function handleSubscribe(ws, data) {
    const { channel, itemId, userId } = data;
    
    if (channel === 'item' && itemId) {
        const key = `item_${itemId}`;
        if (!subscriptions.has(key)) {
            subscriptions.set(key, new Set());
        }
        subscriptions.get(key).add(ws);
        console.log(`Client subscribed to item ${itemId}`);
    } else if (channel === 'notifications') {
        const key = `notifications_${userId}`;
        if (!subscriptions.has(key)) {
            subscriptions.set(key, new Set());
        }
        subscriptions.get(key).add(ws);
        console.log(`Client subscribed to notifications`);
    }
}

function handleUnsubscribe(ws, data) {
    const { channel, itemId } = data;
    
    if (channel === 'item' && itemId) {
        const key = `item_${itemId}`;
        if (subscriptions.has(key)) {
            subscriptions.get(key).delete(ws);
        }
    }
}

function removeSubscriptions(ws) {
    subscriptions.forEach((clients, key) => {
        clients.delete(ws);
    });
}

// Broadcast bid update
function broadcastBidUpdate(itemId, bidData) {
    const key = `item_${itemId}`;
    if (subscriptions.has(key)) {
        const message = JSON.stringify({
            type: 'bid_update',
            itemId,
            amount: bidData.amount,
            bidderId: bidData.bidderId,
            bidderName: bidData.bidderName || 'Anonymous',
            timestamp: new Date().toISOString(),
        });

        subscriptions.get(key).forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    }
}

// Broadcast notification
function broadcastNotification(userId, notificationData) {
    const key = `notifications_${userId}`;
    if (subscriptions.has(key)) {
        const message = JSON.stringify({
            type: 'notification',
            ...notificationData,
            timestamp: new Date().toISOString(),
        });

        subscriptions.get(key).forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    }
}

console.log('WebSocket server started on port 8081');

// Export for use in other modules
module.exports = { broadcastBidUpdate, broadcastNotification };
```

### Install Dependencies

```bash
npm install ws
```

### Run Server

```bash
node websocket_server.js
```

---

## Integration with Backend

### Trigger WebSocket Updates from PHP Backend

When a bid is placed, trigger WebSocket update:

```php
// In BidController.php after successful bid
public function create(array $data): void {
    // ... existing bid creation code ...
    
    // Trigger WebSocket update
    $this->triggerWebSocketUpdate($itemId, [
        'amount' => $amount,
        'bidderId' => $user['userId'],
        'bidderName' => $user['name'],
    ]);
}

private function triggerWebSocketUpdate($itemId, $bidData) {
    // Option 1: HTTP request to WebSocket server
    $ch = curl_init('http://localhost:8081/broadcast');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'type' => 'bid_update',
        'itemId' => $itemId,
        'data' => $bidData,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    
    // Option 2: Redis pub/sub (if using Redis)
    // $redis = new Redis();
    // $redis->connect('127.0.0.1', 6379);
    // $redis->publish('bid_updates', json_encode([
    //     'itemId' => $itemId,
    //     'data' => $bidData,
    // ]));
}
```

---

## Testing WebSocket Connection

### Test with Browser Console

```javascript
const ws = new WebSocket('ws://10.205.162.238:8081');

ws.onopen = () => {
    console.log('Connected');
    
    // Subscribe to item updates
    ws.send(JSON.stringify({
        type: 'subscribe',
        channel: 'item',
        itemId: 1
    }));
};

ws.onmessage = (event) => {
    console.log('Message:', JSON.parse(event.data));
};

ws.onerror = (error) => {
    console.error('Error:', error);
};
```

### Test with Flutter App

1. Start WebSocket server
2. Run Flutter app
3. Navigate to item details
4. Place a bid from another device/browser
5. Watch real-time update in the app

---

## Troubleshooting

### Connection Refused
- Make sure WebSocket server is running
- Check firewall settings
- Verify port 8081 is not blocked

### No Updates Received
- Check if client is subscribed to correct channel
- Verify WebSocket server is broadcasting messages
- Check browser/app console for errors

### Frequent Disconnections
- Implement heartbeat/ping-pong mechanism (already included)
- Check network stability
- Increase timeout values if needed

---

## Production Deployment

### Using Supervisor (Linux)

Create `/etc/supervisor/conf.d/websocket.conf`:

```ini
[program:websocket]
command=php /path/to/your/project/websocket_server.php
directory=/path/to/your/project
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/websocket.log
stderr_logfile=/var/log/websocket_error.log
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start websocket
```

### Using PM2 (Node.js)

```bash
pm2 start websocket_server.js --name "auction-websocket"
pm2 save
pm2 startup
```

### Nginx Reverse Proxy

Add to your Nginx config:

```nginx
location /ws {
    proxy_pass http://localhost:8081;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_read_timeout 86400;
}
```

---

## Security Considerations

1. **Authentication**: Validate JWT tokens in WebSocket connections
2. **Rate Limiting**: Limit message frequency per client
3. **Input Validation**: Validate all incoming messages
4. **SSL/TLS**: Use WSS (WebSocket Secure) in production
5. **CORS**: Configure proper CORS headers

---

## Next Steps

1. Start WebSocket server
2. Test connection from Flutter app
3. Place test bids and verify real-time updates
4. Monitor server logs for issues
5. Deploy to production with proper security

---

**WebSocket server is ready for Phase 2 testing!** 🚀
