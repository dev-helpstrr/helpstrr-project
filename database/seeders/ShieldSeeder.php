<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use BezhanSalleh\FilamentShield\Support\Utils;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_token","view_any_token","create_token","update_token","restore_token","restore_any_token","replicate_token","reorder_token","delete_token","delete_any_token","force_delete_token","force_delete_any_token","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","page_ManageSetting","page_MyProfilePage"]},{"name":"Moderator","guard_name":"web","permissions":["page_ManageSetting","page_MyProfilePage"]}]';
        $directPermissions = '{"12":{"name":"export_book","guard_name":"web"},"18":{"name":"view_contact","guard_name":"web"},"19":{"name":"view_any_contact","guard_name":"web"},"20":{"name":"create_contact","guard_name":"web"},"21":{"name":"update_contact","guard_name":"web"},"22":{"name":"restore_contact","guard_name":"web"},"23":{"name":"restore_any_contact","guard_name":"web"},"24":{"name":"replicate_contact","guard_name":"web"},"25":{"name":"reorder_contact","guard_name":"web"},"26":{"name":"delete_contact","guard_name":"web"},"27":{"name":"delete_any_contact","guard_name":"web"},"28":{"name":"force_delete_contact","guard_name":"web"},"29":{"name":"force_delete_any_contact","guard_name":"web"},"30":{"name":"view_post","guard_name":"web"},"31":{"name":"view_any_post","guard_name":"web"},"32":{"name":"create_post","guard_name":"web"},"33":{"name":"update_post","guard_name":"web"},"34":{"name":"restore_post","guard_name":"web"},"35":{"name":"restore_any_post","guard_name":"web"},"36":{"name":"replicate_post","guard_name":"web"},"37":{"name":"reorder_post","guard_name":"web"},"38":{"name":"delete_post","guard_name":"web"},"39":{"name":"delete_any_post","guard_name":"web"},"40":{"name":"force_delete_post","guard_name":"web"},"41":{"name":"force_delete_any_post","guard_name":"web"},"42":{"name":"post:create_post","guard_name":"web"},"43":{"name":"post:update_post","guard_name":"web"},"44":{"name":"post:delete_post","guard_name":"web"},"45":{"name":"post:pagination_post","guard_name":"web"},"46":{"name":"post:detail_post","guard_name":"web"}}';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);


                }
            }
            $user = User::where('email', 'admin@admin.com')->first();
            if ($user) {
                $user->assignRole('super_admin');
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
