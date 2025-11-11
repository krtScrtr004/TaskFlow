<?php

namespace App\Utility;

use App\Core\Me;
use App\Core\UUID;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Dotenv\Exception\ValidationException;
use Exception;

class PictureUpload
{
    private const URL = "https://api.imghippo.com/v1/upload";
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png'];
    private static ?self $instance = null;

    /**
     * Initializes Cloudinary configuration using environment variables.
     *
     * This constructor checks for the presence of required Cloudinary environment variables:
     * - CLOUDINARY_NAME
     * - CLOUDINARY_API
     * - CLOUDINARY_SECRET
     *
     * If any variable is missing, an Exception is thrown. Otherwise, it sets up the Cloudinary
     * configuration with secure URLs enabled.
     *
     * @throws Exception If any required Cloudinary environment variable is not set
     */
    private function __construct()
    {
        if (!isset($_ENV['CLOUDINARY_NAME'], $_ENV['CLOUDINARY_API'], $_ENV['CLOUDINARY_SECRET'])) {
            throw new Exception("Cloudinary environment variables are not set");
        }

        Configuration::instance([
            'cloud' => [
                'cloud_name' => $_ENV['CLOUDINARY_NAME'],
                'api_key' => $_ENV['CLOUDINARY_API'],
                'api_secret' => $_ENV['CLOUDINARY_SECRET']
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    /**
     * Gets the singleton instance of PictureUpload.
     *
     * @throws Exception If Cloudinary environment variables are not set
     * @return self The singleton instance
     */
    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Uploads a picture file to the cloud storage.
     *
     * @param array $file Associative array containing file upload data with the following keys:
     *      - name: string Original filename
     *      - type: string MIME type of the file
     *      - tmp_name: string Temporary file path on the server
     *      - error: int Upload error code
     *      - size: int File size in bytes
     *
     * @throws ValidationException If the file type is not allowed
     * @throws Exception For any other errors during upload
     *
     * @return string URL of the uploaded picture
     */
    public static function upload(array $file): string
    {
        try {
            self::getInstance(); // Initialize singleton

            if (!in_array($file['type'], self::ALLOWED_TYPES)) {
                throw new ValidationException("Invalid file type");
            }

            return self::uploadToCloud($file['tmp_name'], $file['name']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Uploads an image file to the cloud storage and returns its secure URL.
     *
     * @param string $filePath The local file path of the image to upload.
     * @param string $fileName The original name of the image file.
     *
     * @throws Exception If the upload fails or no secure URL is returned.
     *
     * @return string The secure URL of the uploaded image.
     */
    public static function uploadToCloud(string $filePath, string $fileName): string
    {
        try {
            $uploadResult = (new UploadApi())->upload($filePath, [
                'public_id' => 'profile_pictures/' . pathinfo($fileName, PATHINFO_FILENAME) . '_' . UUID::toString(UUID::get()),
                'folder' => 'profile_pictures',
                'overwrite' => true,
                'resource_type' => 'image'
            ]);
            $response = json_decode(json_encode($uploadResult), true);
            if (!$response || !isset($response['secure_url'])) {
                throw new Exception("Image upload failed: No secure URL returned");
            }

            return $response['secure_url'];
        } catch (Exception $e) {
            throw $e;
        }
    }
}