<?php

namespace App\Utils;

use App\Config\Database;
use PDO;

class WebSocketClient
{
    private ?PDO $db;
    private bool $enabled;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->enabled = ($_ENV['WS_ENABLED'] ?? 'true') === 'true';
    }

    /**
     * Queue an event in the database for the WebSocket server to pick up
     */
    private function queueEvent(string $type, int $itemId, array $payload): bool
    {
        if (!$this->enabled || !$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO events (type, item_id, payload, status) VALUES (?, ?, ?, 'pending')");
            return $stmt->execute([
                $type,
                $itemId,
                json_encode($payload)
            ]);
        } catch (\Exception $e) {
            error_log("Failed to queue WebSocket event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify WebSocket server of a bid update
     */
    public function notifyBidUpdate(int $itemId, array $bidData): bool
    {
        return $this->queueEvent('bid_update', $itemId, $bidData);
    }

    /**
     * Notify WebSocket server of an outbid event
     */
    public function notifyOutbid(int $itemId, int $previousBidderId, float $newBidAmount, float $previousBidAmount): bool
    {
        return $this->queueEvent('outbid', $itemId, [
            'previousBidderId' => $previousBidderId,
            'newBidAmount' => $newBidAmount,
            'previousBidAmount' => $previousBidAmount
        ]);
    }

    /**
     * Notify WebSocket server of auction ending
     */
    public function notifyAuctionEnding(int $itemId, int $secondsRemaining): bool
    {
        return $this->queueEvent('auction_ending', $itemId, [
            'secondsRemaining' => $secondsRemaining
        ]);
    }

    /**
     * Notify WebSocket server of auction ended
     */
    public function notifyAuctionEnded(int $itemId, array $finalData = []): bool
    {
        return $this->queueEvent('auction_ended', $itemId, $finalData);
    }

    /**
     * Notify WebSocket server of auction extension (Soft-Close)
     */
    public function notifyAuctionExtended(int $itemId, string $newEndTime): bool
    {
        return $this->queueEvent('auction_extended', $itemId, [
            'newEndTime' => $newEndTime
        ]);
    }
}
