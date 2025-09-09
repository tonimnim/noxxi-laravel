<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AfricanCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = $this->getAfricanCities();

        foreach ($cities as $cityData) {
            $slug = Str::slug($cityData['name'].'-'.$cityData['country_code']);
            
            City::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $cityData['name'],
                    'country' => $cityData['country'],
                    'country_code' => $cityData['country_code'],
                    'region' => $cityData['region'],
                    'state_province' => $cityData['state_province'] ?? null,
                    'latitude' => $cityData['latitude'] ?? null,
                    'longitude' => $cityData['longitude'] ?? null,
                    'population' => $cityData['population'] ?? null,
                    'is_capital' => $cityData['is_capital'] ?? false,
                    'is_major' => $cityData['is_major'] ?? true,
                    'timezone' => $cityData['timezone'] ?? null,
                    'is_active' => true,
                ]);
        }
    }

    private function getAfricanCities(): array
    {
        return [
            // EAST AFRICA

            // Kenya
            ['name' => 'Nairobi', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -1.2921, 'longitude' => 36.8219, 'population' => 4397073, 'is_capital' => true, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Mombasa', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -4.0435, 'longitude' => 39.6682, 'population' => 1208333, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Kisumu', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.1022, 'longitude' => 34.7617, 'population' => 610082, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Nakuru', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.3031, 'longitude' => 36.0800, 'population' => 570674, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Eldoret', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => 0.5143, 'longitude' => 35.2698, 'population' => 475716, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Thika', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -1.0332, 'longitude' => 37.0693, 'population' => 279429, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Malindi', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -3.2175, 'longitude' => 40.1191, 'population' => 119859, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Kitale', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => 1.0157, 'longitude' => 35.0062, 'population' => 162174, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Garissa', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.4536, 'longitude' => 39.6401, 'population' => 163775, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Nyeri', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.4197, 'longitude' => 36.9478, 'population' => 140338, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Machakos', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -1.5177, 'longitude' => 37.2634, 'population' => 150041, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Naivasha', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.7174, 'longitude' => 36.4310, 'population' => 198000, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Narok', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -1.0784, 'longitude' => 35.8731, 'population' => 55000, 'timezone' => 'Africa/Nairobi'],

            // Tanzania
            ['name' => 'Dar es Salaam', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -6.7924, 'longitude' => 39.2083, 'population' => 6368000, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Dodoma', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -6.1730, 'longitude' => 35.7516, 'population' => 410956, 'is_capital' => true, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Mwanza', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -2.5164, 'longitude' => 32.9175, 'population' => 1120000, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Zanzibar City', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -6.1659, 'longitude' => 39.2026, 'population' => 698000, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Arusha', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -3.3869, 'longitude' => 36.6830, 'population' => 617000, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Mbeya', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -8.9094, 'longitude' => 33.4608, 'population' => 385279, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Morogoro', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -6.8278, 'longitude' => 37.6591, 'population' => 315866, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Tanga', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -5.0689, 'longitude' => 39.0988, 'population' => 273332, 'timezone' => 'Africa/Dar_es_Salaam'],

            // Uganda
            ['name' => 'Kampala', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 0.3476, 'longitude' => 32.5825, 'population' => 1650800, 'is_capital' => true, 'timezone' => 'Africa/Kampala'],
            ['name' => 'Gulu', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 2.7747, 'longitude' => 32.2988, 'population' => 177400, 'timezone' => 'Africa/Kampala'],
            ['name' => 'Lira', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 2.2350, 'longitude' => 32.8997, 'population' => 124800, 'timezone' => 'Africa/Kampala'],
            ['name' => 'Mbale', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 1.0647, 'longitude' => 34.1796, 'population' => 96189, 'timezone' => 'Africa/Kampala'],
            ['name' => 'Jinja', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 0.4244, 'longitude' => 33.2041, 'population' => 76158, 'timezone' => 'Africa/Kampala'],
            ['name' => 'Entebbe', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 0.0512, 'longitude' => 32.4637, 'population' => 70219, 'timezone' => 'Africa/Kampala'],
            ['name' => 'Mbarara', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => -0.6134, 'longitude' => 30.6545, 'population' => 195013, 'timezone' => 'Africa/Kampala'],

            // Rwanda
            ['name' => 'Kigali', 'country' => 'Rwanda', 'country_code' => 'RW', 'region' => 'East Africa', 'latitude' => -1.9441, 'longitude' => 30.0619, 'population' => 1257000, 'is_capital' => true, 'timezone' => 'Africa/Kigali'],
            ['name' => 'Butare', 'country' => 'Rwanda', 'country_code' => 'RW', 'region' => 'East Africa', 'latitude' => -2.5975, 'longitude' => 29.7394, 'population' => 89600, 'timezone' => 'Africa/Kigali'],
            ['name' => 'Gitarama', 'country' => 'Rwanda', 'country_code' => 'RW', 'region' => 'East Africa', 'latitude' => -2.0736, 'longitude' => 29.7564, 'population' => 87613, 'timezone' => 'Africa/Kigali'],
            ['name' => 'Gisenyi', 'country' => 'Rwanda', 'country_code' => 'RW', 'region' => 'East Africa', 'latitude' => -1.7064, 'longitude' => 29.2564, 'population' => 136830, 'timezone' => 'Africa/Kigali'],

            // Burundi
            ['name' => 'Bujumbura', 'country' => 'Burundi', 'country_code' => 'BI', 'region' => 'East Africa', 'latitude' => -3.3731, 'longitude' => 29.3589, 'population' => 1124000, 'timezone' => 'Africa/Bujumbura'],
            ['name' => 'Gitega', 'country' => 'Burundi', 'country_code' => 'BI', 'region' => 'East Africa', 'latitude' => -3.4264, 'longitude' => 29.9306, 'population' => 135467, 'is_capital' => true, 'timezone' => 'Africa/Bujumbura'],

            // Ethiopia
            ['name' => 'Addis Ababa', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 9.0250, 'longitude' => 38.7469, 'population' => 5000000, 'is_capital' => true, 'timezone' => 'Africa/Addis_Ababa'],
            ['name' => 'Dire Dawa', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 9.6008, 'longitude' => 41.8661, 'population' => 607321, 'timezone' => 'Africa/Addis_Ababa'],
            ['name' => 'Gondar', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 12.6000, 'longitude' => 37.4667, 'population' => 443156, 'timezone' => 'Africa/Addis_Ababa'],
            ['name' => 'Mekelle', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 13.4967, 'longitude' => 39.4753, 'population' => 545000, 'timezone' => 'Africa/Addis_Ababa'],
            ['name' => 'Bahir Dar', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 11.5936, 'longitude' => 37.3911, 'population' => 348429, 'timezone' => 'Africa/Addis_Ababa'],
            ['name' => 'Hawassa', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 7.0625, 'longitude' => 38.4764, 'population' => 387087, 'timezone' => 'Africa/Addis_Ababa'],

            // Eritrea
            ['name' => 'Asmara', 'country' => 'Eritrea', 'country_code' => 'ER', 'region' => 'East Africa', 'latitude' => 15.3229, 'longitude' => 38.9251, 'population' => 963000, 'is_capital' => true, 'timezone' => 'Africa/Asmara'],
            ['name' => 'Keren', 'country' => 'Eritrea', 'country_code' => 'ER', 'region' => 'East Africa', 'latitude' => 15.7778, 'longitude' => 38.4511, 'population' => 146483, 'timezone' => 'Africa/Asmara'],
            ['name' => 'Massawa', 'country' => 'Eritrea', 'country_code' => 'ER', 'region' => 'East Africa', 'latitude' => 15.6097, 'longitude' => 39.4456, 'population' => 53090, 'timezone' => 'Africa/Asmara'],

            // Somalia
            ['name' => 'Mogadishu', 'country' => 'Somalia', 'country_code' => 'SO', 'region' => 'East Africa', 'latitude' => 2.0469, 'longitude' => 45.3182, 'population' => 2280000, 'is_capital' => true, 'timezone' => 'Africa/Mogadishu'],
            ['name' => 'Hargeisa', 'country' => 'Somalia', 'country_code' => 'SO', 'region' => 'East Africa', 'latitude' => 9.5600, 'longitude' => 44.0650, 'population' => 1200000, 'timezone' => 'Africa/Mogadishu'],
            ['name' => 'Bosaso', 'country' => 'Somalia', 'country_code' => 'SO', 'region' => 'East Africa', 'latitude' => 11.2834, 'longitude' => 49.1816, 'population' => 164906, 'timezone' => 'Africa/Mogadishu'],
            ['name' => 'Kismayo', 'country' => 'Somalia', 'country_code' => 'SO', 'region' => 'East Africa', 'latitude' => -0.3582, 'longitude' => 42.5454, 'population' => 183300, 'timezone' => 'Africa/Mogadishu'],

            // Djibouti
            ['name' => 'Djibouti City', 'country' => 'Djibouti', 'country_code' => 'DJ', 'region' => 'East Africa', 'latitude' => 11.5721, 'longitude' => 43.1456, 'population' => 600000, 'is_capital' => true, 'timezone' => 'Africa/Djibouti'],

            // South Sudan
            ['name' => 'Juba', 'country' => 'South Sudan', 'country_code' => 'SS', 'region' => 'East Africa', 'latitude' => 4.8517, 'longitude' => 31.5825, 'population' => 525953, 'is_capital' => true, 'timezone' => 'Africa/Juba'],
            ['name' => 'Wau', 'country' => 'South Sudan', 'country_code' => 'SS', 'region' => 'East Africa', 'latitude' => 7.7029, 'longitude' => 27.9898, 'population' => 232910, 'timezone' => 'Africa/Juba'],
            ['name' => 'Malakal', 'country' => 'South Sudan', 'country_code' => 'SS', 'region' => 'East Africa', 'latitude' => 9.5334, 'longitude' => 31.6605, 'population' => 147450, 'timezone' => 'Africa/Juba'],

            // WEST AFRICA

            // Nigeria
            ['name' => 'Lagos', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Lagos State', 'latitude' => 6.5244, 'longitude' => 3.3792, 'population' => 15400000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Abuja', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'FCT', 'latitude' => 9.0765, 'longitude' => 7.3986, 'population' => 3095000, 'is_capital' => true, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Kano', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Kano State', 'latitude' => 12.0022, 'longitude' => 8.5919, 'population' => 4103000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Ibadan', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Oyo State', 'latitude' => 7.3775, 'longitude' => 3.9470, 'population' => 3649000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Port Harcourt', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Rivers State', 'latitude' => 4.8156, 'longitude' => 7.0498, 'population' => 3171000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Benin City', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Edo State', 'latitude' => 6.3176, 'longitude' => 5.6037, 'population' => 1782000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Kaduna', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Kaduna State', 'latitude' => 10.5222, 'longitude' => 7.4383, 'population' => 1954000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Enugu', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Enugu State', 'latitude' => 6.4584, 'longitude' => 7.5464, 'population' => 820000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Onitsha', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Anambra State', 'latitude' => 6.1667, 'longitude' => 6.7833, 'population' => 1080900, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Warri', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Delta State', 'latitude' => 5.5167, 'longitude' => 5.7500, 'population' => 899700, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Owerri', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Imo State', 'latitude' => 5.4836, 'longitude' => 7.0333, 'population' => 525000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Calabar', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Cross River State', 'latitude' => 4.9517, 'longitude' => 8.3220, 'population' => 634000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Uyo', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Akwa Ibom State', 'latitude' => 5.0377, 'longitude' => 7.9128, 'population' => 1200000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Asaba', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'state_province' => 'Delta State', 'latitude' => 6.1980, 'longitude' => 6.6953, 'population' => 688862, 'timezone' => 'Africa/Lagos'],

            // Ghana
            ['name' => 'Accra', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 5.6037, 'longitude' => -0.1870, 'population' => 2606000, 'is_capital' => true, 'timezone' => 'Africa/Accra'],
            ['name' => 'Kumasi', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 6.6885, 'longitude' => -1.6244, 'population' => 3348000, 'timezone' => 'Africa/Accra'],
            ['name' => 'Sekondi-Takoradi', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 4.9340, 'longitude' => -1.7137, 'population' => 946000, 'timezone' => 'Africa/Accra'],
            ['name' => 'Tamale', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 9.4075, 'longitude' => -0.8533, 'population' => 562919, 'timezone' => 'Africa/Accra'],
            ['name' => 'Cape Coast', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 5.1315, 'longitude' => -1.2795, 'population' => 217032, 'timezone' => 'Africa/Accra'],
            ['name' => 'Tema', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 5.6698, 'longitude' => -0.0166, 'population' => 161612, 'timezone' => 'Africa/Accra'],

            // Ivory Coast (Côte d'Ivoire)
            ['name' => 'Abidjan', 'country' => 'Ivory Coast', 'country_code' => 'CI', 'region' => 'West Africa', 'latitude' => 5.3600, 'longitude' => -4.0083, 'population' => 5316000, 'timezone' => 'Africa/Abidjan'],
            ['name' => 'Yamoussoukro', 'country' => 'Ivory Coast', 'country_code' => 'CI', 'region' => 'West Africa', 'latitude' => 6.8205, 'longitude' => -5.2763, 'population' => 281071, 'is_capital' => true, 'timezone' => 'Africa/Abidjan'],
            ['name' => 'Bouaké', 'country' => 'Ivory Coast', 'country_code' => 'CI', 'region' => 'West Africa', 'latitude' => 7.6939, 'longitude' => -5.0303, 'population' => 762000, 'timezone' => 'Africa/Abidjan'],
            ['name' => 'San-Pédro', 'country' => 'Ivory Coast', 'country_code' => 'CI', 'region' => 'West Africa', 'latitude' => 4.7485, 'longitude' => -6.6363, 'population' => 261616, 'timezone' => 'Africa/Abidjan'],

            // Senegal
            ['name' => 'Dakar', 'country' => 'Senegal', 'country_code' => 'SN', 'region' => 'West Africa', 'latitude' => 14.7167, 'longitude' => -17.4677, 'population' => 3732000, 'is_capital' => true, 'timezone' => 'Africa/Dakar'],
            ['name' => 'Touba', 'country' => 'Senegal', 'country_code' => 'SN', 'region' => 'West Africa', 'latitude' => 14.8500, 'longitude' => -15.8833, 'population' => 753315, 'timezone' => 'Africa/Dakar'],
            ['name' => 'Thiès', 'country' => 'Senegal', 'country_code' => 'SN', 'region' => 'West Africa', 'latitude' => 14.7886, 'longitude' => -16.9260, 'population' => 317763, 'timezone' => 'Africa/Dakar'],
            ['name' => 'Saint-Louis', 'country' => 'Senegal', 'country_code' => 'SN', 'region' => 'West Africa', 'latitude' => 16.0179, 'longitude' => -16.4896, 'population' => 237563, 'timezone' => 'Africa/Dakar'],

            // Mali
            ['name' => 'Bamako', 'country' => 'Mali', 'country_code' => 'ML', 'region' => 'West Africa', 'latitude' => 12.6392, 'longitude' => -8.0029, 'population' => 2713000, 'is_capital' => true, 'timezone' => 'Africa/Bamako'],
            ['name' => 'Sikasso', 'country' => 'Mali', 'country_code' => 'ML', 'region' => 'West Africa', 'latitude' => 11.3176, 'longitude' => -5.6664, 'population' => 255431, 'timezone' => 'Africa/Bamako'],
            ['name' => 'Mopti', 'country' => 'Mali', 'country_code' => 'ML', 'region' => 'West Africa', 'latitude' => 14.4843, 'longitude' => -4.1890, 'population' => 148456, 'timezone' => 'Africa/Bamako'],
            ['name' => 'Timbuktu', 'country' => 'Mali', 'country_code' => 'ML', 'region' => 'West Africa', 'latitude' => 16.7735, 'longitude' => -3.0074, 'population' => 54453, 'timezone' => 'Africa/Bamako'],

            // Burkina Faso
            ['name' => 'Ouagadougou', 'country' => 'Burkina Faso', 'country_code' => 'BF', 'region' => 'West Africa', 'latitude' => 12.3714, 'longitude' => -1.5197, 'population' => 2741000, 'is_capital' => true, 'timezone' => 'Africa/Ouagadougou'],
            ['name' => 'Bobo-Dioulasso', 'country' => 'Burkina Faso', 'country_code' => 'BF', 'region' => 'West Africa', 'latitude' => 11.1771, 'longitude' => -4.2979, 'population' => 903887, 'timezone' => 'Africa/Ouagadougou'],

            // Niger
            ['name' => 'Niamey', 'country' => 'Niger', 'country_code' => 'NE', 'region' => 'West Africa', 'latitude' => 13.5127, 'longitude' => 2.1126, 'population' => 1336000, 'is_capital' => true, 'timezone' => 'Africa/Niamey'],
            ['name' => 'Maradi', 'country' => 'Niger', 'country_code' => 'NE', 'region' => 'West Africa', 'latitude' => 13.5000, 'longitude' => 7.1017, 'population' => 267249, 'timezone' => 'Africa/Niamey'],
            ['name' => 'Zinder', 'country' => 'Niger', 'country_code' => 'NE', 'region' => 'West Africa', 'latitude' => 13.8050, 'longitude' => 8.9881, 'population' => 235605, 'timezone' => 'Africa/Niamey'],

            // Guinea
            ['name' => 'Conakry', 'country' => 'Guinea', 'country_code' => 'GN', 'region' => 'West Africa', 'latitude' => 9.6412, 'longitude' => -13.5784, 'population' => 1936000, 'is_capital' => true, 'timezone' => 'Africa/Conakry'],
            ['name' => 'Nzérékoré', 'country' => 'Guinea', 'country_code' => 'GN', 'region' => 'West Africa', 'latitude' => 7.7561, 'longitude' => -8.8179, 'population' => 238928, 'timezone' => 'Africa/Conakry'],
            ['name' => 'Kankan', 'country' => 'Guinea', 'country_code' => 'GN', 'region' => 'West Africa', 'latitude' => 10.3854, 'longitude' => -9.3057, 'population' => 193830, 'timezone' => 'Africa/Conakry'],

            // Benin
            ['name' => 'Porto-Novo', 'country' => 'Benin', 'country_code' => 'BJ', 'region' => 'West Africa', 'latitude' => 6.4969, 'longitude' => 2.6289, 'population' => 285000, 'is_capital' => true, 'timezone' => 'Africa/Porto-Novo'],
            ['name' => 'Cotonou', 'country' => 'Benin', 'country_code' => 'BJ', 'region' => 'West Africa', 'latitude' => 6.3654, 'longitude' => 2.4183, 'population' => 780000, 'timezone' => 'Africa/Porto-Novo'],
            ['name' => 'Parakou', 'country' => 'Benin', 'country_code' => 'BJ', 'region' => 'West Africa', 'latitude' => 9.3370, 'longitude' => 2.6303, 'population' => 255478, 'timezone' => 'Africa/Porto-Novo'],

            // Togo
            ['name' => 'Lomé', 'country' => 'Togo', 'country_code' => 'TG', 'region' => 'West Africa', 'latitude' => 6.1375, 'longitude' => 1.2123, 'population' => 1827000, 'is_capital' => true, 'timezone' => 'Africa/Lome'],
            ['name' => 'Sokodé', 'country' => 'Togo', 'country_code' => 'TG', 'region' => 'West Africa', 'latitude' => 8.9833, 'longitude' => 1.1333, 'population' => 118852, 'timezone' => 'Africa/Lome'],

            // Sierra Leone
            ['name' => 'Freetown', 'country' => 'Sierra Leone', 'country_code' => 'SL', 'region' => 'West Africa', 'latitude' => 8.4657, 'longitude' => -13.2317, 'population' => 1200000, 'is_capital' => true, 'timezone' => 'Africa/Freetown'],
            ['name' => 'Bo', 'country' => 'Sierra Leone', 'country_code' => 'SL', 'region' => 'West Africa', 'latitude' => 7.9647, 'longitude' => -11.7383, 'population' => 233684, 'timezone' => 'Africa/Freetown'],
            ['name' => 'Kenema', 'country' => 'Sierra Leone', 'country_code' => 'SL', 'region' => 'West Africa', 'latitude' => 7.8789, 'longitude' => -11.1875, 'population' => 200354, 'timezone' => 'Africa/Freetown'],

            // Liberia
            ['name' => 'Monrovia', 'country' => 'Liberia', 'country_code' => 'LR', 'region' => 'West Africa', 'latitude' => 6.3005, 'longitude' => -10.7969, 'population' => 1569000, 'is_capital' => true, 'timezone' => 'Africa/Monrovia'],
            ['name' => 'Gbarnga', 'country' => 'Liberia', 'country_code' => 'LR', 'region' => 'West Africa', 'latitude' => 6.9954, 'longitude' => -9.4722, 'population' => 56986, 'timezone' => 'Africa/Monrovia'],

            // Mauritania
            ['name' => 'Nouakchott', 'country' => 'Mauritania', 'country_code' => 'MR', 'region' => 'West Africa', 'latitude' => 18.0735, 'longitude' => -15.9582, 'population' => 1315000, 'is_capital' => true, 'timezone' => 'Africa/Nouakchott'],
            ['name' => 'Nouadhibou', 'country' => 'Mauritania', 'country_code' => 'MR', 'region' => 'West Africa', 'latitude' => 20.9456, 'longitude' => -17.0345, 'population' => 123779, 'timezone' => 'Africa/Nouakchott'],

            // Gambia
            ['name' => 'Banjul', 'country' => 'Gambia', 'country_code' => 'GM', 'region' => 'West Africa', 'latitude' => 13.4549, 'longitude' => -16.5790, 'population' => 437000, 'is_capital' => true, 'timezone' => 'Africa/Banjul'],
            ['name' => 'Serekunda', 'country' => 'Gambia', 'country_code' => 'GM', 'region' => 'West Africa', 'latitude' => 13.4383, 'longitude' => -16.6781, 'population' => 340000, 'timezone' => 'Africa/Banjul'],

            // Guinea-Bissau
            ['name' => 'Bissau', 'country' => 'Guinea-Bissau', 'country_code' => 'GW', 'region' => 'West Africa', 'latitude' => 11.8636, 'longitude' => -15.5977, 'population' => 492000, 'is_capital' => true, 'timezone' => 'Africa/Bissau'],

            // Cape Verde
            ['name' => 'Praia', 'country' => 'Cape Verde', 'country_code' => 'CV', 'region' => 'West Africa', 'latitude' => 14.9330, 'longitude' => -23.5133, 'population' => 168000, 'is_capital' => true, 'timezone' => 'Atlantic/Cape_Verde'],
            ['name' => 'Mindelo', 'country' => 'Cape Verde', 'country_code' => 'CV', 'region' => 'West Africa', 'latitude' => 16.8901, 'longitude' => -24.9805, 'population' => 70468, 'timezone' => 'Atlantic/Cape_Verde'],

            // NORTH AFRICA

            // Egypt
            ['name' => 'Cairo', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 30.0444, 'longitude' => 31.2357, 'population' => 21323000, 'is_capital' => true, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Alexandria', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 31.2001, 'longitude' => 29.9187, 'population' => 5464000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Giza', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 30.0131, 'longitude' => 31.2089, 'population' => 9200000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Shubra El Kheima', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 30.1286, 'longitude' => 31.2422, 'population' => 1165000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Port Said', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 31.2565, 'longitude' => 32.2841, 'population' => 760000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Suez', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 29.9737, 'longitude' => 32.5263, 'population' => 750000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Luxor', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 25.6872, 'longitude' => 32.6396, 'population' => 529000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Aswan', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 24.0909, 'longitude' => 32.8998, 'population' => 381000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Hurghada', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 27.2579, 'longitude' => 33.8116, 'population' => 290000, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Sharm El Sheikh', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 27.9158, 'longitude' => 34.3300, 'population' => 73000, 'timezone' => 'Africa/Cairo'],

            // Libya
            ['name' => 'Tripoli', 'country' => 'Libya', 'country_code' => 'LY', 'region' => 'North Africa', 'latitude' => 32.8872, 'longitude' => 13.1913, 'population' => 1165000, 'is_capital' => true, 'timezone' => 'Africa/Tripoli'],
            ['name' => 'Benghazi', 'country' => 'Libya', 'country_code' => 'LY', 'region' => 'North Africa', 'latitude' => 32.1194, 'longitude' => 20.0868, 'population' => 807250, 'timezone' => 'Africa/Tripoli'],
            ['name' => 'Misrata', 'country' => 'Libya', 'country_code' => 'LY', 'region' => 'North Africa', 'latitude' => 32.3754, 'longitude' => 15.0925, 'population' => 550938, 'timezone' => 'Africa/Tripoli'],

            // Tunisia
            ['name' => 'Tunis', 'country' => 'Tunisia', 'country_code' => 'TN', 'region' => 'North Africa', 'latitude' => 36.8065, 'longitude' => 10.1815, 'population' => 2700000, 'is_capital' => true, 'timezone' => 'Africa/Tunis'],
            ['name' => 'Sfax', 'country' => 'Tunisia', 'country_code' => 'TN', 'region' => 'North Africa', 'latitude' => 34.7406, 'longitude' => 10.7603, 'population' => 330440, 'timezone' => 'Africa/Tunis'],
            ['name' => 'Sousse', 'country' => 'Tunisia', 'country_code' => 'TN', 'region' => 'North Africa', 'latitude' => 35.8256, 'longitude' => 10.6369, 'population' => 271428, 'timezone' => 'Africa/Tunis'],
            ['name' => 'Hammamet', 'country' => 'Tunisia', 'country_code' => 'TN', 'region' => 'North Africa', 'latitude' => 36.4000, 'longitude' => 10.6167, 'population' => 97785, 'timezone' => 'Africa/Tunis'],
            ['name' => 'Djerba', 'country' => 'Tunisia', 'country_code' => 'TN', 'region' => 'North Africa', 'latitude' => 33.8076, 'longitude' => 10.8451, 'population' => 163726, 'timezone' => 'Africa/Tunis'],

            // Algeria
            ['name' => 'Algiers', 'country' => 'Algeria', 'country_code' => 'DZ', 'region' => 'North Africa', 'latitude' => 36.7538, 'longitude' => 3.0588, 'population' => 3916000, 'is_capital' => true, 'timezone' => 'Africa/Algiers'],
            ['name' => 'Oran', 'country' => 'Algeria', 'country_code' => 'DZ', 'region' => 'North Africa', 'latitude' => 35.6969, 'longitude' => -0.6331, 'population' => 1560000, 'timezone' => 'Africa/Algiers'],
            ['name' => 'Constantine', 'country' => 'Algeria', 'country_code' => 'DZ', 'region' => 'North Africa', 'latitude' => 36.3650, 'longitude' => 6.6147, 'population' => 938475, 'timezone' => 'Africa/Algiers'],
            ['name' => 'Annaba', 'country' => 'Algeria', 'country_code' => 'DZ', 'region' => 'North Africa', 'latitude' => 36.9000, 'longitude' => 7.7667, 'population' => 640050, 'timezone' => 'Africa/Algiers'],

            // Morocco
            ['name' => 'Rabat', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 34.0209, 'longitude' => -6.8416, 'population' => 572717, 'is_capital' => true, 'timezone' => 'Africa/Casablanca'],
            ['name' => 'Casablanca', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 33.5731, 'longitude' => -7.5898, 'population' => 3752000, 'timezone' => 'Africa/Casablanca'],
            ['name' => 'Marrakech', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 31.6295, 'longitude' => -7.9811, 'population' => 1330000, 'timezone' => 'Africa/Casablanca'],
            ['name' => 'Fez', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 34.0331, 'longitude' => -4.9998, 'population' => 1256000, 'timezone' => 'Africa/Casablanca'],
            ['name' => 'Tangier', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 35.7595, 'longitude' => -5.8340, 'population' => 1065000, 'timezone' => 'Africa/Casablanca'],
            ['name' => 'Agadir', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 30.4278, 'longitude' => -9.5981, 'population' => 924000, 'timezone' => 'Africa/Casablanca'],
            ['name' => 'Essaouira', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'North Africa', 'latitude' => 31.5125, 'longitude' => -9.7700, 'population' => 91764, 'timezone' => 'Africa/Casablanca'],

            // Sudan
            ['name' => 'Khartoum', 'country' => 'Sudan', 'country_code' => 'SD', 'region' => 'North Africa', 'latitude' => 15.5007, 'longitude' => 32.5599, 'population' => 5829000, 'is_capital' => true, 'timezone' => 'Africa/Khartoum'],
            ['name' => 'Omdurman', 'country' => 'Sudan', 'country_code' => 'SD', 'region' => 'North Africa', 'latitude' => 15.6445, 'longitude' => 32.4777, 'population' => 3127000, 'timezone' => 'Africa/Khartoum'],
            ['name' => 'Port Sudan', 'country' => 'Sudan', 'country_code' => 'SD', 'region' => 'North Africa', 'latitude' => 19.6158, 'longitude' => 37.2164, 'population' => 690000, 'timezone' => 'Africa/Khartoum'],

            // SOUTHERN AFRICA

            // South Africa
            ['name' => 'Johannesburg', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Gauteng', 'latitude' => -26.2041, 'longitude' => 28.0473, 'population' => 5635000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Cape Town', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Western Cape', 'latitude' => -33.9249, 'longitude' => 18.4241, 'population' => 4618000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Durban', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'KwaZulu-Natal', 'latitude' => -29.8587, 'longitude' => 31.0218, 'population' => 3871000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Pretoria', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Gauteng', 'latitude' => -25.7479, 'longitude' => 28.2293, 'population' => 2473000, 'is_capital' => true, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Port Elizabeth', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Eastern Cape', 'latitude' => -33.9608, 'longitude' => 25.6022, 'population' => 1263000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Bloemfontein', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Free State', 'latitude' => -29.0852, 'longitude' => 26.1596, 'population' => 556000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Sandton', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Gauteng', 'latitude' => -26.1076, 'longitude' => 28.0567, 'population' => 336000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Centurion', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Gauteng', 'latitude' => -25.8603, 'longitude' => 28.1894, 'population' => 340000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'East London', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Eastern Cape', 'latitude' => -33.0153, 'longitude' => 27.9116, 'population' => 478676, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Stellenbosch', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Western Cape', 'latitude' => -33.9321, 'longitude' => 18.8602, 'population' => 199396, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Pietermaritzburg', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'KwaZulu-Natal', 'latitude' => -29.6006, 'longitude' => 30.3794, 'population' => 679766, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Knysna', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Western Cape', 'latitude' => -34.0363, 'longitude' => 23.0471, 'population' => 76150, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Rustenburg', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'North West', 'latitude' => -25.6674, 'longitude' => 27.2423, 'population' => 626522, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Kimberley', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'state_province' => 'Northern Cape', 'latitude' => -28.7320, 'longitude' => 24.7618, 'population' => 451000, 'timezone' => 'Africa/Johannesburg'],

            // Zimbabwe
            ['name' => 'Harare', 'country' => 'Zimbabwe', 'country_code' => 'ZW', 'region' => 'Southern Africa', 'latitude' => -17.8252, 'longitude' => 31.0335, 'population' => 2150000, 'is_capital' => true, 'timezone' => 'Africa/Harare'],
            ['name' => 'Bulawayo', 'country' => 'Zimbabwe', 'country_code' => 'ZW', 'region' => 'Southern Africa', 'latitude' => -20.1539, 'longitude' => 28.5866, 'population' => 1200337, 'timezone' => 'Africa/Harare'],
            ['name' => 'Victoria Falls', 'country' => 'Zimbabwe', 'country_code' => 'ZW', 'region' => 'Southern Africa', 'latitude' => -17.9243, 'longitude' => 25.8572, 'population' => 33060, 'timezone' => 'Africa/Harare'],
            ['name' => 'Mutare', 'country' => 'Zimbabwe', 'country_code' => 'ZW', 'region' => 'Southern Africa', 'latitude' => -18.9707, 'longitude' => 32.6709, 'population' => 262124, 'timezone' => 'Africa/Harare'],

            // Zambia
            ['name' => 'Lusaka', 'country' => 'Zambia', 'country_code' => 'ZM', 'region' => 'Southern Africa', 'latitude' => -15.3875, 'longitude' => 28.3228, 'population' => 2906000, 'is_capital' => true, 'timezone' => 'Africa/Lusaka'],
            ['name' => 'Kitwe', 'country' => 'Zambia', 'country_code' => 'ZM', 'region' => 'Southern Africa', 'latitude' => -12.8024, 'longitude' => 28.2132, 'population' => 710000, 'timezone' => 'Africa/Lusaka'],
            ['name' => 'Ndola', 'country' => 'Zambia', 'country_code' => 'ZM', 'region' => 'Southern Africa', 'latitude' => -12.9587, 'longitude' => 28.6366, 'population' => 626000, 'timezone' => 'Africa/Lusaka'],
            ['name' => 'Livingstone', 'country' => 'Zambia', 'country_code' => 'ZM', 'region' => 'Southern Africa', 'latitude' => -17.8519, 'longitude' => 25.8543, 'population' => 177393, 'timezone' => 'Africa/Lusaka'],

            // Botswana
            ['name' => 'Gaborone', 'country' => 'Botswana', 'country_code' => 'BW', 'region' => 'Southern Africa', 'latitude' => -24.6282, 'longitude' => 25.9231, 'population' => 273602, 'is_capital' => true, 'timezone' => 'Africa/Gaborone'],
            ['name' => 'Francistown', 'country' => 'Botswana', 'country_code' => 'BW', 'region' => 'Southern Africa', 'latitude' => -21.1661, 'longitude' => 27.5157, 'population' => 103417, 'timezone' => 'Africa/Gaborone'],
            ['name' => 'Maun', 'country' => 'Botswana', 'country_code' => 'BW', 'region' => 'Southern Africa', 'latitude' => -19.9833, 'longitude' => 23.4167, 'population' => 85363, 'timezone' => 'Africa/Gaborone'],

            // Namibia
            ['name' => 'Windhoek', 'country' => 'Namibia', 'country_code' => 'NA', 'region' => 'Southern Africa', 'latitude' => -22.5594, 'longitude' => 17.0832, 'population' => 494000, 'is_capital' => true, 'timezone' => 'Africa/Windhoek'],
            ['name' => 'Swakopmund', 'country' => 'Namibia', 'country_code' => 'NA', 'region' => 'Southern Africa', 'latitude' => -22.6833, 'longitude' => 14.5333, 'population' => 72000, 'timezone' => 'Africa/Windhoek'],
            ['name' => 'Walvis Bay', 'country' => 'Namibia', 'country_code' => 'NA', 'region' => 'Southern Africa', 'latitude' => -22.9575, 'longitude' => 14.5053, 'population' => 100000, 'timezone' => 'Africa/Windhoek'],

            // Mozambique
            ['name' => 'Maputo', 'country' => 'Mozambique', 'country_code' => 'MZ', 'region' => 'Southern Africa', 'latitude' => -25.9692, 'longitude' => 32.5732, 'population' => 1191613, 'is_capital' => true, 'timezone' => 'Africa/Maputo'],
            ['name' => 'Beira', 'country' => 'Mozambique', 'country_code' => 'MZ', 'region' => 'Southern Africa', 'latitude' => -19.8437, 'longitude' => 34.8389, 'population' => 592090, 'timezone' => 'Africa/Maputo'],
            ['name' => 'Nampula', 'country' => 'Mozambique', 'country_code' => 'MZ', 'region' => 'Southern Africa', 'latitude' => -15.1165, 'longitude' => 39.2666, 'population' => 743638, 'timezone' => 'Africa/Maputo'],
            ['name' => 'Pemba', 'country' => 'Mozambique', 'country_code' => 'MZ', 'region' => 'Southern Africa', 'latitude' => -12.9744, 'longitude' => 40.5178, 'population' => 223000, 'timezone' => 'Africa/Maputo'],

            // Malawi
            ['name' => 'Lilongwe', 'country' => 'Malawi', 'country_code' => 'MW', 'region' => 'Southern Africa', 'latitude' => -13.9626, 'longitude' => 33.7741, 'population' => 1122000, 'is_capital' => true, 'timezone' => 'Africa/Blantyre'],
            ['name' => 'Blantyre', 'country' => 'Malawi', 'country_code' => 'MW', 'region' => 'Southern Africa', 'latitude' => -15.7866, 'longitude' => 35.0055, 'population' => 994500, 'timezone' => 'Africa/Blantyre'],
            ['name' => 'Mzuzu', 'country' => 'Malawi', 'country_code' => 'MW', 'region' => 'Southern Africa', 'latitude' => -11.4581, 'longitude' => 34.0207, 'population' => 221272, 'timezone' => 'Africa/Blantyre'],

            // Angola
            ['name' => 'Luanda', 'country' => 'Angola', 'country_code' => 'AO', 'region' => 'Southern Africa', 'latitude' => -8.8368, 'longitude' => 13.2343, 'population' => 8952000, 'is_capital' => true, 'timezone' => 'Africa/Luanda'],
            ['name' => 'Huambo', 'country' => 'Angola', 'country_code' => 'AO', 'region' => 'Southern Africa', 'latitude' => -12.7764, 'longitude' => 15.7392, 'population' => 713134, 'timezone' => 'Africa/Luanda'],
            ['name' => 'Lobito', 'country' => 'Angola', 'country_code' => 'AO', 'region' => 'Southern Africa', 'latitude' => -12.3481, 'longitude' => 13.5456, 'population' => 460613, 'timezone' => 'Africa/Luanda'],
            ['name' => 'Benguela', 'country' => 'Angola', 'country_code' => 'AO', 'region' => 'Southern Africa', 'latitude' => -12.5763, 'longitude' => 13.4055, 'population' => 600000, 'timezone' => 'Africa/Luanda'],

            // Madagascar
            ['name' => 'Antananarivo', 'country' => 'Madagascar', 'country_code' => 'MG', 'region' => 'Southern Africa', 'latitude' => -18.8792, 'longitude' => 47.5079, 'population' => 3058000, 'is_capital' => true, 'timezone' => 'Indian/Antananarivo'],
            ['name' => 'Toamasina', 'country' => 'Madagascar', 'country_code' => 'MG', 'region' => 'Southern Africa', 'latitude' => -18.1492, 'longitude' => 49.4023, 'population' => 541800, 'timezone' => 'Indian/Antananarivo'],
            ['name' => 'Antsirabe', 'country' => 'Madagascar', 'country_code' => 'MG', 'region' => 'Southern Africa', 'latitude' => -19.8652, 'longitude' => 47.0337, 'population' => 257163, 'timezone' => 'Indian/Antananarivo'],
            ['name' => 'Mahajanga', 'country' => 'Madagascar', 'country_code' => 'MG', 'region' => 'Southern Africa', 'latitude' => -15.7167, 'longitude' => 46.3167, 'population' => 244722, 'timezone' => 'Indian/Antananarivo'],

            // Mauritius
            ['name' => 'Port Louis', 'country' => 'Mauritius', 'country_code' => 'MU', 'region' => 'Southern Africa', 'latitude' => -20.1619, 'longitude' => 57.4989, 'population' => 148000, 'is_capital' => true, 'timezone' => 'Indian/Mauritius'],
            ['name' => 'Vacoas-Phoenix', 'country' => 'Mauritius', 'country_code' => 'MU', 'region' => 'Southern Africa', 'latitude' => -20.2981, 'longitude' => 57.4936, 'population' => 110000, 'timezone' => 'Indian/Mauritius'],
            ['name' => 'Curepipe', 'country' => 'Mauritius', 'country_code' => 'MU', 'region' => 'Southern Africa', 'latitude' => -20.3182, 'longitude' => 57.5201, 'population' => 78618, 'timezone' => 'Indian/Mauritius'],

            // Lesotho
            ['name' => 'Maseru', 'country' => 'Lesotho', 'country_code' => 'LS', 'region' => 'Southern Africa', 'latitude' => -29.3167, 'longitude' => 27.4833, 'population' => 521000, 'is_capital' => true, 'timezone' => 'Africa/Maseru'],

            // Eswatini (Swaziland)
            ['name' => 'Mbabane', 'country' => 'Eswatini', 'country_code' => 'SZ', 'region' => 'Southern Africa', 'latitude' => -26.3054, 'longitude' => 31.1367, 'population' => 95000, 'is_capital' => true, 'timezone' => 'Africa/Mbabane'],
            ['name' => 'Manzini', 'country' => 'Eswatini', 'country_code' => 'SZ', 'region' => 'Southern Africa', 'latitude' => -26.4897, 'longitude' => 31.3809, 'population' => 141000, 'timezone' => 'Africa/Mbabane'],

            // CENTRAL AFRICA

            // Democratic Republic of Congo
            ['name' => 'Kinshasa', 'country' => 'DR Congo', 'country_code' => 'CD', 'region' => 'Central Africa', 'latitude' => -4.4419, 'longitude' => 15.2663, 'population' => 14970000, 'is_capital' => true, 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Lubumbashi', 'country' => 'DR Congo', 'country_code' => 'CD', 'region' => 'Central Africa', 'latitude' => -11.6640, 'longitude' => 27.4791, 'population' => 2584000, 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Goma', 'country' => 'DR Congo', 'country_code' => 'CD', 'region' => 'Central Africa', 'latitude' => -1.6786, 'longitude' => 29.2346, 'population' => 2000000, 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Bukavu', 'country' => 'DR Congo', 'country_code' => 'CD', 'region' => 'Central Africa', 'latitude' => -2.5083, 'longitude' => 28.8606, 'population' => 1133000, 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Kisangani', 'country' => 'DR Congo', 'country_code' => 'CD', 'region' => 'Central Africa', 'latitude' => 0.5151, 'longitude' => 25.1911, 'population' => 1602144, 'timezone' => 'Africa/Lubumbashi'],

            // Cameroon
            ['name' => 'Yaoundé', 'country' => 'Cameroon', 'country_code' => 'CM', 'region' => 'Central Africa', 'latitude' => 3.8480, 'longitude' => 11.5021, 'population' => 4100000, 'is_capital' => true, 'timezone' => 'Africa/Douala'],
            ['name' => 'Douala', 'country' => 'Cameroon', 'country_code' => 'CM', 'region' => 'Central Africa', 'latitude' => 4.0511, 'longitude' => 9.7679, 'population' => 3663000, 'timezone' => 'Africa/Douala'],
            ['name' => 'Bamenda', 'country' => 'Cameroon', 'country_code' => 'CM', 'region' => 'Central Africa', 'latitude' => 5.9597, 'longitude' => 10.1458, 'population' => 500000, 'timezone' => 'Africa/Douala'],
            ['name' => 'Kribi', 'country' => 'Cameroon', 'country_code' => 'CM', 'region' => 'Central Africa', 'latitude' => 2.9333, 'longitude' => 9.9000, 'population' => 80957, 'timezone' => 'Africa/Douala'],

            // Central African Republic
            ['name' => 'Bangui', 'country' => 'Central African Republic', 'country_code' => 'CF', 'region' => 'Central Africa', 'latitude' => 4.3947, 'longitude' => 18.5582, 'population' => 889231, 'is_capital' => true, 'timezone' => 'Africa/Bangui'],

            // Chad
            ['name' => "N'Djamena", 'country' => 'Chad', 'country_code' => 'TD', 'region' => 'Central Africa', 'latitude' => 12.1347, 'longitude' => 15.0557, 'population' => 1605000, 'is_capital' => true, 'timezone' => 'Africa/Ndjamena'],
            ['name' => 'Moundou', 'country' => 'Chad', 'country_code' => 'TD', 'region' => 'Central Africa', 'latitude' => 8.5667, 'longitude' => 16.0833, 'population' => 142462, 'timezone' => 'Africa/Ndjamena'],

            // Republic of Congo
            ['name' => 'Brazzaville', 'country' => 'Republic of Congo', 'country_code' => 'CG', 'region' => 'Central Africa', 'latitude' => -4.2634, 'longitude' => 15.2429, 'population' => 2388000, 'is_capital' => true, 'timezone' => 'Africa/Brazzaville'],
            ['name' => 'Pointe-Noire', 'country' => 'Republic of Congo', 'country_code' => 'CG', 'region' => 'Central Africa', 'latitude' => -4.7761, 'longitude' => 11.8635, 'population' => 1420000, 'timezone' => 'Africa/Brazzaville'],

            // Gabon
            ['name' => 'Libreville', 'country' => 'Gabon', 'country_code' => 'GA', 'region' => 'Central Africa', 'latitude' => 0.4162, 'longitude' => 9.4673, 'population' => 797003, 'is_capital' => true, 'timezone' => 'Africa/Libreville'],
            ['name' => 'Port-Gentil', 'country' => 'Gabon', 'country_code' => 'GA', 'region' => 'Central Africa', 'latitude' => -0.7193, 'longitude' => 8.7815, 'population' => 164000, 'timezone' => 'Africa/Libreville'],

            // Equatorial Guinea
            ['name' => 'Malabo', 'country' => 'Equatorial Guinea', 'country_code' => 'GQ', 'region' => 'Central Africa', 'latitude' => 3.7550, 'longitude' => 8.7371, 'population' => 297000, 'is_capital' => true, 'timezone' => 'Africa/Malabo'],
            ['name' => 'Bata', 'country' => 'Equatorial Guinea', 'country_code' => 'GQ', 'region' => 'Central Africa', 'latitude' => 1.8639, 'longitude' => 9.7656, 'population' => 250770, 'timezone' => 'Africa/Malabo'],

            // São Tomé and Príncipe
            ['name' => 'São Tomé', 'country' => 'São Tomé and Príncipe', 'country_code' => 'ST', 'region' => 'Central Africa', 'latitude' => 0.3365, 'longitude' => 6.7273, 'population' => 90112, 'is_capital' => true, 'timezone' => 'Africa/Sao_Tome'],

            // Seychelles
            ['name' => 'Victoria', 'country' => 'Seychelles', 'country_code' => 'SC', 'region' => 'East Africa', 'latitude' => -4.6191, 'longitude' => 55.4513, 'population' => 26450, 'is_capital' => true, 'timezone' => 'Indian/Mahe'],

            // Comoros
            ['name' => 'Moroni', 'country' => 'Comoros', 'country_code' => 'KM', 'region' => 'East Africa', 'latitude' => -11.7022, 'longitude' => 43.2551, 'population' => 111326, 'is_capital' => true, 'timezone' => 'Indian/Comoro'],
        ];
    }
}
