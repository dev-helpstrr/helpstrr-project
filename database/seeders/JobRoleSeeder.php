<?php

namespace Database\Seeders;

use App\Services\CSVImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobRoleSeeder extends Seeder
{
    public function run()
    {

        $file = database_path('seeders/data/sortkar_job_roles.csv');
        $jobRoles = CSVImporter::read($file);

        foreach ($jobRoles as $jobRole) {
            DB::table('sortkar_job_roles')->insert([
                'id'          => $jobRole['id'],
                'zoho_job_role_id'        => $jobRole['zoho_job_role_id'],
                'role_name'        => $jobRole['role_name'],
                'role_status' => !empty($jobRole['role_status']) && $jobRole['role_status'] !== 'NULL' ? $jobRole['role_status'] : null,
                'created_at'  => !empty($jobRole['created_at']) && $jobRole['created_at'] !== 'NULL' ? $jobRole['created_at'] : now(),
                'updated_at'  => !empty($jobRole['updated_at']) && $jobRole['updated_at'] !== 'NULL' ? $jobRole['updated_at'] : now(),
            ]);
        }
    }
}
