<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;
use Spatie\Permission\Models\Role;
class ServiceCategorySeeder extends Seeder
{
    public function run()
    {
        $driverRole = Role::firstOrCreate(['name' => 'Driver']);
        $chefRole = Role::firstOrCreate(['name' => 'Chef']);
        $maidRole = Role::firstOrCreate(['name' => 'Maid']);
        $driver = ServiceCategory::create(['role_id' => $driverRole->id, 'name' => 'LMV Driver']);
        ServiceCategory::create(['role_id' => $driverRole->id, 'parent_id' => $driver->id, 'name' => 'Automatic']);
        ServiceCategory::create(['role_id' => $driverRole->id, 'parent_id' => $driver->id, 'name' => 'Manual']);
        $chef = ServiceCategory::create(['role_id' => $chefRole->id, 'name' => 'Indian']);
        $north = ServiceCategory::create(['role_id' => $chefRole->id, 'parent_id' => $chef->id, 'name' => 'North Indian']);
        ServiceCategory::create(['role_id' => $chefRole->id, 'parent_id' => $north->id, 'name' => 'Tandoori']);
    }
}
