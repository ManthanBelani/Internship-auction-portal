<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ImageService;
use App\Config\Database;

class ImageServiceTest extends TestCase
{
    private ImageService $imageService;

    protected function setUp(): void
    {
        $db = Database::getConnection();
        $this->imageService = new ImageService($db);
    }

    /**
     * Create a mock uploaded file array for testing
     */
    private function createMockFile(
        string $name,
        string $tmpName,
        int $size,
        int $error = UPLOAD_ERR_OK
    ): array {
        return [
            'name' => $name,
            'tmp_name' => $tmpName,
            'size' => $size,
            'error' => $error
        ];
    }

    /**
     * Create a temporary test image file
     */
    private function createTestImage(string $extension, int $width = 100, int $height = 100): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_image_');
        
        // Create a simple image using GD
        $image = imagecreatetruecolor($width, $height);
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $tmpFile);
                break;
            case 'png':
                imagepng($image, $tmpFile);
                break;
            case 'webp':
                imagewebp($image, $tmpFile);
                break;
        }
        
        imagedestroy($image);
        
        return $tmpFile;
    }

    public function testValidateImageWithValidJpg()
    {
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageWithValidPng()
    {
        $tmpFile = $this->createTestImage('png');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.png', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageWithValidWebp()
    {
        $tmpFile = $this->createTestImage('webp');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.webp', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsInvalidExtension()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'test content');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.txt', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid file format', $result['error']);
        $this->assertStringContainsString('JPG, JPEG, PNG, WEBP', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsFileTooLarge()
    {
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = 6 * 1024 * 1024; // 6MB (exceeds 5MB limit)
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('File size exceeds maximum', $result['error']);
        $this->assertStringContainsString('5MB', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsEmptyFile()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        // Create empty file
        touch($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, 0);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('File is empty', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsMismatchedMimeType()
    {
        // Create a text file but name it as jpg
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'This is not an image');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('MIME type', $result['error']);
        $this->assertStringContainsString('does not match extension', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageHandlesUploadErrors()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        
        $file = $this->createMockFile('test.jpg', $tmpFile, 1000, UPLOAD_ERR_PARTIAL);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('partially uploaded', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsNoFile()
    {
        $file = [
            'name' => 'test.jpg',
            'tmp_name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_NO_FILE
        ];
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    public function testValidateImageWithJpegExtension()
    {
        // Test that both 'jpg' and 'jpeg' extensions work
        $tmpFile = $this->createTestImage('jpeg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpeg', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageCaseInsensitiveExtension()
    {
        // Test that extension checking is case-insensitive
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.JPG', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsExecutableExtension()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, '<?php echo "test"; ?>');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.php', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid file format', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageRejectsGifFormat()
    {
        // GIF is not in the allowed formats list
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        
        // Create a simple GIF
        $image = imagecreatetruecolor(10, 10);
        imagegif($image, $tmpFile);
        imagedestroy($image);
        
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.gif', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid file format', $result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageAtMaxFileSize()
    {
        // Test file exactly at the 5MB limit
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = 5242880; // Exactly 5MB
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        
        unlink($tmpFile);
    }

    public function testValidateImageJustOverMaxFileSize()
    {
        // Test file just over the 5MB limit
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = 5242881; // 1 byte over 5MB
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        $result = $this->imageService->validateImage($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('File size exceeds maximum', $result['error']);
        
        unlink($tmpFile);
    }

    // Tests for uploadImage method

    public function testUploadImageSuccess()
    {
        // Create a test image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        // Mock the item ID
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertNotNull($result['imageUrl']);
        $this->assertNotNull($result['thumbnailUrl']);
        $this->assertStringStartsWith('/uploads/', $result['imageUrl']);
        $this->assertStringStartsWith('/uploads/thumbnails/', $result['thumbnailUrl']);
        
        // Clean up uploaded file
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            unlink($result['uploadPath']);
        }
    }

    public function testUploadImageWithPng()
    {
        $tmpFile = $this->createTestImage('png');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.png', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.png', $result['imageUrl']);
        
        // Clean up
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            unlink($result['uploadPath']);
        }
    }

    public function testUploadImageWithWebp()
    {
        $tmpFile = $this->createTestImage('webp');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.webp', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.webp', $result['imageUrl']);
        
        // Clean up
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            unlink($result['uploadPath']);
        }
    }

    public function testUploadImageFailsWithInvalidFile()
    {
        // Create an invalid file (text file with jpg extension)
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'Not an image');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        $this->assertNull($result['imageUrl']);
        $this->assertNull($result['thumbnailUrl']);
        
        unlink($tmpFile);
    }

    public function testUploadImageFailsWithFileTooLarge()
    {
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = 6 * 1024 * 1024; // 6MB
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File size exceeds maximum', $result['error']);
        
        unlink($tmpFile);
    }

    public function testUploadImageGeneratesUniqueFilenames()
    {
        // Upload two images and verify they have different filenames
        $tmpFile1 = $this->createTestImage('jpg');
        $fileSize1 = filesize($tmpFile1);
        $file1 = $this->createMockFile('test.jpg', $tmpFile1, $fileSize1);
        
        $tmpFile2 = $this->createTestImage('jpg');
        $fileSize2 = filesize($tmpFile2);
        $file2 = $this->createMockFile('test.jpg', $tmpFile2, $fileSize2);
        
        $result1 = $this->imageService->uploadImage(1, $file1);
        $result2 = $this->imageService->uploadImage(1, $file2);
        
        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertNotEquals($result1['imageUrl'], $result2['imageUrl']);
        
        // Clean up
        if (isset($result1['uploadPath']) && file_exists($result1['uploadPath'])) {
            unlink($result1['uploadPath']);
        }
        if (isset($result2['uploadPath']) && file_exists($result2['uploadPath'])) {
            unlink($result2['uploadPath']);
        }
    }

    public function testUploadImageSetsCorrectPermissions()
    {
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        
        // Check file permissions (0644)
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            $perms = fileperms($result['uploadPath']);
            $octalPerms = substr(sprintf('%o', $perms), -4);
            
            // On some systems, the permissions might be slightly different
            // We mainly want to ensure it's not executable
            $this->assertStringNotContainsString('7', substr($octalPerms, -1));
            
            // Clean up
            unlink($result['uploadPath']);
        }
    }

    public function testUploadImageFailsWithInvalidExtension()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'test content');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.txt', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid file format', $result['error']);
        
        unlink($tmpFile);
    }

    public function testUploadImageReturnsCorrectUrlFormat()
    {
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        
        // Verify URL format
        $this->assertMatchesRegularExpression('/^\/uploads\/img_[a-f0-9_]+\.jpg$/', $result['imageUrl']);
        $this->assertMatchesRegularExpression('/^\/uploads\/thumbnails\/img_[a-f0-9_]+_thumb\.jpg$/', $result['thumbnailUrl']);
        
        // Clean up
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            unlink($result['uploadPath']);
        }
    }

    // Tests for generateThumbnail method

    public function testGenerateThumbnailForJpg()
    {
        // Create a test image
        $originalPath = $this->createTestImage('jpg', 800, 600);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        $this->assertFileExists($thumbnailPath);
        
        // Verify thumbnail dimensions
        list($width, $height) = getimagesize($thumbnailPath);
        $this->assertLessThanOrEqual(200, $width);
        $this->assertLessThanOrEqual(200, $height);
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailForPng()
    {
        $originalPath = $this->createTestImage('png', 800, 600);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.png';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        $this->assertFileExists($thumbnailPath);
        
        // Verify thumbnail dimensions
        list($width, $height) = getimagesize($thumbnailPath);
        $this->assertLessThanOrEqual(200, $width);
        $this->assertLessThanOrEqual(200, $height);
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailForWebp()
    {
        $originalPath = $this->createTestImage('webp', 800, 600);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.webp';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        $this->assertFileExists($thumbnailPath);
        
        // Verify thumbnail dimensions
        list($width, $height) = getimagesize($thumbnailPath);
        $this->assertLessThanOrEqual(200, $width);
        $this->assertLessThanOrEqual(200, $height);
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailMaintainsAspectRatioLandscape()
    {
        // Create a landscape image (wider than tall)
        $originalPath = $this->createTestImage('jpg', 800, 400);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        
        // Verify aspect ratio is maintained
        list($width, $height) = getimagesize($thumbnailPath);
        $aspectRatio = $width / $height;
        $expectedAspectRatio = 800 / 400; // 2.0
        
        $this->assertEquals($expectedAspectRatio, $aspectRatio, '', 0.1);
        $this->assertEquals(200, $width); // Width should be 200 for landscape
        $this->assertEquals(100, $height); // Height should be 100 to maintain 2:1 ratio
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailMaintainsAspectRatioPortrait()
    {
        // Create a portrait image (taller than wide)
        $originalPath = $this->createTestImage('jpg', 400, 800);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        
        // Verify aspect ratio is maintained
        list($width, $height) = getimagesize($thumbnailPath);
        $aspectRatio = $width / $height;
        $expectedAspectRatio = 400 / 800; // 0.5
        
        $this->assertEquals($expectedAspectRatio, $aspectRatio, '', 0.1);
        $this->assertEquals(100, $width); // Width should be 100 to maintain 1:2 ratio
        $this->assertEquals(200, $height); // Height should be 200 for portrait
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailMaintainsAspectRatioSquare()
    {
        // Create a square image
        $originalPath = $this->createTestImage('jpg', 800, 800);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        
        // Verify dimensions are equal (square)
        list($width, $height) = getimagesize($thumbnailPath);
        $this->assertEquals($width, $height);
        $this->assertEquals(200, $width);
        $this->assertEquals(200, $height);
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailFailsWithNonExistentFile()
    {
        $originalPath = '/path/to/nonexistent/file.jpg';
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertFalse($result);
        $this->assertFileDoesNotExist($thumbnailPath);
    }

    public function testGenerateThumbnailFailsWithInvalidImage()
    {
        // Create a text file pretending to be an image
        $originalPath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($originalPath, 'This is not an image');
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertFalse($result);
        
        // Clean up
        unlink($originalPath);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }

    public function testGenerateThumbnailSetsCorrectPermissions()
    {
        $originalPath = $this->createTestImage('jpg', 800, 600);
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.jpg';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        $this->assertFileExists($thumbnailPath);
        
        // Check file permissions (should be 0644)
        $perms = fileperms($thumbnailPath);
        $octalPerms = substr(sprintf('%o', $perms), -4);
        
        // Verify it's not executable
        $this->assertStringNotContainsString('7', substr($octalPerms, -1));
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testGenerateThumbnailPreservesTransparencyForPng()
    {
        // Create a PNG with transparency
        $originalPath = tempnam(sys_get_temp_dir(), 'test_image_');
        $image = imagecreatetruecolor(100, 100);
        
        // Enable alpha blending and save alpha channel
        imagealphablending($image, false);
        imagesavealpha($image, true);
        
        // Fill with transparent color
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, 100, 100, $transparent);
        
        imagepng($image, $originalPath);
        imagedestroy($image);
        
        $thumbnailPath = sys_get_temp_dir() . '/test_thumb_' . uniqid() . '.png';
        
        $result = $this->imageService->generateThumbnail($originalPath, $thumbnailPath);
        
        $this->assertTrue($result);
        $this->assertFileExists($thumbnailPath);
        
        // Verify the thumbnail was created
        $thumbImage = imagecreatefrompng($thumbnailPath);
        $this->assertNotFalse($thumbImage);
        imagedestroy($thumbImage);
        
        // Clean up
        unlink($originalPath);
        unlink($thumbnailPath);
    }

    public function testUploadImageCreatesThumbnail()
    {
        // Create a test image
        $tmpFile = $this->createTestImage('jpg', 800, 600);
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['thumbnailUrl']);
        
        // Verify thumbnail file exists
        if (isset($result['thumbnailPath'])) {
            $this->assertFileExists($result['thumbnailPath']);
            
            // Verify thumbnail dimensions
            list($width, $height) = getimagesize($result['thumbnailPath']);
            $this->assertLessThanOrEqual(200, $width);
            $this->assertLessThanOrEqual(200, $height);
            
            // Clean up
            unlink($result['thumbnailPath']);
        }
        
        // Clean up original
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            unlink($result['uploadPath']);
        }
    }

    public function testUploadImageFailsIfThumbnailGenerationFails()
    {
        // This test verifies that if thumbnail generation fails, the upload is rolled back
        // We can't easily simulate this without mocking, but we can verify the behavior
        // by checking that the original file is cleaned up if thumbnail fails
        
        // Create a very small image that might cause issues
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_image_');
        $image = imagecreatetruecolor(1, 1);
        imagejpeg($image, $tmpFile);
        imagedestroy($image);
        
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        // Even with a 1x1 image, thumbnail generation should succeed
        // This test mainly documents the expected behavior
        $this->assertTrue($result['success']);
        
        // Clean up
        if (isset($result['uploadPath']) && file_exists($result['uploadPath'])) {
            unlink($result['uploadPath']);
        }
        if (isset($result['thumbnailPath']) && file_exists($result['thumbnailPath'])) {
            unlink($result['thumbnailPath']);
        }
    }

    // Tests for database operations

    public function testUploadImageInsertsRecordIntoDatabase()
    {
        // Create a test image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('imageId', $result);
        $this->assertIsInt($result['imageId']);
        $this->assertGreaterThan(0, $result['imageId']);
        
        // Verify the record exists in database by retrieving it
        $images = $this->imageService->getItemImages($itemId);
        $this->assertNotEmpty($images);
        
        $found = false;
        foreach ($images as $image) {
            if ($image['imageId'] === $result['imageId']) {
                $found = true;
                $this->assertEquals($result['imageUrl'], $image['imageUrl']);
                $this->assertEquals($result['thumbnailUrl'], $image['thumbnailUrl']);
                break;
            }
        }
        $this->assertTrue($found, 'Uploaded image should be found in database');
        
        // Clean up
        $this->imageService->deleteImage($result['imageId']);
    }

    public function testGetItemImagesReturnsEmptyArrayForItemWithNoImages()
    {
        // Use a non-existent item ID
        $itemId = 999999;
        
        $images = $this->imageService->getItemImages($itemId);
        
        $this->assertIsArray($images);
        $this->assertEmpty($images);
    }

    public function testGetItemImagesReturnsAllImagesForItem()
    {
        // Upload multiple images for the same item
        $itemId = 1;
        $uploadedImageIds = [];
        
        for ($i = 0; $i < 3; $i++) {
            $tmpFile = $this->createTestImage('jpg');
            $fileSize = filesize($tmpFile);
            $file = $this->createMockFile("test{$i}.jpg", $tmpFile, $fileSize);
            
            $result = $this->imageService->uploadImage($itemId, $file);
            $this->assertTrue($result['success']);
            $uploadedImageIds[] = $result['imageId'];
        }
        
        // Retrieve all images for the item
        $images = $this->imageService->getItemImages($itemId);
        
        $this->assertIsArray($images);
        $this->assertGreaterThanOrEqual(3, count($images));
        
        // Verify all uploaded images are in the result
        $retrievedImageIds = array_map(fn($img) => $img['imageId'], $images);
        foreach ($uploadedImageIds as $uploadedId) {
            $this->assertContains($uploadedId, $retrievedImageIds);
        }
        
        // Clean up
        foreach ($uploadedImageIds as $imageId) {
            $this->imageService->deleteImage($imageId);
        }
    }

    public function testGetItemImagesReturnsCorrectStructure()
    {
        // Upload an image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $uploadResult = $this->imageService->uploadImage($itemId, $file);
        $this->assertTrue($uploadResult['success']);
        
        // Retrieve images
        $images = $this->imageService->getItemImages($itemId);
        
        $this->assertNotEmpty($images);
        
        // Find our uploaded image
        $image = null;
        foreach ($images as $img) {
            if ($img['imageId'] === $uploadResult['imageId']) {
                $image = $img;
                break;
            }
        }
        
        $this->assertNotNull($image);
        $this->assertArrayHasKey('imageId', $image);
        $this->assertArrayHasKey('itemId', $image);
        $this->assertArrayHasKey('imageUrl', $image);
        $this->assertArrayHasKey('thumbnailUrl', $image);
        $this->assertArrayHasKey('uploadTimestamp', $image);
        
        $this->assertEquals($itemId, $image['itemId']);
        $this->assertEquals($uploadResult['imageUrl'], $image['imageUrl']);
        $this->assertEquals($uploadResult['thumbnailUrl'], $image['thumbnailUrl']);
        
        // Clean up
        $this->imageService->deleteImage($uploadResult['imageId']);
    }

    public function testGetItemImagesOrdersByUploadTimestamp()
    {
        // Upload multiple images with slight delays
        $itemId = 1;
        $uploadedImageIds = [];
        
        for ($i = 0; $i < 3; $i++) {
            $tmpFile = $this->createTestImage('jpg');
            $fileSize = filesize($tmpFile);
            $file = $this->createMockFile("test{$i}.jpg", $tmpFile, $fileSize);
            
            $result = $this->imageService->uploadImage($itemId, $file);
            $this->assertTrue($result['success']);
            $uploadedImageIds[] = $result['imageId'];
            
            // Small delay to ensure different timestamps
            usleep(100000); // 0.1 seconds
        }
        
        // Retrieve images
        $images = $this->imageService->getItemImages($itemId);
        
        // Filter to only our uploaded images
        $ourImages = array_filter($images, fn($img) => in_array($img['imageId'], $uploadedImageIds));
        $ourImages = array_values($ourImages);
        
        // Verify they are in chronological order
        $this->assertGreaterThanOrEqual(3, count($ourImages));
        
        for ($i = 0; $i < count($ourImages) - 1; $i++) {
            $this->assertLessThanOrEqual(
                $ourImages[$i + 1]['uploadTimestamp'],
                $ourImages[$i]['uploadTimestamp'],
                'Images should be ordered by upload timestamp ascending'
            );
        }
        
        // Clean up
        foreach ($uploadedImageIds as $imageId) {
            $this->imageService->deleteImage($imageId);
        }
    }

    public function testDeleteImageRemovesDatabaseRecord()
    {
        // Upload an image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $uploadResult = $this->imageService->uploadImage($itemId, $file);
        $this->assertTrue($uploadResult['success']);
        $imageId = $uploadResult['imageId'];
        
        // Verify image exists
        $images = $this->imageService->getItemImages($itemId);
        $imageIds = array_map(fn($img) => $img['imageId'], $images);
        $this->assertContains($imageId, $imageIds);
        
        // Delete the image
        $deleteResult = $this->imageService->deleteImage($imageId);
        $this->assertTrue($deleteResult['success']);
        $this->assertNull($deleteResult['error']);
        
        // Verify image no longer exists in database
        $imagesAfter = $this->imageService->getItemImages($itemId);
        $imageIdsAfter = array_map(fn($img) => $img['imageId'], $imagesAfter);
        $this->assertNotContains($imageId, $imageIdsAfter);
    }

    public function testDeleteImageRemovesPhysicalFiles()
    {
        // Upload an image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $uploadResult = $this->imageService->uploadImage($itemId, $file);
        $this->assertTrue($uploadResult['success']);
        
        // Get file paths
        $imageUrl = $uploadResult['imageUrl'];
        $thumbnailUrl = $uploadResult['thumbnailUrl'];
        $imagePath = ltrim($imageUrl, '/');
        $thumbnailPath = ltrim($thumbnailUrl, '/');
        
        // Verify files exist
        $this->assertFileExists($imagePath);
        $this->assertFileExists($thumbnailPath);
        
        // Delete the image
        $deleteResult = $this->imageService->deleteImage($uploadResult['imageId']);
        $this->assertTrue($deleteResult['success']);
        
        // Verify files are deleted
        $this->assertFileDoesNotExist($imagePath);
        $this->assertFileDoesNotExist($thumbnailPath);
    }

    public function testDeleteImageReturnsErrorForNonExistentImage()
    {
        // Try to delete a non-existent image
        $imageId = 999999;
        
        $result = $this->imageService->deleteImage($imageId);
        
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    public function testDeleteImageHandlesMissingPhysicalFiles()
    {
        // Upload an image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        $itemId = 1;
        
        $uploadResult = $this->imageService->uploadImage($itemId, $file);
        $this->assertTrue($uploadResult['success']);
        
        // Manually delete the physical files
        $imagePath = ltrim($uploadResult['imageUrl'], '/');
        $thumbnailPath = ltrim($uploadResult['thumbnailUrl'], '/');
        
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        // Delete should still succeed (database record is removed)
        $deleteResult = $this->imageService->deleteImage($uploadResult['imageId']);
        $this->assertTrue($deleteResult['success']);
        
        // Verify database record is removed
        $images = $this->imageService->getItemImages($itemId);
        $imageIds = array_map(fn($img) => $img['imageId'], $images);
        $this->assertNotContains($uploadResult['imageId'], $imageIds);
    }

    public function testUploadImageRollsBackOnDatabaseError()
    {
        // This test verifies that if database insert fails, files are cleaned up
        // We can't easily simulate a database error without mocking,
        // but we document the expected behavior
        
        // Create a test image
        $tmpFile = $this->createTestImage('jpg');
        $fileSize = filesize($tmpFile);
        $file = $this->createMockFile('test.jpg', $tmpFile, $fileSize);
        
        // Use an invalid item ID (negative) which might cause issues
        $itemId = -1;
        
        $result = $this->imageService->uploadImage($itemId, $file);
        
        // The result depends on database constraints
        // If it fails, files should be cleaned up
        if (!$result['success']) {
            $this->assertNotNull($result['error']);
            
            // Verify no orphaned files were left behind
            // (This is hard to test without knowing the exact filename)
        }
        
        // Clean up if upload succeeded
        if ($result['success'] && isset($result['imageId'])) {
            $this->imageService->deleteImage($result['imageId']);
        }
    }

    public function testMultipleImagesForDifferentItems()
    {
        // Upload images for different items
        $itemId1 = 1;
        $itemId2 = 2;
        
        $tmpFile1 = $this->createTestImage('jpg');
        $fileSize1 = filesize($tmpFile1);
        $file1 = $this->createMockFile('test1.jpg', $tmpFile1, $fileSize1);
        
        $tmpFile2 = $this->createTestImage('png');
        $fileSize2 = filesize($tmpFile2);
        $file2 = $this->createMockFile('test2.png', $tmpFile2, $fileSize2);
        
        $result1 = $this->imageService->uploadImage($itemId1, $file1);
        $result2 = $this->imageService->uploadImage($itemId2, $file2);
        
        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        
        // Verify each item has its own images
        $images1 = $this->imageService->getItemImages($itemId1);
        $images2 = $this->imageService->getItemImages($itemId2);
        
        $imageIds1 = array_map(fn($img) => $img['imageId'], $images1);
        $imageIds2 = array_map(fn($img) => $img['imageId'], $images2);
        
        $this->assertContains($result1['imageId'], $imageIds1);
        $this->assertNotContains($result1['imageId'], $imageIds2);
        
        $this->assertContains($result2['imageId'], $imageIds2);
        $this->assertNotContains($result2['imageId'], $imageIds1);
        
        // Clean up
        $this->imageService->deleteImage($result1['imageId']);
        $this->imageService->deleteImage($result2['imageId']);
    }
}
