<?php

namespace Database\Seeders;

use App\Models\User;
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
            'view-members', 'view-reports', 'approve-membership', 'review-loan', 'approve-loan',
            'verify-loan-security', 'manage-guarantors', 'process-disbursement', 'confirm-repayment',
            'reverse-repayment', 'reverse-disbursement', 'write-off-loan', 'invoke-guarantee', 'restructure-loan', 'manage-journals', 'post-journal', 'reverse-journal', 'manage-savings-products', 'manage-savings-accounts',
            'confirm-savings-transaction', 'reverse-savings-transaction',
            'approve-savings-withdrawal', 'pay-savings-withdrawal',
            'manage-share-products', 'manage-share-accounts', 'manage-dividends', 'approve-dividend-runs',
            'post-dividend-runs', 'pay-dividends', 'reverse-dividends', 'confirm-share-transaction',
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
            'credit_officer' => ['view-dashboard', 'view-members', 'view-reports', 'approve-membership', 'review-loan',
                'approve-loan', 'verify-loan-security', 'manage-guarantors', 'restructure-loan', 'view-own-loan'],
            'cashier' => ['view-dashboard', 'view-reports', 'create-savings-transaction', 'confirm-savings-transaction',
                'approve-savings-withdrawal', 'pay-savings-withdrawal', 'confirm-repayment'],
            'accountant' => ['view-dashboard', 'view-reports', 'write-off-loan', 'invoke-guarantee', 'manage-journals', 'post-journal', 'reverse-journal', 'reverse-repayment', 'reverse-savings-transaction',
                'reverse-share-transaction', 'manage-dividends', 'approve-dividend-runs',
                'post-dividend-runs', 'pay-dividends', 'reverse-dividends'],
        ];

        $memberPermissionNames = $rolePermissions['member'];
        $staffPermissionNames = array_values(array_diff($permissions, $memberPermissionNames));

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
            if ($keyName === 'member') {
                $role->revokePermissionTo($staffPermissionNames);
            }
        }

        // Remove direct grants too; a member role must not inherit staff power
        // from an earlier bootstrap or a previous manual permission change.
        User::whereHas('roles', function ($query) use ($rolesHaveKeys) {
            $query->where(function ($roleQuery) use ($rolesHaveKeys) {
                $roleQuery->where('name', 'member');
                if ($rolesHaveKeys) $roleQuery->orWhere('key_name', 'member');
            });
        })->get()->each(function (User $member) use ($staffPermissionNames) {
            $member->revokePermissionTo($staffPermissionNames);
        });

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

        // Bootstrap the first account with direct permissions so an existing
        // installation has an immediately usable administrator. This writes
        // to model_has_permissions in addition to the role assignment.
        $firstUser = User::query()->orderBy('id')->first();
        if ($firstUser && !$firstUser->hasRole('member')) {
            $firstUser->givePermissionTo(Permission::where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->get());
        }
    }
}
