<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    use ApiResponse;

    /**
     * Detect user's location based on IP address
     */
    public function detect(Request $request)
    {
        // Get the user's IP address
        $ip = $this->getUserIp($request);

        // For localhost/development, use a default location
        if ($this->isLocalIp($ip)) {
            return $this->success([
                'country' => 'Kenya',
                'country_code' => 'KE',
                'city' => 'Nairobi',
                'region' => 'Nairobi',
                'lat' => -1.2921,
                'lon' => 36.8219,
                'ip' => $ip,
                'is_default' => true,
            ]);
        }

        // Check cache first
        $cacheKey = 'ip_location_'.md5($ip);
        $cachedLocation = Cache::get($cacheKey);

        if ($cachedLocation) {
            return $this->success($cachedLocation);
        }

        // Try multiple IP geolocation services
        $location = $this->getLocationFromIpApi($ip);

        if (! $location) {
            $location = $this->getLocationFromIpApiCo($ip);
        }

        if (! $location) {
            // Fallback to default location
            $location = [
                'country' => 'Kenya',
                'country_code' => 'KE',
                'city' => 'Nairobi',
                'region' => 'Nairobi',
                'lat' => -1.2921,
                'lon' => 36.8219,
                'ip' => $ip,
                'is_default' => true,
            ];
        }

        // Cache for 1 hour
        Cache::put($cacheKey, $location, 3600);

        return $this->success($location);
    }

    /**
     * Get user's real IP address
     */
    private function getUserIp(Request $request): string
    {
        // Check for IP behind proxy/load balancer
        if ($request->header('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));

            return trim($ips[0]);
        }

        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        return $request->ip() ?? '127.0.0.1';
    }

    /**
     * Check if IP is local/private
     */
    private function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1']) ||
               filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Get location from ip-api.com (free, no key required)
     */
    private function getLocationFromIpApi(string $ip): ?array
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? 'Unknown',
                        'country_code' => $data['countryCode'] ?? 'XX',
                        'city' => $data['city'] ?? 'Unknown',
                        'region' => $data['regionName'] ?? 'Unknown',
                        'lat' => $data['lat'] ?? 0,
                        'lon' => $data['lon'] ?? 0,
                        'ip' => $ip,
                        'is_default' => false,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Log error but don't expose to user
            logger()->error('IP geolocation failed (ip-api.com): '.$e->getMessage());
        }

        return null;
    }

    /**
     * Get location from ipapi.co (free tier available)
     */
    private function getLocationFromIpApiCo(string $ip): ?array
    {
        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();

                if (! isset($data['error'])) {
                    return [
                        'country' => $data['country_name'] ?? 'Unknown',
                        'country_code' => $data['country_code'] ?? 'XX',
                        'city' => $data['city'] ?? 'Unknown',
                        'region' => $data['region'] ?? 'Unknown',
                        'lat' => $data['latitude'] ?? 0,
                        'lon' => $data['longitude'] ?? 0,
                        'ip' => $ip,
                        'is_default' => false,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Log error but don't expose to user
            logger()->error('IP geolocation failed (ipapi.co): '.$e->getMessage());
        }

        return null;
    }
}
