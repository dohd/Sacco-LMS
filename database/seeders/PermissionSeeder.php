<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // php artisan make:seed PermissionSeeder
        // php artisan db:seed --class=PermissionSeeder

        $permissions = [];
        $rights = [];
        foreach ($rights as $i => $item) {
            foreach (['edit', 'view'] as $j => $verb) {
                $permissions[] = [
                    'name' => $verb . '-' . $item, 
                    'guard_name' => 'web'
                ];
            }
        }
        DB::table('permissions')->insert($permissions);
        printLog('============== Permissions Loaded Successfully =================');
        $super_user_role = Role::findById(1);
        $permissions = Permission::where('name', 'LIKE', '%code-prefix%')->get();
        foreach ($permissions as $key => $value) {
            $super_user_role->givePermissionTo($value);
        }
        printLog('============== Permissions Assigned Successfully =================');
    }
}
