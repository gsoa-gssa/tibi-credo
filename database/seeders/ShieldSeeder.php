<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_batch","view_any_batch","create_batch","update_batch","restore_batch","restore_any_batch","replicate_batch","reorder_batch","delete_batch","delete_any_batch","force_delete_batch","force_delete_any_batch","view_commune","view_any_commune","create_commune","update_commune","restore_commune","restore_any_commune","replicate_commune","reorder_commune","delete_commune","delete_any_commune","force_delete_commune","force_delete_any_commune","view_contact","view_any_contact","create_contact","update_contact","restore_contact","restore_any_contact","replicate_contact","reorder_contact","delete_contact","delete_any_contact","force_delete_contact","force_delete_any_contact","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_sheet","view_any_sheet","create_sheet","update_sheet","restore_sheet","restore_any_sheet","replicate_sheet","reorder_sheet","delete_sheet","delete_any_sheet","force_delete_sheet","force_delete_any_sheet","view_source","view_any_source","create_source","update_source","delete_source","delete_any_source","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_zipcode","view_any_zipcode","create_zipcode","update_zipcode","restore_zipcode","restore_any_zipcode","replicate_zipcode","reorder_zipcode","delete_zipcode","delete_any_zipcode","force_delete_zipcode","force_delete_any_zipcode","page_SheetWorkflow","view_counting","view_any_counting","create_counting","update_counting","restore_counting","restore_any_counting","replicate_counting","reorder_counting","delete_counting","delete_any_counting","force_delete_counting","force_delete_any_counting","view_token","view_any_token","create_token","update_token","restore_token","restore_any_token","replicate_token","reorder_token","delete_token","delete_any_token","force_delete_token","force_delete_any_token","page_CreateBatchWorkflow","widget_SignatureCountStats","search_source"]},{"name":"Certifier","guard_name":"web","permissions":["create_contact","update_contact","create_sheet","page_SheetWorkflow"]},{"name":"Batcher","guard_name":"web","permissions":["view_batch","view_any_batch","create_batch","update_batch","restore_batch","restore_any_batch","replicate_batch","reorder_batch","delete_batch","delete_any_batch","force_delete_batch","force_delete_any_batch","view_sheet","view_any_sheet","create_sheet","update_sheet","restore_sheet","restore_any_sheet","replicate_sheet","reorder_sheet","delete_sheet","delete_any_sheet","force_delete_sheet","force_delete_any_sheet"]}]';
        $directPermissions = '{"58":{"name":"restore_source","guard_name":"web"},"59":{"name":"restore_any_source","guard_name":"web"},"60":{"name":"replicate_source","guard_name":"web"},"61":{"name":"reorder_source","guard_name":"web"},"64":{"name":"force_delete_source","guard_name":"web"},"65":{"name":"force_delete_any_source","guard_name":"web"},"91":{"name":"widget_BlogPostsOverview","guard_name":"web"},"118":{"name":"find_source","guard_name":"web"}}';

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
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
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
