<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\CSVImporter;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('seeders/data/sortkar_loc_countries.csv');
        $countries = CSVImporter::read($file);

        foreach ($countries as $country) {
            DB::table('loc_countries')->insert([
                'id'              => $country['id'],
                'zoho_country_id' => self::nullable($country['zoho_country_id']),
                'name'            => $country['name'],
                'iso3'            => self::nullable($country['iso3']),
                'numeric_code'    => self::nullable($country['numeric_code']),
                'iso2'            => self::nullable($country['iso2']),
                'phonecode'       => self::nullable($country['phonecode']),
                'capital'         => self::nullable($country['capital']),
                'currency'        => self::nullable($country['currency']),
                'currency_name'   => self::nullable($country['currency_name']),
                'currency_symbol' => self::nullable($country['currency_symbol']),
                'tld'             => self::nullable($country['tld']),
                'native'          => self::nullable($country['native']),
                'region'          => self::nullable($country['region']),
                'subregion'       => self::nullable($country['subregion']),
                'timezones'       => self::nullable($country['timezones']),
                'translations'    => self::nullable($country['translations']),
                'latitude'        => self::nullable($country['latitude']),
                'longitude'       => self::nullable($country['longitude']),
                'emoji'           => self::nullable($country['emoji']),
                'emojiU'          => self::nullable($country['emojiU']),
                'flag'            => isset($country['flag']) && $country['flag'] !== 'NULL' ? (int) $country['flag'] : 1,
                'wikiDataId'      => self::nullable($country['wikiDataId']),
                'created_at'      => self::nullable($country['created_at'], now()),
                'updated_at'      => self::nullable($country['updated_at'], now()),
            ]);
        }
    }

    private static function nullable($value, $default = null)
    {
        return !empty($value) && $value !== 'NULL' ? $value : $default;
    }
}
