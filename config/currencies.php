<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of all supported currencies for the Noxxi platform
    | USD is listed first as the default international currency
    | Then all African currencies in alphabetical order by country
    |
    */

    'supported' => [
        'USD' => 'USD - US Dollar',

        // African Currencies (Alphabetical by country)
        'DZD' => 'DZD - Algerian Dinar',
        'AOA' => 'AOA - Angolan Kwanza',
        'XOF' => 'XOF - West African CFA Franc', // Benin, Burkina Faso, Ivory Coast, Mali, Niger, Senegal, Togo
        'BWP' => 'BWP - Botswanan Pula',
        'BIF' => 'BIF - Burundian Franc',
        'XAF' => 'XAF - Central African CFA Franc', // Cameroon, CAR, Chad, Congo, Equatorial Guinea, Gabon
        'CVE' => 'CVE - Cape Verdean Escudo',
        'DJF' => 'DJF - Djiboutian Franc',
        'EGP' => 'EGP - Egyptian Pound',
        'ERN' => 'ERN - Eritrean Nakfa',
        'ETB' => 'ETB - Ethiopian Birr',
        'GMD' => 'GMD - Gambian Dalasi',
        'GHS' => 'GHS - Ghanaian Cedi',
        'GNF' => 'GNF - Guinean Franc',
        'KES' => 'KES - Kenyan Shilling',
        'LSL' => 'LSL - Lesotho Loti',
        'LRD' => 'LRD - Liberian Dollar',
        'LYD' => 'LYD - Libyan Dinar',
        'MGA' => 'MGA - Malagasy Ariary',
        'MWK' => 'MWK - Malawian Kwacha',
        'MRU' => 'MRU - Mauritanian Ouguiya',
        'MUR' => 'MUR - Mauritian Rupee',
        'MAD' => 'MAD - Moroccan Dirham',
        'MZN' => 'MZN - Mozambican Metical',
        'NAD' => 'NAD - Namibian Dollar',
        'NGN' => 'NGN - Nigerian Naira',
        'RWF' => 'RWF - Rwandan Franc',
        'SCR' => 'SCR - Seychellois Rupee',
        'SLE' => 'SLE - Sierra Leonean Leone',
        'SOS' => 'SOS - Somali Shilling',
        'ZAR' => 'ZAR - South African Rand',
        'SSP' => 'SSP - South Sudanese Pound',
        'SDG' => 'SDG - Sudanese Pound',
        'SZL' => 'SZL - Swazi Lilangeni',
        'TZS' => 'TZS - Tanzanian Shilling',
        'TND' => 'TND - Tunisian Dinar',
        'UGX' => 'UGX - Ugandan Shilling',
        'ZMW' => 'ZMW - Zambian Kwacha',
        'ZWL' => 'ZWL - Zimbabwean Dollar',

        // Additional international currencies for convenience
        'EUR' => 'EUR - Euro',
        'GBP' => 'GBP - British Pound',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default' => env('DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Popular Currencies (shown first in dropdowns)
    |--------------------------------------------------------------------------
    */
    'popular' => [
        'USD',
        'KES',
        'NGN',
        'ZAR',
        'GHS',
        'UGX',
        'TZS',
        'EGP',
    ],
];
