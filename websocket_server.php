<?php
/**
 * WebSocket Server for Real-Time Auction Updates
 * 
 * Run this server separately: php websocket_server.php
 * It will listen on port 8081 for WebSocket connections
 */

require_once __DIR__ . '/vendor/autoload.php';

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
        $this->subscriptions = [
            'items' => [],
            'notifications' => [],
            'auctions' => []
        ];
        echo "WebSocket Server initialized\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->subscriptions = [];
        
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send welcome message
        $conn->send(json_encode([
            'type' => 'connected',
            'message' => 'Connected to BidOrbit WebSocket Server',
            'connectionId' => $conn->resourceId
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                return;
            }

            echo "Message from {$from->resourceId}: {$data['type']}\n";

            switch ($data['type']) {
                case 'ping':
                    $from->send(json_encode(['type' => 'pong']));
                    break;

                case 'subscribe':
                    $this->handleSubscribe($from, $data);
                    break;

                case 'unsubscribe':
                    $this->handleUnsubscribe($from, $data);
                    break;

                case 'bid_placed':
                    $this->broadcastBidUpdate($data);
                    break;

                default:
                    echo "Unknown message type: {$data['type']}\n";
            }
        } catch (Exception $e) {
            echo "Error processing message: {$e->getMessage()}\n";
        }
    }

    protected function handleSubscribe(ConnectionInterface $conn, $data) {
        $channel = $data['channel'] ?? null;
        
        if (!$channel) {
            return;
        }

        switch ($channel) {
            case 'item':
                $itemId = $data['itemId'] ?? null;
                if ($itemId) {
                    if (!isset($this->subscriptions['items'][$itemId])) {
                        $this->subscriptions['items'][$itemId] = new \SplObjectStorage;
                    }
                    $this->subscriptions['items'][$itemId]->attach($conn);
                    $conn->subscriptions[] = ['type' => 'item', 'id' => $itemId];
                    
                    echo "Client {$conn->resourceId} subscribed to item {$itemId}\n";
                    
                    $conn->send(json_encode([
                        'type' => 'subscribed',
                        'channel' => 'item',
                        'itemId' => $itemId
                    ]));
                }
                break;

            case 'notifications':
                if (!isset($this->subscriptions['notifications'])) {
                    $this->subscriptions['notifications'] = new \SplObjectStorage;
                }
                $this->subscriptions['notifications']->attach($conn);
                $conn->subscriptions[] = ['type' => 'notifications'];
                
                echo "Client {$conn->resourceId} subscribed to notifications\n";
                
                $conn->send(json_encode([
                    'type' => 'subscribed',
                    'channel' => 'notifications'
                ]));
                break;

            case 'auctions':
                if (!isset($this->subscriptions['auctions'])) {
                    $this->subscriptions['auctions'] = new \SplObjectStorage;
                }
                $this->subscriptions['auctions']->attach($conn);
                $conn->subscriptions[] = ['type' => 'auctions'];
                
                echo "Client {$conn->resourceId} subscribed to auctions\n";
                
                $conn->send(json_encode([
                    'type' => 'subscribed',
                    'channel' => 'auctions'
                ]));
                break;
        }
    }

    protected function handleUnsubscribe(ConnectionInterface $conn, $data) {
        $channel = $data['channel'] ?? null;
        
        if (!$channel) {
            return;
        }

        switch ($channel) {
            case 'item':
                $itemId = $data['itemId'] ?? null;
                if ($itemId && isset($this->subscriptions['items'][$itemId])) {
                    $this->subscriptions['items'][$itemId]->detach($conn);
                    echo "Client {$conn->resourceId} unsubscribed from item {$itemId}\n";
                }
                break;

            case 'notifications':
                if (isset($this->subscriptions['notifications'])) {
                    $this->subscriptions['notifications']->detach($conn);
                    echo "Client {$conn->resourceId} unsubscribed from notifications\n";
                }
                break;

            case 'auctions':
                if (isset($this->subscriptions['auctions'])) {
                    $this->subscriptions['auctions']->detach($conn);
                    echo "Client {$conn->resourceId} unsubscribed from auctions\n";
                }
                break;
        }
    }

    protected function broadcastBidUpdate($data) {
        $itemId = $data['itemId'] ?? null;
        
        if (!$itemId || !isset($this->subscriptions['items'][$itemId])) {
            return;
        }

        $message = json_encode([
            'type' => 'bid_update',
            'itemId' => $itemId,
            'bidAmount' => $data['bidAmount'] ?? 0,
            'bidderName' => $data['bidderName'] ?? 'Anonymous',
            'timestamp' => date('c'),
            'bidCount' => $data['bidCount'] ?? 0
        ]);

        echo "Broadcasting bid update for item {$itemId}\n";

        foreach ($this->subscriptions['items'][$itemId] as $client) {
            $client->send($message);
        }

        // Also broadcast to general auctions channel
        if (isset($this->subscriptions['auctions'])) {
            foreach ($this->subscriptions['auctions'] as $client) {
                $client->send($message);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Unsubscribe from all channels
        foreach ($conn->subscriptions ?? [] as $subscription) {
            if ($subscription['type'] === 'item' && isset($subscription['id'])) {
                $itemId = $subscription['id'];
                if (isset($this->subscriptions['items'][$itemId])) {
                    $this->subscriptions['items'][$itemId]->detach($conn);
                }
            } elseif ($subscription['type'] === 'notifications') {
                if (isset($this->subscriptions['notifications'])) {
                    $this->subscriptions['notifications']->detach($conn);
                }
            } elseif ($subscription['type'] === 'auctions') {
                if (isset($this->subscriptions['auctions'])) {
                    $this->subscriptions['auctions']->detach($conn);
                }
            }
        }

        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Method to broadcast from external sources (e.g., when a bid is placed via HTTP)
    public function broadcastToItem($itemId, $data) {
        if (!isset($this->subscriptions['items'][$itemId])) {
            return;
        }

        $message = json_encode($data);
        foreach ($this->subscriptions['items'][$itemId] as $client) {
            $client->send($message);
        }
    }
}

// Start the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new AuctionWebSocket()
        )
    ),
    8081,
    '0.0.0.0'
);

echo "WebSocket server started on port 8081\n";
echo "Listening for connections...\n";

$server->run();
