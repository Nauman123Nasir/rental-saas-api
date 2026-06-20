<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'AED', 'symbol' => 'د.إ',  'name' => 'UAE Dirham',               'decimal_places' => 2],
            ['code' => 'AUD', 'symbol' => 'A$',    'name' => 'Australian Dollar',        'decimal_places' => 2],
            ['code' => 'BDT', 'symbol' => '৳',     'name' => 'Bangladeshi Taka',         'decimal_places' => 2],
            ['code' => 'BHD', 'symbol' => 'BD',    'name' => 'Bahraini Dinar',           'decimal_places' => 3],
            ['code' => 'BRL', 'symbol' => 'R$',    'name' => 'Brazilian Real',           'decimal_places' => 2],
            ['code' => 'CAD', 'symbol' => 'C$',    'name' => 'Canadian Dollar',          'decimal_places' => 2],
            ['code' => 'CHF', 'symbol' => 'Fr',    'name' => 'Swiss Franc',              'decimal_places' => 2],
            ['code' => 'CNY', 'symbol' => '¥',     'name' => 'Chinese Yuan',             'decimal_places' => 2],
            ['code' => 'DKK', 'symbol' => 'kr',    'name' => 'Danish Krone',             'decimal_places' => 2],
            ['code' => 'EGP', 'symbol' => 'E£',    'name' => 'Egyptian Pound',           'decimal_places' => 2],
            ['code' => 'EUR', 'symbol' => '€',     'name' => 'Euro',                     'decimal_places' => 2],
            ['code' => 'GBP', 'symbol' => '£',     'name' => 'British Pound Sterling',   'decimal_places' => 2],
            ['code' => 'GHS', 'symbol' => '₵',     'name' => 'Ghanaian Cedi',            'decimal_places' => 2],
            ['code' => 'IDR', 'symbol' => 'Rp',    'name' => 'Indonesian Rupiah',        'decimal_places' => 0],
            ['code' => 'ILS', 'symbol' => '₪',     'name' => 'Israeli New Shekel',       'decimal_places' => 2],
            ['code' => 'INR', 'symbol' => '₹',     'name' => 'Indian Rupee',             'decimal_places' => 2],
            ['code' => 'IQD', 'symbol' => 'ع.د',   'name' => 'Iraqi Dinar',              'decimal_places' => 3],
            ['code' => 'IRR', 'symbol' => '﷼',     'name' => 'Iranian Rial',             'decimal_places' => 2],
            ['code' => 'JPY', 'symbol' => '¥',     'name' => 'Japanese Yen',             'decimal_places' => 0],
            ['code' => 'JOD', 'symbol' => 'JD',    'name' => 'Jordanian Dinar',          'decimal_places' => 3],
            ['code' => 'KES', 'symbol' => 'KSh',   'name' => 'Kenyan Shilling',          'decimal_places' => 2],
            ['code' => 'KRW', 'symbol' => '₩',     'name' => 'South Korean Won',         'decimal_places' => 0],
            ['code' => 'KWD', 'symbol' => 'KD',    'name' => 'Kuwaiti Dinar',            'decimal_places' => 3],
            ['code' => 'LBP', 'symbol' => 'ل.ل',   'name' => 'Lebanese Pound',           'decimal_places' => 2],
            ['code' => 'LKR', 'symbol' => 'Rs',    'name' => 'Sri Lankan Rupee',         'decimal_places' => 2],
            ['code' => 'MAD', 'symbol' => 'MAD',   'name' => 'Moroccan Dirham',          'decimal_places' => 2],
            ['code' => 'MXN', 'symbol' => 'MX$',   'name' => 'Mexican Peso',             'decimal_places' => 2],
            ['code' => 'MYR', 'symbol' => 'RM',    'name' => 'Malaysian Ringgit',        'decimal_places' => 2],
            ['code' => 'NGN', 'symbol' => '₦',     'name' => 'Nigerian Naira',           'decimal_places' => 2],
            ['code' => 'NOK', 'symbol' => 'kr',    'name' => 'Norwegian Krone',          'decimal_places' => 2],
            ['code' => 'NZD', 'symbol' => 'NZ$',   'name' => 'New Zealand Dollar',       'decimal_places' => 2],
            ['code' => 'OMR', 'symbol' => 'ر.ع.',  'name' => 'Omani Rial',               'decimal_places' => 3],
            ['code' => 'PHP', 'symbol' => '₱',     'name' => 'Philippine Peso',          'decimal_places' => 2],
            ['code' => 'PKR', 'symbol' => '₨',     'name' => 'Pakistani Rupee',          'decimal_places' => 2],
            ['code' => 'PLN', 'symbol' => 'zł',    'name' => 'Polish Zloty',             'decimal_places' => 2],
            ['code' => 'QAR', 'symbol' => 'ر.ق',   'name' => 'Qatari Riyal',             'decimal_places' => 2],
            ['code' => 'RON', 'symbol' => 'lei',   'name' => 'Romanian Leu',             'decimal_places' => 2],
            ['code' => 'RUB', 'symbol' => '₽',     'name' => 'Russian Ruble',            'decimal_places' => 2],
            ['code' => 'SAR', 'symbol' => 'ر.س',   'name' => 'Saudi Riyal',              'decimal_places' => 2],
            ['code' => 'SEK', 'symbol' => 'kr',    'name' => 'Swedish Krona',            'decimal_places' => 2],
            ['code' => 'SGD', 'symbol' => 'S$',    'name' => 'Singapore Dollar',         'decimal_places' => 2],
            ['code' => 'THB', 'symbol' => '฿',     'name' => 'Thai Baht',                'decimal_places' => 2],
            ['code' => 'TND', 'symbol' => 'DT',    'name' => 'Tunisian Dinar',           'decimal_places' => 3],
            ['code' => 'TRY', 'symbol' => '₺',     'name' => 'Turkish Lira',             'decimal_places' => 2],
            ['code' => 'UAH', 'symbol' => '₴',     'name' => 'Ukrainian Hryvnia',        'decimal_places' => 2],
            ['code' => 'USD', 'symbol' => '$',     'name' => 'US Dollar',                'decimal_places' => 2],
            ['code' => 'VND', 'symbol' => '₫',     'name' => 'Vietnamese Dong',          'decimal_places' => 0],
            ['code' => 'ZAR', 'symbol' => 'R',     'name' => 'South African Rand',       'decimal_places' => 2],
        ];

        $now = now();
        foreach ($currencies as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
        }

        DB::table('currencies')->insertOrIgnore($currencies);
    }
}
