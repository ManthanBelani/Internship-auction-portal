-- Create events table for WebSocket signaling
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    item_id INT NOT NULL,
    payload TEXT NOT NULL,
    status ENUM('pending', 'processed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
);
