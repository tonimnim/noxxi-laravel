<?php

namespace App\Filament\Admin\Widgets\System;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class GeographicHeatMapWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.system.geographic-heat-map';
    
    protected static ?int $sort = 3;
    
    // Takes one column in 2-column layout
    protected int | string | array $columnSpan = 1;
    
    // Lazy load for performance
    protected static bool $isLazy = true;
    
    // No auto polling
    protected static ?string $pollingInterval = null;
    
    protected function getViewData(): array
    {
        // Get pre-aggregated data from cache
        $geoData = Cache::get('admin.geographic.data', [
            'countries' => [],
            'top_countries' => [],
            'summary' => [
                'total_countries' => 0,
                'total_users' => 0,
                'total_revenue' => 0,
                'most_active' => null,
            ],
            'last_updated' => null,
        ]);
        
        // If no data, trigger aggregation job
        if (empty($geoData['countries']) && !Cache::has('admin.geographic.aggregation_running')) {
            Cache::put('admin.geographic.aggregation_running', true, 60);
            dispatch(new \App\Jobs\AggregateGeographicData());
        }
        
        // Get African countries configuration
        $africanCountries = $this->getAfricanCountries();
        
        // Merge country data with configuration
        $mapData = [];
        foreach ($africanCountries as $code => $country) {
            $countryStats = $geoData['countries'][$country['name']] ?? null;
            
            $mapData[$code] = [
                'name' => $country['name'],
                'users' => $countryStats['users'] ?? 0,
                'organizers' => $countryStats['organizers'] ?? 0,
                'listings' => $countryStats['listings'] ?? 0,
                'bookings' => $countryStats['bookings'] ?? 0,
                'revenue' => $countryStats['revenue'] ?? 0,
                'heat_level' => $countryStats['heat_level'] ?? 'very-low',
                'normalized_score' => $countryStats['normalized_score'] ?? 0,
                'currency' => $country['currency'] ?? 'USD',
            ];
        }
        
        return [
            'mapData' => $mapData,
            'topCountries' => $geoData['top_countries'] ?? [],
            'summary' => $geoData['summary'],
            'lastUpdated' => $geoData['last_updated'],
            'isLoading' => empty($geoData['countries']),
        ];
    }
    
    /**
     * Get African countries configuration
     */
    protected function getAfricanCountries(): array
    {
        return [
            'DZ' => ['name' => 'Algeria', 'currency' => 'DZD'],
            'AO' => ['name' => 'Angola', 'currency' => 'AOA'],
            'BJ' => ['name' => 'Benin', 'currency' => 'XOF'],
            'BW' => ['name' => 'Botswana', 'currency' => 'BWP'],
            'BF' => ['name' => 'Burkina Faso', 'currency' => 'XOF'],
            'BI' => ['name' => 'Burundi', 'currency' => 'BIF'],
            'CM' => ['name' => 'Cameroon', 'currency' => 'XAF'],
            'CV' => ['name' => 'Cape Verde', 'currency' => 'CVE'],
            'CF' => ['name' => 'Central African Republic', 'currency' => 'XAF'],
            'TD' => ['name' => 'Chad', 'currency' => 'XAF'],
            'KM' => ['name' => 'Comoros', 'currency' => 'KMF'],
            'CG' => ['name' => 'Congo', 'currency' => 'XAF'],
            'CD' => ['name' => 'Congo DRC', 'currency' => 'CDF'],
            'CI' => ['name' => 'Côte d\'Ivoire', 'currency' => 'XOF'],
            'DJ' => ['name' => 'Djibouti', 'currency' => 'DJF'],
            'EG' => ['name' => 'Egypt', 'currency' => 'EGP'],
            'GQ' => ['name' => 'Equatorial Guinea', 'currency' => 'XAF'],
            'ER' => ['name' => 'Eritrea', 'currency' => 'ERN'],
            'ET' => ['name' => 'Ethiopia', 'currency' => 'ETB'],
            'GA' => ['name' => 'Gabon', 'currency' => 'XAF'],
            'GM' => ['name' => 'Gambia', 'currency' => 'GMD'],
            'GH' => ['name' => 'Ghana', 'currency' => 'GHS'],
            'GN' => ['name' => 'Guinea', 'currency' => 'GNF'],
            'GW' => ['name' => 'Guinea-Bissau', 'currency' => 'XOF'],
            'KE' => ['name' => 'Kenya', 'currency' => 'KES'],
            'LS' => ['name' => 'Lesotho', 'currency' => 'LSL'],
            'LR' => ['name' => 'Liberia', 'currency' => 'LRD'],
            'LY' => ['name' => 'Libya', 'currency' => 'LYD'],
            'MG' => ['name' => 'Madagascar', 'currency' => 'MGA'],
            'MW' => ['name' => 'Malawi', 'currency' => 'MWK'],
            'ML' => ['name' => 'Mali', 'currency' => 'XOF'],
            'MR' => ['name' => 'Mauritania', 'currency' => 'MRU'],
            'MU' => ['name' => 'Mauritius', 'currency' => 'MUR'],
            'MA' => ['name' => 'Morocco', 'currency' => 'MAD'],
            'MZ' => ['name' => 'Mozambique', 'currency' => 'MZN'],
            'NA' => ['name' => 'Namibia', 'currency' => 'NAD'],
            'NE' => ['name' => 'Niger', 'currency' => 'XOF'],
            'NG' => ['name' => 'Nigeria', 'currency' => 'NGN'],
            'RW' => ['name' => 'Rwanda', 'currency' => 'RWF'],
            'ST' => ['name' => 'São Tomé and Príncipe', 'currency' => 'STN'],
            'SN' => ['name' => 'Senegal', 'currency' => 'XOF'],
            'SC' => ['name' => 'Seychelles', 'currency' => 'SCR'],
            'SL' => ['name' => 'Sierra Leone', 'currency' => 'SLL'],
            'SO' => ['name' => 'Somalia', 'currency' => 'SOS'],
            'ZA' => ['name' => 'South Africa', 'currency' => 'ZAR'],
            'SS' => ['name' => 'South Sudan', 'currency' => 'SSP'],
            'SD' => ['name' => 'Sudan', 'currency' => 'SDG'],
            'SZ' => ['name' => 'Eswatini', 'currency' => 'SZL'],
            'TZ' => ['name' => 'Tanzania', 'currency' => 'TZS'],
            'TG' => ['name' => 'Togo', 'currency' => 'XOF'],
            'TN' => ['name' => 'Tunisia', 'currency' => 'TND'],
            'UG' => ['name' => 'Uganda', 'currency' => 'UGX'],
            'ZM' => ['name' => 'Zambia', 'currency' => 'ZMW'],
            'ZW' => ['name' => 'Zimbabwe', 'currency' => 'ZWL'],
        ];
    }
}