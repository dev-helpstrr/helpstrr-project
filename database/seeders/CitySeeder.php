<?php

namespace Database\Seeders;

use App\Services\CSVImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('seeders/data/sortkar_loc_cities.csv');
        $cities = CSVImporter::read($file);

        // Prepare chunking (adjust chunk size as needed)
        $chunkSize = 1000;
        $dataChunk = [];

        foreach ($cities as $city) {
            $dataChunk[] = [
                'id'           => $city['id'],
                'zoho_city_id' => $city['zoho_city_id'] ?: null,
                'name'         => $city['name'],
                'state_id'     => $city['state_id'] ?: null,
                'state_code'   => $city['state_code'] ?: null,
                'country_id'   => $city['country_id'] ?: null,
                'country_code' => $city['country_code'] ?: null,
                'latitude'     => $city['latitude'] ?: null,
                'longitude'    => $city['longitude'] ?: null,
                'timezone'     => $city['timezone'] ?: null,
                'flag'         => $city['flag'] ?: 1,
                'wikiDataId'   => $city['wikiDataId'] ?: null,
                'created_at'   => $city['created_at'] ?: now(),
                'updated_at'   => $city['updated_at'] ?: now(),
            ];

            // Insert when chunk is full
            if (count($dataChunk) >= $chunkSize) {
                DB::table('loc_cities')->insert($dataChunk);
                $dataChunk = []; // reset
            }
        }

        // Insert remaining records
        if (!empty($dataChunk)) {
            DB::table('loc_cities')->insert($dataChunk);
        }
    }
}
