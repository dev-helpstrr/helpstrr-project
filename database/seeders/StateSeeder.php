<?php

namespace Database\Seeders;

use App\Services\CSVImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('seeders/data/sortkar_loc_states.csv');
        $states = CSVImporter::read($file);
        foreach ($states as $state) {
            DB::table('loc_states')->insert([
                'id'            => $state['id'],
                'zoho_state_id' => !empty($state['zoho_state_id']) && $state['zoho_state_id'] !== 'NULL' ? $state['zoho_state_id'] : null,
                'name'          => $state['name'],
                'country_id'    => !empty($state['country_id']) && $state['country_id'] !== 'NULL' ? $state['country_id'] : null,
                'country_code'  => !empty($state['country_code']) && $state['country_code'] !== 'NULL' ? $state['country_code'] : null,
                'fips_code'     => !empty($state['fips_code']) && $state['fips_code'] !== 'NULL' ? $state['fips_code'] : null,
                'iso2'          => !empty($state['iso2']) && $state['iso2'] !== 'NULL' ? $state['iso2'] : null,
                'iso3166_2'     => !empty($state['iso3166_2']) && $state['iso3166_2'] !== 'NULL' ? $state['iso3166_2'] : null,
                'type'          => !empty($state['type']) && $state['type'] !== 'NULL' ? $state['type'] : null,
                'level'         => !empty($state['level']) && $state['level'] !== 'NULL' ? $state['level'] : null,
                'parent_id'     => !empty($state['parent_id']) && $state['parent_id'] !== 'NULL' ? $state['parent_id'] : null,
                'native'        => !empty($state['native']) && $state['native'] !== 'NULL' ? $state['native'] : null,
                'latitude'      => !empty($state['latitude']) && $state['latitude'] !== 'NULL' ? $state['latitude'] : null,
                'longitude'     => !empty($state['longitude']) && $state['longitude'] !== 'NULL' ? $state['longitude'] : null,
                'timezone'      => !empty($state['timezone']) && $state['timezone'] !== 'NULL' ? $state['timezone'] : null,
                'flag'          => isset($state['flag']) && $state['flag'] !== 'NULL' ? (int) $state['flag'] : 1,
                'wikiDataId'    => !empty($state['wikiDataId']) && $state['wikiDataId'] !== 'NULL' ? $state['wikiDataId'] : null,
                'created_at'    => $state['created_at'] !== 'NULL' ? $state['created_at'] : now(),
                'updated_at'    => $state['updated_at'] !== 'NULL' ? $state['updated_at'] : now(),
            ]);
        }
    }
}
