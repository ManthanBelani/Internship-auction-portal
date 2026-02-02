<?php

namespace App\Utils;

class WebSocketClient
{
    private string $wsHost;
    private int $wsPort;
    private bool $enabled;

    public function __construct()
    {
        $this->wsHost = $_ENV['WS_HOST'] ?? '127.0.0.1';
        $this->wsPort = (int)($_ENV['WS_PORT'] ?? 8080);
        $this->enabled = ($_ENV['WS_ENABLED'] ?? 'true') === 'true';
    }

    /**
     * Notify WebSocket server of a bid update
     * 
     * @param int $itemId Item ID
     * @param array $bidData Bid data to broadcast
     * @return bool Success status
     */
    public function notifyBidUpdate(int $itemId, array $bidData): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // In a real implementation, this would send an HTTP request to a WebSocket server endpoint
            // For now, we'll use a simple approach that the WebSocket server can poll or receive via HTTP
            
            // Create a simple HTTP request to notify the WebSocket server
            $url = "http://{$this->wsHost}:{$this->wsPort}/notify";
            
            $payload = json_encode([
                'type' => 'bid_update',
                'itemId' => $itemId,
                'bidData' => $bidData
            ]);

            // Use cURL for non-blocking request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 second timeout
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
            
        } catch (\Exception $e) {
            // Log error but don't fail the bid operation
            error_log("WebSocket notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify WebSocket server of an outbid event
     * 
     * @param int $itemId Item ID
     * @param int $previousBidderId Previous highest bidder ID
     * @param float $newBidAmount New bid amount
     * @param float $previousBidAmount Previous bid amount
     * @return bool Success status
     */
    public function notifyOutbid(int $itemId, int $previousBidderId, float $newBidAmount, float $previousBidAmount): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $url = "http://{$this->wsHost}:{$this->wsPort}/notify";
            
            $payload = json_encode([
                'type' => 'outbid',
                'itemId' => $itemId,
                'previousBidderId' => $previousBidderId,
                'newBidAmount' => $newBidAmount,
                'previousBidAmount' => $previousBidAmount
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
            
        } catch (\Exception $e) {
            error_log("WebSocket outbid notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify WebSocket server of auction ending
     * 
     * @param int $itemId Item ID
     * @param int $secondsRemaining Seconds until auction ends
     * @return bool Success status
     */
    public function notifyAuctionEnding(int $itemId, int $secondsRemaining): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $url = "http://{$this->wsHost}:{$this->wsPort}/notify";
            
            $payload = json_encode([
                'type' => 'auction_ending',
                'itemId' => $itemId,
                'secondsRemaining' => $secondsRemaining
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
            
        } catch (\Exception $e) {
            error_log("WebSocket auction ending notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify WebSocket server of auction ended
     * 
     * @param int $itemId Item ID
     * @param array $finalData Final auction data
     * @return bool Success status
     */
    public function notifyAuctionEnded(int $itemId, array $finalData = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $url = "http://{$this->wsHost}:{$this->wsPort}/notify";
            
            $payload = json_encode([
                'type' => 'auction_ended',
                'itemId' => $itemId,
                'finalData' => $finalData
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
            
        } catch (\Exception $e) {
            error_log("WebSocket auction ended notification failed: " . $e->getMessage());
            return false;
        }
    }
}
