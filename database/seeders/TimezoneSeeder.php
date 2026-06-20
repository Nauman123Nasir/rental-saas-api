<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimezoneSeeder extends Seeder
{
    public function run(): void
    {
        $timezones = [
            ['name' => 'Pacific/Midway',          'utc_offset' => '-11:00'],
            ['name' => 'Pacific/Honolulu',         'utc_offset' => '-10:00'],
            ['name' => 'America/Anchorage',        'utc_offset' => '-09:00'],
            ['name' => 'America/Los_Angeles',      'utc_offset' => '-08:00'],
            ['name' => 'America/Denver',           'utc_offset' => '-07:00'],
            ['name' => 'America/Chicago',          'utc_offset' => '-06:00'],
            ['name' => 'America/New_York',         'utc_offset' => '-05:00'],
            ['name' => 'America/Caracas',          'utc_offset' => '-04:30'],
            ['name' => 'America/Halifax',          'utc_offset' => '-04:00'],
            ['name' => 'America/St_Johns',         'utc_offset' => '-03:30'],
            ['name' => 'America/Sao_Paulo',        'utc_offset' => '-03:00'],
            ['name' => 'America/Noronha',          'utc_offset' => '-02:00'],
            ['name' => 'Atlantic/Azores',          'utc_offset' => '-01:00'],
            ['name' => 'UTC',                      'utc_offset' => '+00:00'],
            ['name' => 'Europe/London',            'utc_offset' => '+00:00'],
            ['name' => 'Europe/Paris',             'utc_offset' => '+01:00'],
            ['name' => 'Europe/Berlin',            'utc_offset' => '+01:00'],
            ['name' => 'Europe/Warsaw',            'utc_offset' => '+01:00'],
            ['name' => 'Europe/Istanbul',          'utc_offset' => '+03:00'],
            ['name' => 'Europe/Kyiv',              'utc_offset' => '+02:00'],
            ['name' => 'Europe/Athens',            'utc_offset' => '+02:00'],
            ['name' => 'Europe/Bucharest',         'utc_offset' => '+02:00'],
            ['name' => 'Africa/Cairo',             'utc_offset' => '+02:00'],
            ['name' => 'Africa/Johannesburg',      'utc_offset' => '+02:00'],
            ['name' => 'Africa/Nairobi',           'utc_offset' => '+03:00'],
            ['name' => 'Africa/Lagos',             'utc_offset' => '+01:00'],
            ['name' => 'Africa/Accra',             'utc_offset' => '+00:00'],
            ['name' => 'Africa/Tunis',             'utc_offset' => '+01:00'],
            ['name' => 'Africa/Casablanca',        'utc_offset' => '+01:00'],
            ['name' => 'Asia/Riyadh',              'utc_offset' => '+03:00'],
            ['name' => 'Asia/Kuwait',              'utc_offset' => '+03:00'],
            ['name' => 'Asia/Baghdad',             'utc_offset' => '+03:00'],
            ['name' => 'Asia/Beirut',              'utc_offset' => '+02:00'],
            ['name' => 'Asia/Amman',               'utc_offset' => '+02:00'],
            ['name' => 'Asia/Jerusalem',           'utc_offset' => '+02:00'],
            ['name' => 'Asia/Dubai',               'utc_offset' => '+04:00'],
            ['name' => 'Asia/Muscat',              'utc_offset' => '+04:00'],
            ['name' => 'Asia/Bahrain',             'utc_offset' => '+03:00'],
            ['name' => 'Asia/Qatar',               'utc_offset' => '+03:00'],
            ['name' => 'Asia/Tehran',              'utc_offset' => '+03:30'],
            ['name' => 'Asia/Kabul',               'utc_offset' => '+04:30'],
            ['name' => 'Asia/Karachi',             'utc_offset' => '+05:00'],
            ['name' => 'Asia/Tashkent',            'utc_offset' => '+05:00'],
            ['name' => 'Asia/Yekaterinburg',       'utc_offset' => '+05:00'],
            ['name' => 'Asia/Kolkata',             'utc_offset' => '+05:30'],
            ['name' => 'Asia/Colombo',             'utc_offset' => '+05:30'],
            ['name' => 'Asia/Kathmandu',           'utc_offset' => '+05:45'],
            ['name' => 'Asia/Dhaka',               'utc_offset' => '+06:00'],
            ['name' => 'Asia/Almaty',              'utc_offset' => '+06:00'],
            ['name' => 'Asia/Rangoon',             'utc_offset' => '+06:30'],
            ['name' => 'Asia/Bangkok',             'utc_offset' => '+07:00'],
            ['name' => 'Asia/Jakarta',             'utc_offset' => '+07:00'],
            ['name' => 'Asia/Shanghai',            'utc_offset' => '+08:00'],
            ['name' => 'Asia/Kuala_Lumpur',        'utc_offset' => '+08:00'],
            ['name' => 'Asia/Singapore',           'utc_offset' => '+08:00'],
            ['name' => 'Asia/Manila',              'utc_offset' => '+08:00'],
            ['name' => 'Asia/Seoul',               'utc_offset' => '+09:00'],
            ['name' => 'Asia/Tokyo',               'utc_offset' => '+09:00'],
            ['name' => 'Australia/Darwin',         'utc_offset' => '+09:30'],
            ['name' => 'Australia/Adelaide',       'utc_offset' => '+09:30'],
            ['name' => 'Australia/Sydney',         'utc_offset' => '+10:00'],
            ['name' => 'Australia/Brisbane',       'utc_offset' => '+10:00'],
            ['name' => 'Pacific/Auckland',         'utc_offset' => '+12:00'],
        ];

        $now = now();
        foreach ($timezones as &$t) {
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
        }

        DB::table('timezones')->insertOrIgnore($timezones);
    }
}
