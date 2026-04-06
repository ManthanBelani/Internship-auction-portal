<?php

namespace App\Services;

use App\Config\Database;

class ShippingService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get user's shipping addresses
     */
    public function getAddresses(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, fullName, addressLine1, addressLine2, city, state, 
                   zipCode, country, phone, addressType, isDefault, createdAt
            FROM shipping_addresses
            WHERE userId = :userId AND isDeleted = 0
            ORDER BY isDefault DESC, createdAt DESC
        ");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Add new shipping address
     */
    public function addAddress(int $userId, array $data): array
    {
        $isDefault = $data['isDefault'] ?? false;

        // If setting as default, unset other defaults
        if ($isDefault) {
            $stmt = $this->db->prepare("UPDATE shipping_addresses SET isDefault = 0 WHERE userId = :userId");
            $stmt->execute(['userId' => $userId]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO shipping_addresses (
                userId, fullName, addressLine1, addressLine2, city, state,
                zipCode, country, phone, addressType, isDefault, createdAt
            ) VALUES (
                :userId, :fullName, :addressLine1, :addressLine2, :city, :state,
                :zipCode, :country, :phone, :addressType, :isDefault, NOW())
            )
        ");

        $stmt->execute([
            'userId' => $userId,
            'fullName' => $data['fullName'],
            'addressLine1' => $data['addressLine1'],
            'addressLine2' => $data['addressLine2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'],
            'zipCode' => $data['zipCode'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'addressType' => $data['addressType'] ?? 'home',
            'isDefault' => $isDefault ? 1 : 0
        ]);

        return [
            'id' => $this->db->lastInsertId(),
            'message' => 'Address added successfully'
        ];
    }

    /**
     * Update shipping address
     */
    public function updateAddress(int $userId, int $addressId, array $data): array
    {
        // Verify ownership
        $stmt = $this->db->prepare("SELECT id FROM shipping_addresses WHERE id = :id AND userId = :userId");
        $stmt->execute(['id' => $addressId, 'userId' => $userId]);
        if (!$stmt->fetch()) {
            throw new \Exception('Address not found');
        }

        $isDefault = $data['isDefault'] ?? false;
        if ($isDefault) {
            $stmt = $this->db->prepare("UPDATE shipping_addresses SET isDefault = 0 WHERE userId = :userId");
            $stmt->execute(['userId' => $userId]);
        }

        $fields = [];
        $params = ['id' => $addressId, 'userId' => $userId];

        foreach (['fullName', 'addressLine1', 'addressLine2', 'city', 'state', 'zipCode', 'country', 'phone', 'addressType'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if ($isDefault) {
            $fields[] = "isDefault = :isDefault";
            $params['isDefault'] = 1;
        }

        if (empty($fields)) {
            throw new \Exception('No fields to update');
        }

        $sql = "UPDATE shipping_addresses SET " . implode(', ', $fields) . " WHERE id = :id AND userId = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['message' => 'Address updated successfully'];
    }

    /**
     * Delete shipping address
     */
    public function deleteAddress(int $userId, int $addressId): void
    {
        $stmt = $this->db->prepare("
            UPDATE shipping_addresses 
            SET isDeleted = 1 
            WHERE id = :id AND userId = :userId
        ");
        $stmt->execute(['id' => $addressId, 'userId' => $userId]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception('Address not found');
        }
    }

    /**
     * Calculate shipping cost (simplified)
     */
    public function calculateShipping(int $itemId, int $addressId): float
    {
        // In production, integrate with shipping API (USPS, FedEx, etc.)
        // For now, return a flat rate based on item category
        
        $stmt = $this->db->prepare("SELECT category FROM items WHERE id = :itemId");
        $stmt->execute(['itemId' => $itemId]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item) {
            throw new \Exception('Item not found');
        }

        // Simple shipping cost calculation
        $shippingRates = [
            'Electronics' => 15.00,
            'Art' => 25.00,
            'Watches' => 10.00,
            'Jewelry' => 10.00,
            'Collectibles' => 12.00,
            'default' => 10.00
        ];

        return $shippingRates[$item['category']] ?? $shippingRates['default'];
    }
}
