<?php

namespace App\Controllers;

use App\Services\ShippingService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;

class ShippingController
{
    private ShippingService $shippingService;

    public function __construct()
    {
        $this->shippingService = new ShippingService();
    }

    /**
     * GET /api/shipping/addresses
     * Get user's shipping addresses
     */
    public function getAddresses(): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $addresses = $this->shippingService->getAddresses((int)$user['userId']);
            Response::success(['addresses' => $addresses]);
        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/shipping/addresses
     * Add a new shipping address
     */
    public function addAddress(array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $validator = new \App\Validation\Validator();
            if (!$validator->validate($data, [
                'fullName' => 'required|min:2',
                'addressLine1' => 'required|min:5',
                'city' => 'required',
                'state' => 'required',
                'zipCode' => 'required',
                'country' => 'required',
                'phone' => 'required'
            ])) {
                Response::badRequest($validator->getFirstError());
                return;
            }

            $result = $this->shippingService->addAddress((int)$user['userId'], $data);
            Response::success($result, 201);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * PUT /api/shipping/addresses/:addressId
     * Update shipping address
     */
    public function updateAddress(int $addressId, array $data): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $result = $this->shippingService->updateAddress(
                (int)$user['userId'],
                $addressId,
                $data
            );
            Response::success($result);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * DELETE /api/shipping/addresses/:addressId
     * Delete shipping address
     */
    public function deleteAddress(int $addressId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            $this->shippingService->deleteAddress((int)$user['userId'], $addressId);
            Response::success(['message' => 'Address deleted']);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * POST /api/shipping/calculate
     * Calculate shipping cost
     */
    public function calculateShipping(array $data): void
    {
        try {
            if (!isset($data['itemId']) || !isset($data['addressId'])) {
                Response::badRequest('Item ID and address ID are required');
                return;
            }

            $cost = $this->shippingService->calculateShipping(
                (int)$data['itemId'],
                (int)$data['addressId']
            );

            Response::success(['shippingCost' => $cost]);
        } catch (\Exception $e) {
            Response::badRequest($e->getMessage());
        }
    }
}
