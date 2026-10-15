<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SaccoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolesHaveKeys = DB::getSchemaBuilder()->hasColumn('roles', 'key_name');
        $permissions = [
            'view-dashboard', 'view-own-membership', 'edit-own-membership',
            'create-loan-application', 'edit-own-loan-application', 'submit-loan-application',
            'view-own-loan', 'create-savings-transaction', 'request-savings-withdrawal',
            'view-own-savings', 'view-own-shares', 'view-own-repayment',
            'view-members', 'approve-membership', 'review-loan', 'approve-loan',
            'verify-loan-security', 'process-disbursement', 'confirm-repayment',
            'reverse-repayment', 'reverse-disbursement', 'manage-savings-products', 'manage-savings-accounts',
            'confirm-savings-transaction', 'reverse-savings-transaction',
            'approve-savings-withdrawal', 'pay-savings-withdrawal',
            'manage-share-products', 'manage-share-accounts', 'confirm-share-transaction',
            'reverse-share-transaction', 'manage-users', 'manage-roles', 'manage-settings',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'member' => array_filter($permissions, fn ($permission) => strpos($permission, 'own-') !== false
                || in_array($permission, ['view-dashboard', 'create-loan-application',
                    'edit-own-loan-application', 'submit-loan-application',
                    'create-savings-transaction', 'request-savings-withdrawal'])),
            'credit_officer' => ['view-dashboard', 'view-members', 'approve-membership', 'review-loan',
                'approve-loan', 'verify-loan-security', 'view-own-loan'],
            'cashier' => ['view-dashboard', 'create-savings-transaction', 'confirm-savings-transaction',
                'approve-savings-withdrawal', 'pay-savings-withdrawal', 'confirm-repayment'],
            'accountant' => ['view-dashboard', 'reverse-repayment', 'reverse-savings-transaction',
                'reverse-share-transaction'],
        ];

        foreach ($rolePermissions as $keyName => $rolePermissionNames) {
            $role = Role::where('name', $keyName)->where('guard_name', 'web')->first();
            if (!$role) {
                $attributes = ['name' => $keyName, 'guard_name' => 'web'];
                if ($rolesHaveKeys) {
                    $attributes += ['key_name' => $keyName, 'description' => ucfirst(str_replace('_', ' ', $keyName)), 'is_active' => true];
                }
                $role = Role::create($attributes);
            }
            if ($rolesHaveKeys) {
                $role->update(['key_name' => $keyName, 'is_active' => true]);
            }
            $role->givePermissionTo($rolePermissionNames);
        }

        $admin = Role::where('name', 'sacco_admin')->where('guard_name', 'web')->first();
        if (!$admin) {
            $attributes = ['name' => 'sacco_admin', 'guard_name' => 'web'];
            if ($rolesHaveKeys) {
                $attributes += ['key_name' => 'sacco_admin', 'description' => 'Sacco administrator', 'is_active' => true];
            }
            $admin = Role::create($attributes);
        }
        if ($rolesHaveKeys) {
            $admin->update(['key_name' => 'sacco_admin', 'is_active' => true]);
        }
        $admin->givePermissionTo($permissions);
    }
}
