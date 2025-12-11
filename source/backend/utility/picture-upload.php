<?php

namespace App\Utility;

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
     * Private constructor.
     *
     * Initializes the Cloudinary SDK configuration using environment variables.
     * This constructor ensures required environment variables are present and then
     * configures the Cloudinary client with the provided credentials and secure URL option.
     *
     * It performs the following steps:
     * - Verifies that the following environment variables exist in $_ENV:
     *      - CLOUDINARY_NAME: Cloudinary cloud name
     *      - CLOUDINARY_API:  Cloudinary API key
     *      - CLOUDINARY_SECRET: Cloudinary API secret
     * - Calls Configuration::instance() to set:
     *      - cloud.cloud_name
     *      - cloud.api_key
     *      - cloud.api_secret
     *      - url.secure = true
     *
     * @throws \Exception If any of the required Cloudinary environment variables are not set.
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
     * Returns the singleton instance of this class.
     *
     * Implements lazy initialization for the singleton pattern:
     * - Checks the private static $instance property
     * - Creates and stores a new instance if none exists
     * - Returns the existing instance otherwise
     *
     * Ensures a single shared instance is used throughout the application.
     * Note: The class constructor should be private to prevent direct instantiation.
     *
     * @return self The singleton instance of this class
     */
    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Uploads a file to cloud storage and returns its storage URL or path.
     *
     * This method performs required initialization and validation before delegating
     * the actual transfer to the cloud upload helper:
     * - Ensures the singleton instance is initialized via self::getInstance()
     * - Validates that the provided file MIME type ($file['type']) is allowed (must be in self::ALLOWED_TYPES)
     * - Delegates upload to self::uploadToCloud using the temporary file path and original filename
     *
     * @param array $file Associative array representing the uploaded file with the following keys:
     *      - tmp_name: string   Temporary file path on the local filesystem (required)
     *      - name: string       Original filename as supplied by the client (required)
     *      - type: string       MIME type of the file (required)
     *      - size: int|null     File size in bytes (optional)
     *      - error: int|null    Upload error code as provided by PHP (optional)
     *
     * @return string URL or storage path returned by the cloud upload implementation
     *
     * @throws ValidationException If the file MIME type is not allowed
     * @throws Exception Re-throws any other exception that occurs during initialization or upload
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
     * Uploads an image file to Cloudinary and returns its secure URL.
     *
     * This method performs the following steps:
     * - Calls Cloudinary's UploadApi to upload the file located at $filePath.
     * - Generates a deterministic public_id in the "profile_pictures" folder using:
     *     pathinfo($fileName, PATHINFO_FILENAME) . '_' . UUID::toString(UUID::get())
     * - Sets upload options: folder => 'profile_pictures', overwrite => true, resource_type => 'image'.
     * - Normalizes the UploadApi result into an associative array and extracts the 'secure_url'.
     * - Throws an Exception if the upload fails or if no secure_url is returned.
     *
     * @param string $filePath Absolute or relative filesystem path to the image file to upload.
     * @param string $fileName Original filename (used to build the Cloudinary public_id).
     *
     * @return string Secure (HTTPS) URL of the uploaded image on Cloudinary.
     *
     * @throws Exception If the Cloudinary upload fails or the response does not contain a secure_url.
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