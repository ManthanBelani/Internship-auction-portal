<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use PDO;
use App\Utils\Auth;
use App\Services\NotificationQueueService;

class AuctionWebSocketServer implements MessageComponentInterface {
    private SplObjectStorage $clients;
    private array $subscriptions = []; // itemId => [connections]
    private PDO $db;
    private NotificationQueueService $notificationQueue;

    public function __construct(PDO $db) {
        $this->clients = new SplObjectStorage();
        $this->db = $db;
        $this->notificationQueue = new NotificationQueueService($db);
    }

    public function onOpen(ConnectionInterface $conn): void {
        echo "New connection: {$conn->resourceId}\n";
        
        // Extract token from query string
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);
        $token = $params['token'] ?? null;

        if ($token && $this->authenticateConnection($conn, $token)) {
            $this->clients->attach($conn);
            echo "Connection {$conn->resourceId} authenticated as user {$conn->userId}\n";
            
            // Deliver pending notifications
            $this->deliverPendingNotifications($conn);
            
            // Clean up old delivered notifications (older than 24 hours)
            $this->notificationQueue->cleanupOldNotifications(24);
        } else {
            echo "Connection {$conn->resourceId} authentication failed\n";
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Authentication failed'
            ]));
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        echo "Message from {$from->resourceId}: {$msg}\n";
        
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['action'])) {
                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Invalid message format'
                ]));
                return;
            }

            switch ($data['action']) {
                case 'subscribe':
                    if (isset($data['itemId'])) {
                        $this->subscribeToItem($from, (int)$data['itemId']);
                    }
                    break;
                    
                case 'unsubscribe':
                    if (isset($data['itemId'])) {
                        $this->unsubscribeFromItem($from, (int)$data['itemId']);
                    }
                    break;
                    
                default:
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Unknown action'
                    ]));
            }
        } catch (\Exception $e) {
            echo "Error processing message: {$e->getMessage()}\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error processing message'
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn): void {
        echo "Connection {$conn->resourceId} closed\n";
        
        // Remove from all subscriptions
        foreach ($this->subscriptions as $itemId => $connections) {
            $this->subscriptions[$itemId] = array_filter(
                $connections,
                fn($c) => $c !== $conn
            );
            
            // Clean up empty subscription arrays
            if (empty($this->subscriptions[$itemId])) {
                unset($this->subscriptions[$itemId]);
            }
        }
        
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        echo "Error on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Broadcast bid update to all clients watching the item
     */
    public function broadcastBidUpdate(int $itemId, array $bidData): void {
        if (!isset($this->subscriptions[$itemId])) {
            return;
        }

        // Generate unique event ID
        $eventId = uniqid('bid_', true);

        $message = json_encode([
            'type' => 'bid_update',
            'eventId' => $eventId,
            'itemId' => $itemId,
            'bidAmount' => $bidData['bidAmount'],
            'bidderId' => $bidData['bidderId'],
            'bidderName' => $bidData['bidderName'],
            'timestamp' => $bidData['timestamp'],
            'reserveMet' => $bidData['reserveMet'] ?? null
        ]);

        $deliveredCount = 0;
        $failedConnections = [];

        foreach ($this->subscriptions[$itemId] as $conn) {
            try {
                $conn->send($message);
                $deliveredCount++;
            } catch (\Exception $e) {
                echo "Failed to send to connection {$conn->resourceId}: {$e->getMessage()}\n";
                $failedConnections[] = $conn;
                
                // Queue notification for failed delivery
                if (isset($conn->userId)) {
                    $this->notificationQueue->queueNotification(
                        $conn->userId,
                        'bid_update',
                        $itemId,
                        [
                            'eventId' => $eventId,
                            'bidAmount' => $bidData['bidAmount'],
                            'bidderId' => $bidData['bidderId'],
                            'bidderName' => $bidData['bidderName'],
                            'timestamp' => $bidData['timestamp'],
                            'reserveMet' => $bidData['reserveMet'] ?? null
                        ]
                    );
                }
            }
        }
        
        echo "Broadcast bid update for item {$itemId} to {$deliveredCount} clients (event: {$eventId})\n";
    }

    /**
     * Send outbid notification to previous highest bidder
     */
    public function broadcastOutbidNotification(int $itemId, int $previousBidderId, float $newBidAmount, float $previousBidAmount): void {
        if (!isset($this->subscriptions[$itemId])) {
            return;
        }

        // Generate unique event ID
        $eventId = uniqid('outbid_', true);

        $message = json_encode([
            'type' => 'outbid',
            'eventId' => $eventId,
            'itemId' => $itemId,
            'newBidAmount' => $newBidAmount,
            'yourBidAmount' => $previousBidAmount
        ]);

        $delivered = false;

        foreach ($this->subscriptions[$itemId] as $conn) {
            // Send only to the previous bidder
            if (isset($conn->userId) && $conn->userId === $previousBidderId) {
                try {
                    $conn->send($message);
                    $delivered = true;
                    echo "Sent outbid notification to user {$previousBidderId} (event: {$eventId})\n";
                } catch (\Exception $e) {
                    echo "Failed to send outbid notification: {$e->getMessage()}\n";
                    
                    // Queue notification for failed delivery
                    $this->notificationQueue->queueNotification(
                        $previousBidderId,
                        'outbid',
                        $itemId,
                        [
                            'eventId' => $eventId,
                            'newBidAmount' => $newBidAmount,
                            'yourBidAmount' => $previousBidAmount
                        ]
                    );
                }
            }
        }

        // If user not connected, queue the notification
        if (!$delivered) {
            $this->notificationQueue->queueNotification(
                $previousBidderId,
                'outbid',
                $itemId,
                [
                    'eventId' => $eventId,
                    'newBidAmount' => $newBidAmount,
                    'yourBidAmount' => $previousBidAmount
                ]
            );
            echo "Queued outbid notification for offline user {$previousBidderId}\n";
        }
    }

    /**
     * Broadcast auction ending countdown
     */
    public function broadcastAuctionEnding(int $itemId, int $secondsRemaining): void {
        if (!isset($this->subscriptions[$itemId])) {
            return;
        }

        // Generate unique event ID
        $eventId = uniqid('ending_', true);

        $message = json_encode([
            'type' => 'auction_ending',
            'eventId' => $eventId,
            'itemId' => $itemId,
            'secondsRemaining' => $secondsRemaining
        ]);

        $deliveredCount = 0;

        foreach ($this->subscriptions[$itemId] as $conn) {
            try {
                $conn->send($message);
                $deliveredCount++;
            } catch (\Exception $e) {
                echo "Failed to send auction ending notification: {$e->getMessage()}\n";
                
                // Queue notification for failed delivery
                if (isset($conn->userId)) {
                    $this->notificationQueue->queueNotification(
                        $conn->userId,
                        'auction_ending',
                        $itemId,
                        [
                            'eventId' => $eventId,
                            'secondsRemaining' => $secondsRemaining
                        ]
                    );
                }
            }
        }
        
        echo "Broadcast auction ending for item {$itemId}: {$secondsRemaining}s remaining to {$deliveredCount} clients (event: {$eventId})\n";
    }

    /**
     * Broadcast auction ended notification
     */
    public function broadcastAuctionEnded(int $itemId, array $finalData = []): void {
        if (!isset($this->subscriptions[$itemId])) {
            return;
        }

        // Generate unique event ID
        $eventId = uniqid('ended_', true);

        $message = json_encode([
            'type' => 'auction_ended',
            'eventId' => $eventId,
            'itemId' => $itemId,
            'finalPrice' => $finalData['finalPrice'] ?? null,
            'winnerId' => $finalData['winnerId'] ?? null,
            'winnerName' => $finalData['winnerName'] ?? null
        ]);

        $deliveredCount = 0;

        foreach ($this->subscriptions[$itemId] as $conn) {
            try {
                $conn->send($message);
                $deliveredCount++;
            } catch (\Exception $e) {
                echo "Failed to send auction ended notification: {$e->getMessage()}\n";
                
                // Queue notification for failed delivery
                if (isset($conn->userId)) {
                    $this->notificationQueue->queueNotification(
                        $conn->userId,
                        'auction_ended',
                        $itemId,
                        [
                            'eventId' => $eventId,
                            'finalPrice' => $finalData['finalPrice'] ?? null,
                            'winnerId' => $finalData['winnerId'] ?? null,
                            'winnerName' => $finalData['winnerName'] ?? null
                        ]
                    );
                }
            }
        }
        
        echo "Broadcast auction ended for item {$itemId} to {$deliveredCount} clients (event: {$eventId})\n";
    }

    /**
     * Authenticate connection using JWT token
     */
    private function authenticateConnection(ConnectionInterface $conn, string $token): bool {
        $payload = Auth::verifyToken($token);
        
        if ($payload === null) {
            return false;
        }

        // Store user info in connection object
        $conn->userId = $payload['userId'];
        $conn->email = $payload['email'];
        
        return true;
    }

    /**
     * Subscribe connection to item updates
     */
    private function subscribeToItem(ConnectionInterface $conn, int $itemId): void {
        // Verify item exists
        $stmt = $this->db->prepare("SELECT item_id FROM items WHERE item_id = ?");
        $stmt->execute([$itemId]);
        
        if (!$stmt->fetch()) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Item not found'
            ]));
            return;
        }

        // Add connection to subscriptions
        if (!isset($this->subscriptions[$itemId])) {
            $this->subscriptions[$itemId] = [];
        }
        
        // Avoid duplicates
        if (!in_array($conn, $this->subscriptions[$itemId], true)) {
            $this->subscriptions[$itemId][] = $conn;
        }

        $conn->send(json_encode([
            'type' => 'subscribed',
            'itemId' => $itemId
        ]));
        
        echo "Connection {$conn->resourceId} subscribed to item {$itemId}\n";
    }

    /**
     * Unsubscribe connection from item updates
     */
    private function unsubscribeFromItem(ConnectionInterface $conn, int $itemId): void {
        if (isset($this->subscriptions[$itemId])) {
            $this->subscriptions[$itemId] = array_filter(
                $this->subscriptions[$itemId],
                fn($c) => $c !== $conn
            );
            
            if (empty($this->subscriptions[$itemId])) {
                unset($this->subscriptions[$itemId]);
            }
        }

        $conn->send(json_encode([
            'type' => 'unsubscribed',
            'itemId' => $itemId
        ]));
        
        echo "Connection {$conn->resourceId} unsubscribed from item {$itemId}\n";
    }

    /**
     * Deliver pending notifications to reconnected client
     */
    private function deliverPendingNotifications(ConnectionInterface $conn): void {
        if (!isset($conn->userId)) {
            return;
        }

        try {
            $pendingNotifications = $this->notificationQueue->getPendingNotifications($conn->userId);
            
            if (empty($pendingNotifications)) {
                echo "No pending notifications for user {$conn->userId}\n";
                return;
            }

            echo "Delivering " . count($pendingNotifications) . " pending notifications to user {$conn->userId}\n";
            
            $deliveredIds = [];
            
            foreach ($pendingNotifications as $notification) {
                // Send the notification
                $message = json_encode([
                    'type' => $notification['type'],
                    'itemId' => $notification['itemId'],
                    'data' => $notification['payload'],
                    'queued' => true,
                    'queuedAt' => $notification['createdAt']
                ]);
                
                $conn->send($message);
                $deliveredIds[] = $notification['notificationId'];
            }
            
            // Mark all as delivered
            if (!empty($deliveredIds)) {
                $this->notificationQueue->markMultipleAsDelivered($deliveredIds);
                echo "Marked " . count($deliveredIds) . " notifications as delivered\n";
            }
            
        } catch (\Exception $e) {
            echo "Error delivering pending notifications: {$e->getMessage()}\n";
        }
    }
}
