<?php

namespace App\Controllers;

use App\Services\ImageService;
use App\Services\ItemService;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Config\Database;

class ImageController
{
    private ImageService $imageService;
    private ItemService $itemService;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->imageService = new ImageService($db);
        $this->itemService = new ItemService();
    }

    /**
     * POST /api/items/{itemId}/images/bulk
     * Bulk upload images for an auction item
     */
    public function bulkUpload(int $itemId): void
    {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            // Verify item exists and user is seller
            $item = $this->itemService->getItemById($itemId);
            if ($item['sellerId'] !== (int)$user['userId']) {
                Response::forbidden('Access denied');
                return;
            }

            if (!isset($_FILES['images'])) {
                Response::badRequest('No images provided');
                return;
            }

            $files = $_FILES['images'];
            $results = [];
            $errors = [];

            // Reformat $_FILES array if needed (multiple files)
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $uploadResult = $this->imageService->uploadImage($itemId, $file);
                if ($uploadResult['success']) {
                    $results[] = [
                        'imageId' => $uploadResult['imageId'],
                        'imageUrl' => $uploadResult['imageUrl']
                    ];
                } else {
                    $errors[] = [
                        'fileName' => $file['name'],
                        'error' => $uploadResult['error']
                    ];
                }
            }

            Response::success([
                'uploaded' => $results,
                'errors' => $errors,
                'total_success' => count($results),
                'total_failed' => count($errors)
            ], 201);

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/items/{itemId}/images
     * Get all images for an auction item
     * Public endpoint - no authentication required
     */
    public function getImages(int $itemId): void
    {
        try {
            // Validate itemId
            if ($itemId <= 0) {
                Response::badRequest('Invalid item ID');
                return;
            }

            // Verify item exists
            try {
                $this->itemService->getItemById($itemId);
            } catch (\Exception $e) {
                Response::notFound('Item not found');
                return;
            }

            // Get images for the item
            $images = $this->imageService->getItemImages($itemId);

            Response::success([
                'itemId' => $itemId,
                'images' => $images,
                'count' => count($images)
            ]);

        } catch (\Exception $e) {
            error_log("ImageController: Get images error - " . $e->getMessage());
            Response::serverError('Failed to retrieve images');
        }
    }

    /**
     * DELETE /api/images/{imageId}
     * Delete a specific image
     * Requires authentication and seller ownership
     */
    public function delete(int $imageId): void
    {
        try {
            // Authenticate user
            $user = AuthMiddleware::authenticate();
            if (!$user) return;

            // Validate imageId
            if ($imageId <= 0) {
                Response::badRequest('Invalid image ID');
                return;
            }

            // Get image details to verify ownership
            $images = $this->imageService->getItemImages(0); // We need to get the image first
            
            // We need to query the image to get its itemId for ownership verification
            // Let's get all images and find the one we need
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT item_id FROM item_images WHERE image_id = :image_id");
            $stmt->execute([':image_id' => $imageId]);
            $imageRecord = $stmt->fetch();

            if (!$imageRecord) {
                Response::notFound('Image not found');
                return;
            }

            $itemId = (int)$imageRecord['item_id'];

            // Verify item exists and user is the seller
            try {
                $item = $this->itemService->getItemById($itemId);
            } catch (\Exception $e) {
                Response::notFound('Associated item not found');
                return;
            }

            // Check if user is the seller
            if ($item['sellerId'] !== (int)$user['userId']) {
                Response::forbidden('Only the seller can delete images for this item');
                return;
            }

            // Delete the image
            $result = $this->imageService->deleteImage($imageId);

            if (!$result['success']) {
                Response::badRequest($result['error']);
                return;
            }

            Response::success([
                'message' => 'Image deleted successfully'
            ]);

        } catch (\Exception $e) {
            error_log("ImageController: Delete error - " . $e->getMessage());
            Response::serverError('Failed to delete image');
        }
    }
}
