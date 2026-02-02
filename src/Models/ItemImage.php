<?php

namespace App\Models;

use PDO;

class ItemImage {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create(int $itemId, string $imageUrl, string $thumbnailUrl): int {
        $stmt = $this->db->prepare("
            INSERT INTO item_images (item_id, image_url, thumbnail_url)
            VALUES (:item_id, :image_url, :thumbnail_url)
        ");
        
        $stmt->execute([
            ':item_id' => $itemId,
            ':image_url' => $imageUrl,
            ':thumbnail_url' => $thumbnailUrl
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function findByItemId(int $itemId): array {
        $stmt = $this->db->prepare("
            SELECT image_id, item_id, image_url, thumbnail_url, upload_timestamp
            FROM item_images
            WHERE item_id = :item_id
            ORDER BY upload_timestamp ASC
        ");
        
        $stmt->execute([':item_id' => $itemId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $imageId): ?array {
        $stmt = $this->db->prepare("
            SELECT image_id, item_id, image_url, thumbnail_url, upload_timestamp
            FROM item_images
            WHERE image_id = :image_id
        ");
        
        $stmt->execute([':image_id' => $imageId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function delete(int $imageId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM item_images
            WHERE image_id = :image_id
        ");
        
        return $stmt->execute([':image_id' => $imageId]);
    }

    public function toArray(array $data): array {
        return [
            'imageId' => (int) $data['image_id'],
            'itemId' => (int) $data['item_id'],
            'imageUrl' => $data['image_url'],
            'thumbnailUrl' => $data['thumbnail_url'],
            'uploadTimestamp' => $data['upload_timestamp']
        ];
    }
}
