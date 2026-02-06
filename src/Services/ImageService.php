<?php

namespace App\Services;

use PDO;

class ImageService {
    private PDO $db;
    private string $uploadDir = 'uploads/';
    private string $thumbnailDir = 'uploads/thumbnails/';
    private array $allowedFormats = ['jpg', 'jpeg', 'png', 'webp'];
    private int $maxFileSize = 5242880; // 5MB
    private int $thumbnailWidth = 200;
    private int $thumbnailHeight = 200;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Validate uploaded image file
     * Checks file extension, MIME type, and file size
     * 
     * @param array $file The uploaded file array from $_FILES
     * @return array Returns ['valid' => bool, 'error' => string|null]
     */
    public function validateImage(array $file): array {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file was uploaded'];
        }

        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }

        // Validate file size
        if (!isset($file['size']) || $file['size'] > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / 1048576;
            return ['valid' => false, 'error' => "File size exceeds maximum allowed size of {$maxSizeMB}MB"];
        }

        if ($file['size'] === 0) {
            return ['valid' => false, 'error' => 'File is empty'];
        }

        // Validate file extension
        $filename = $file['name'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->allowedFormats)) {
            $allowedList = implode(', ', array_map('strtoupper', $this->allowedFormats));
            return ['valid' => false, 'error' => "Invalid file format. Allowed formats: {$allowedList}"];
        }

        // Validate MIME type matches extension
        $mimeValidation = $this->validateMimeType($file['tmp_name'], $extension);
        if (!$mimeValidation['valid']) {
            return $mimeValidation;
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate that MIME type matches the file extension
     * 
     * @param string $filePath Path to the uploaded file
     * @param string $extension File extension
     * @return array Returns ['valid' => bool, 'error' => string|null]
     */
    private function validateMimeType(string $filePath, string $extension): array {
        // Get MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return ['valid' => false, 'error' => 'Unable to determine file type'];
        }

        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if ($mimeType === false) {
            return ['valid' => false, 'error' => 'Unable to read file MIME type'];
        }

        // Map extensions to expected MIME types
        $mimeMap = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp']
        ];

        $expectedMimes = $mimeMap[$extension] ?? [];
        
        if (!in_array($mimeType, $expectedMimes)) {
            return [
                'valid' => false, 
                'error' => "File MIME type ({$mimeType}) does not match extension ({$extension})"
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get human-readable error message for upload error codes
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by PHP extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Generate a unique filename for uploaded image
     * Uses uniqid() and hash to prevent collisions and path traversal
     * 
     * @param string $extension File extension (without dot)
     * @return string Unique filename with extension
     */
    private function generateUniqueFilename(string $extension): string {
        // Generate unique ID with more entropy
        $uniqueId = uniqid('img_', true);
        
        // Add hash for additional uniqueness and security
        $hash = hash('sha256', $uniqueId . microtime() . random_bytes(16));
        
        // Take first 32 characters of hash
        $shortHash = substr($hash, 0, 32);
        
        // Combine and sanitize to prevent path traversal
        $filename = $uniqueId . '_' . $shortHash;
        
        // Remove any directory separators for security
        $filename = str_replace(['/', '\\', '..'], '', $filename);
        
        return $filename . '.' . $extension;
    }

    /**
     * Upload image file and store it with unique filename
     * Validates file, moves to uploads directory, sets permissions
     * 
     * @param int $itemId The item ID to associate the image with
     * @param array $file The uploaded file array from $_FILES
     * @return array Returns ['success' => bool, 'error' => string|null, 'imageUrl' => string|null, 'thumbnailUrl' => string|null]
     */
    public function uploadImage(int $itemId, array $file): array {
        // Validate the image first
        $validation = $this->validateImage($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }

        // Get file extension
        $filename = $file['name'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Generate unique filename
        $uniqueFilename = $this->generateUniqueFilename($extension);
        
        // Define destination paths
        $uploadPath = $this->uploadDir . $uniqueFilename;
        $thumbnailFilename = pathinfo($uniqueFilename, PATHINFO_FILENAME) . '_thumb.' . $extension;
        $thumbnailPath = $this->thumbnailDir . $thumbnailFilename;

        // Ensure upload directories exist
        if (!is_dir($this->uploadDir)) {
            return [
                'success' => false,
                'error' => 'Upload directory does not exist',
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }

        if (!is_dir($this->thumbnailDir)) {
            return [
                'success' => false,
                'error' => 'Thumbnail directory does not exist',
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }

        // Move uploaded file to destination
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => false,
                'error' => 'Failed to move uploaded file',
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }

        // Set file permissions to 0644 (read-only for others, no execute)
        if (!chmod($uploadPath, 0644)) {
            // Clean up uploaded file if chmod fails
            @unlink($uploadPath);
            return [
                'success' => false,
                'error' => 'Failed to set file permissions',
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }

        // Generate thumbnail
        $thumbnailResult = $this->generateThumbnail($uploadPath, $thumbnailPath);
        if (!$thumbnailResult) {
            // Clean up uploaded file if thumbnail generation fails
            @unlink($uploadPath);
            return [
                'success' => false,
                'error' => 'Failed to generate thumbnail',
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }

        // Prepare URLs for database storage
        $imageUrl = '/' . $uploadPath;
        $thumbnailUrl = '/' . $thumbnailPath;

        // Insert record into item_images table
        try {
            $stmt = $this->db->prepare("
                INSERT INTO item_images (item_id, image_url, thumbnail_url)
                VALUES (:item_id, :image_url, :thumbnail_url)
            ");
            
            $stmt->execute([
                ':item_id' => $itemId,
                ':image_url' => $imageUrl,
                ':thumbnail_url' => $thumbnailUrl
            ]);
            
            $imageId = (int) $this->db->lastInsertId();

            // Return success with file paths and image ID
            return [
                'success' => true,
                'error' => null,
                'imageId' => $imageId,
                'imageUrl' => $imageUrl,
                'thumbnailUrl' => $thumbnailUrl
            ];

        } catch (\PDOException $e) {
            // Clean up uploaded files if database insert fails
            @unlink($uploadPath);
            @unlink($thumbnailPath);
            
            error_log("ImageService: Database error during image upload: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to save image record to database',
                'imageUrl' => null,
                'thumbnailUrl' => null
            ];
        }
    }

    /**
     * Generate thumbnail from original image
     * Resizes image to 200x200 maintaining aspect ratio
     * Supports JPG, PNG, and WEBP formats
     * 
     * @param string $originalPath Path to the original image file
     * @param string $thumbnailPath Path where thumbnail should be saved
     * @return bool Returns true on success, false on failure
     */
    public function generateThumbnail(string $originalPath, string $thumbnailPath): bool {
        try {
            // Check if original file exists
            if (!file_exists($originalPath)) {
                error_log("ImageService: Original file not found: {$originalPath}");
                return false;
            }

            // Get image information
            $imageInfo = @getimagesize($originalPath);
            if ($imageInfo === false) {
                error_log("ImageService: Unable to get image size for: {$originalPath}");
                return false;
            }

            list($originalWidth, $originalHeight, $imageType) = $imageInfo;

            // Create image resource from original based on type
            $sourceImage = null;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = @imagecreatefromjpeg($originalPath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = @imagecreatefrompng($originalPath);
                    break;
                case IMAGETYPE_WEBP:
                    $sourceImage = @imagecreatefromwebp($originalPath);
                    break;
                default:
                    error_log("ImageService: Unsupported image type: {$imageType}");
                    return false;
            }

            if ($sourceImage === false) {
                error_log("ImageService: Failed to create image resource from: {$originalPath}");
                return false;
            }

            // Calculate thumbnail dimensions maintaining aspect ratio
            $aspectRatio = $originalWidth / $originalHeight;
            
            if ($aspectRatio > 1) {
                // Landscape orientation
                $thumbWidth = $this->thumbnailWidth;
                $thumbHeight = (int)($this->thumbnailWidth / $aspectRatio);
            } else {
                // Portrait or square orientation
                $thumbHeight = $this->thumbnailHeight;
                $thumbWidth = (int)($this->thumbnailHeight * $aspectRatio);
            }

            // Create thumbnail image resource
            $thumbnailImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
            if ($thumbnailImage === false) {
                imagedestroy($sourceImage);
                error_log("ImageService: Failed to create thumbnail image resource");
                return false;
            }

            // Preserve transparency for PNG and WEBP
            if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
                imagealphablending($thumbnailImage, false);
                imagesavealpha($thumbnailImage, true);
                $transparent = imagecolorallocatealpha($thumbnailImage, 0, 0, 0, 127);
                imagefilledrectangle($thumbnailImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
            }

            // Resize image with resampling for better quality
            $resizeResult = imagecopyresampled(
                $thumbnailImage,
                $sourceImage,
                0, 0, 0, 0,
                $thumbWidth,
                $thumbHeight,
                $originalWidth,
                $originalHeight
            );

            if (!$resizeResult) {
                imagedestroy($sourceImage);
                imagedestroy($thumbnailImage);
                error_log("ImageService: Failed to resample image");
                return false;
            }

            // Save thumbnail based on original image type
            $saveResult = false;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $saveResult = imagejpeg($thumbnailImage, $thumbnailPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    $saveResult = imagepng($thumbnailImage, $thumbnailPath, 8);
                    break;
                case IMAGETYPE_WEBP:
                    $saveResult = imagewebp($thumbnailImage, $thumbnailPath, 85);
                    break;
            }

            // Clean up resources
            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);

            if (!$saveResult) {
                error_log("ImageService: Failed to save thumbnail to: {$thumbnailPath}");
                return false;
            }

            // Set file permissions to 0644
            if (!chmod($thumbnailPath, 0644)) {
                error_log("ImageService: Failed to set thumbnail permissions: {$thumbnailPath}");
                // Don't fail the operation if chmod fails, thumbnail is still created
            }

            return true;

        } catch (\Exception $e) {
            error_log("ImageService: Exception during thumbnail generation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all images for a specific item
     * Retrieves all image records associated with an item ID
     * 
     * @param int $itemId The item ID to retrieve images for
     * @return array Array of image records with URLs
     */
    public function getItemImages(int $itemId): array {
        try {
            // Check if table exists and has correct structure
            $tableCheck = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='item_images'");
            if (!$tableCheck || !$tableCheck->fetch()) {
                // Table doesn't exist, return empty array
                return [];
            }
            
            $stmt = $this->db->prepare("
                SELECT id, item_id, image_url, thumbnail_url, created_at
                FROM item_images
                WHERE item_id = :item_id
                ORDER BY created_at ASC
            ");
            
            $stmt->execute([':item_id' => $itemId]);
            
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform to camelCase for API response
            return array_map(function($image) {
                return [
                    'imageId' => (int) $image['id'],
                    'itemId' => (int) $image['item_id'],
                    'imageUrl' => $image['image_url'],
                    'thumbnailUrl' => $image['thumbnail_url'],
                    'uploadTimestamp' => $image['created_at']
                ];
            }, $images);

        } catch (\PDOException $e) {
            error_log("ImageService: Database error retrieving images: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete an image by ID
     * Removes both the database record and the physical files
     * 
     * @param int $imageId The image ID to delete
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function deleteImage(int $imageId): array {
        try {
            // First, retrieve the image record to get file paths
            $stmt = $this->db->prepare("
                SELECT image_url, thumbnail_url
                FROM item_images
                WHERE image_id = :image_id
            ");
            
            $stmt->execute([':image_id' => $imageId]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                return [
                    'success' => false,
                    'error' => 'Image not found'
                ];
            }

            // Delete database record first
            $deleteStmt = $this->db->prepare("
                DELETE FROM item_images
                WHERE image_id = :image_id
            ");
            
            $deleteStmt->execute([':image_id' => $imageId]);

            // Remove leading slash from URLs to get file paths
            $imagePath = ltrim($image['image_url'], '/');
            $thumbnailPath = ltrim($image['thumbnail_url'], '/');

            // Delete physical files
            $imageDeleted = true;
            $thumbnailDeleted = true;

            if (file_exists($imagePath)) {
                $imageDeleted = @unlink($imagePath);
                if (!$imageDeleted) {
                    error_log("ImageService: Failed to delete image file: {$imagePath}");
                }
            }

            if (file_exists($thumbnailPath)) {
                $thumbnailDeleted = @unlink($thumbnailPath);
                if (!$thumbnailDeleted) {
                    error_log("ImageService: Failed to delete thumbnail file: {$thumbnailPath}");
                }
            }

            // Return success even if file deletion partially failed
            // Database record is already deleted
            return [
                'success' => true,
                'error' => null
            ];

        } catch (\PDOException $e) {
            error_log("ImageService: Database error deleting image: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete image from database'
            ];
        }
    }

    // Methods will be implemented in subsequent tasks
}
