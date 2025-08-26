<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CityController extends Controller
{
    use ApiResponse;

    /**
     * Get all cities grouped by country
     */
    public function index(Request $request)
    {
        $cities = Cache::remember('cities_grouped', 3600, function () {
            return City::active()
                ->major()
                ->select('id', 'name', 'country', 'country_code', 'region')
                ->orderBy('country')
                ->orderBy('population', 'desc')
                ->get()
                ->groupBy('country')
                ->map(function ($cities, $country) {
                    return [
                        'country' => $country,
                        'country_code' => $cities->first()->country_code,
                        'region' => $cities->first()->region,
                        'cities' => $cities->map(function ($city) {
                            return [
                                'id' => $city->id,
                                'name' => $city->name,
                                'display_name' => $city->name.', '.$city->country,
                            ];
                        })->values(),
                    ];
                })
                ->values();
        });

        return $this->success($cities);
    }

    /**
     * Search cities by name
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (! $query || strlen($query) < 2) {
            return $this->success([]);
        }

        $cities = City::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('country', 'ILIKE', "%{$query}%");
            })
            ->select('id', 'name', 'country', 'country_code', 'region')
            ->orderBy('population', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'country' => $city->country,
                    'display_name' => $city->name.', '.$city->country,
                    'region' => $city->region,
                ];
            });

        return $this->success($cities);
    }

    /**
     * Get popular cities for quick selection
     */
    public function popular()
    {
        $cities = Cache::remember('popular_cities', 3600, function () {
            // Get capitals and major cities
            return City::active()
                ->where(function ($q) {
                    $q->where('is_capital', true)
                        ->orWhere('population', '>', 1000000);
                })
                ->select('id', 'name', 'country', 'country_code', 'region', 'population')
                ->orderBy('population', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($city) {
                    return [
                        'id' => $city->id,
                        'name' => $city->name,
                        'country' => $city->country,
                        'display_name' => $city->name.', '.$city->country,
                        'region' => $city->region,
                    ];
                });
        });

        return $this->success($cities);
    }

    /**
     * Get cities by region
     */
    public function byRegion($region)
    {
        $validRegions = ['East Africa', 'West Africa', 'North Africa', 'Southern Africa', 'Central Africa'];

        if (! in_array($region, $validRegions)) {
            return $this->error('Invalid region', 400);
        }

        $cities = Cache::remember("cities_region_{$region}", 3600, function () use ($region) {
            return City::active()
                ->byRegion($region)
                ->major()
                ->select('id', 'name', 'country', 'country_code')
                ->orderBy('country')
                ->orderBy('population', 'desc')
                ->get()
                ->groupBy('country')
                ->map(function ($cities, $country) {
                    return [
                        'country' => $country,
                        'cities' => $cities->map(function ($city) {
                            return [
                                'id' => $city->id,
                                'name' => $city->name,
                                'display_name' => $city->name.', '.$city->country,
                            ];
                        })->values(),
                    ];
                })
                ->values();
        });

        return $this->success($cities);
    }
}
