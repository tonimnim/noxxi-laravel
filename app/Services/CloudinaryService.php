<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Transformation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CloudinaryService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        // Parse Cloudinary URL
        $cloudUrl = config('cloudinary.cloud_url');
        
        // Extract credentials from URL
        preg_match('/cloudinary:\/\/(\d+):([^@]+)@(.+)/', $cloudUrl, $matches);
        
        $config = [
            'cloud' => [
                'cloud_name' => $matches[3] ?? '',
                'api_key' => $matches[1] ?? '',
                'api_secret' => $matches[2] ?? '',
            ],
            'url' => [
                'secure' => true,
            ],
        ];
        
        // In development, disable SSL verification (Windows cURL issue)
        if (app()->environment('local', 'development')) {
            $config['api'] = [
                'upload' => [
                    'curl_options' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ],
                ],
            ];
        }
        
        $this->cloudinary = new Cloudinary($config);
    }

    /**
     * Upload an image to Cloudinary
     */
    public function uploadImage(UploadedFile $file, string $folder = 'events', array $options = []): array
    {
        $publicId = $folder . '/' . Str::random(20);
        
        $uploadOptions = array_merge([
            'folder' => $folder,
            'public_id' => $publicId,
            'resource_type' => 'image',
            'quality' => 'auto:good',
            'fetch_format' => 'auto',
        ], $options);

        // Add SSL workaround for local development
        if (app()->environment('local', 'development')) {
            $uploadOptions['api_options'] = [
                'verify' => false,  // Disable SSL verification
            ];
        }

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $uploadOptions
            );

            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'size' => $result['bytes'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple(array $files, string $folder = 'events'): array
    {
        $results = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = $this->uploadImage($file, $folder);
            }
        }
        
        return $results;
    }

    /**
     * Get transformed image URL
     */
    public function getTransformedUrl(string $publicId, string $transformation = 'event_thumbnail'): string
    {
        $config = config('cloudinary.transformations.' . $transformation, []);
        
        if (empty($config)) {
            // Return original if transformation not found
            return $this->cloudinary->image($publicId)->toUrl();
        }

        // Build transformation string for URL
        $transformations = [];
        
        if (isset($config['width']) && isset($config['height'])) {
            $crop = $config['crop'] ?? 'fill';
            $transformations[] = "w_{$config['width']},h_{$config['height']},c_{$crop}";
        } elseif (isset($config['width'])) {
            $crop = $config['crop'] ?? 'fill';
            $transformations[] = "w_{$config['width']},c_{$crop}";
        } elseif (isset($config['height'])) {
            $crop = $config['crop'] ?? 'fill';
            $transformations[] = "h_{$config['height']},c_{$crop}";
        }
        
        if (isset($config['quality'])) {
            $transformations[] = "q_{$config['quality']}";
        }
        
        if (isset($config['format'])) {
            $transformations[] = "f_{$config['format']}";
        }
        
        if (isset($config['gravity'])) {
            $transformations[] = "g_{$config['gravity']}";
        }

        // Get base URL and apply transformations
        $baseUrl = $this->cloudinary->image($publicId)->toUrl();
        
        if (!empty($transformations)) {
            // Insert transformation string into URL
            // URL format: https://res.cloudinary.com/[cloud]/image/upload/[transformations]/[version]/[public_id].[ext]
            $transformationString = implode(',', $transformations);
            $baseUrl = str_replace('/image/upload/', '/image/upload/' . $transformationString . '/', $baseUrl);
        }
        
        return $baseUrl;
    }

    /**
     * Delete an image from Cloudinary
     */
    public function deleteImage(string $publicId): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get responsive image URLs for different screen sizes
     */
    public function getResponsiveUrls(string $publicId): array
    {
        return [
            'mobile' => $this->getTransformedUrl($publicId, 'event_card'),
            'tablet' => $this->getTransformedUrl($publicId, 'event_thumbnail'),
            'desktop' => $this->getTransformedUrl($publicId, 'event_banner'),
            'original' => $this->cloudinary->image($publicId)->toUrl(),
        ];
    }

    /**
     * Transform a Cloudinary URL with dynamic sizing
     * Accepts existing Cloudinary URLs and applies transformations
     */
    public function transformCloudinaryUrl(string $url, int $width = null, int $height = null, string $crop = 'fill', string $gravity = 'auto'): string
    {
        // Check if it's a Cloudinary URL
        if (!str_contains($url, 'cloudinary.com')) {
            return $url;
        }

        // Extract components from the URL
        // Example: https://res.cloudinary.com/dpbheqr2n/image/upload/v1757119157/events/events/0C0bzh1s9KhQwK1d2CAB.jpg
        $pattern = '/(.+\/image\/upload)(\/v\d+)?\/(.+?)(\.[a-z]+)?$/i';
        if (preg_match($pattern, $url, $matches)) {
            $baseUrl = $matches[1];
            $version = $matches[2] ?? '';
            $publicId = $matches[3];
            $extension = $matches[4] ?? '.jpg';
            
            // Build transformation string
            $transformations = [];
            if ($width && $height) {
                $transformations[] = "w_{$width},h_{$height},c_{$crop},g_{$gravity}";
            } elseif ($width) {
                $transformations[] = "w_{$width},c_{$crop}";
            } elseif ($height) {
                $transformations[] = "h_{$height},c_{$crop}";
            }
            
            // Add quality and format
            $transformations[] = 'q_auto';
            $transformations[] = 'f_auto';
            
            // Construct the new URL
            $transformationString = implode(',', $transformations);
            return "{$baseUrl}/{$transformationString}{$version}/{$publicId}{$extension}";
        }

        return $url;
    }

    /**
     * Get optimized image URL for mobile cards
     */
    public function getMobileCardUrl(string $url, int $width = 400, int $height = 300): string
    {
        return $this->transformCloudinaryUrl($url, $width, $height, 'fill');
    }

    /**
     * Get optimized image URL for list thumbnails
     */
    public function getListThumbnailUrl(string $url, int $width = 150, int $height = 150): string
    {
        return $this->transformCloudinaryUrl($url, $width, $height, 'thumb');
    }
}