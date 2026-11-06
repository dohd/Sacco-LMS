<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Resets a demo copy: clears all business data, the products and the member
 * portal users, and keeps the configuration.
 *
 * Only the tables listed here are touched, so a table added later is never
 * wiped by accident. Kept: chart of accounts, accounting periods (reopened),
 * settings, roles and permissions, staff users and the migrations table.
 */
class ResetDemoData extends Command
{
    protected $signature = 'demo:reset {--force : Do not ask for confirmation}';

    protected $description = 'Delete all demo data, products and member portal users, keeping the configuration (demo copies only)';

    /** Tables emptied with TRUNCATE, so their ids and counters start again. */
    public const CLEARED_TABLES = [
        // People
        'members', 'member_applications', 'nominees', 'nominee_witnesses', 'nominations', 'credit_bureau_consents',
        // Loans
        'loan_applications', 'loan_approvals', 'loans', 'loan_disbursements', 'loan_repayments',
        'loan_repayment_allocations', 'loan_repayment_schedules', 'loan_guarantors', 'loan_guarantor_holds',
        'loan_securities', 'loan_witnesses',
        // Savings
        'savings_accounts', 'savings_account_holds', 'savings_transactions', 'savings_withdrawals',
        // Shares
        'share_accounts', 'share_holds', 'share_transactions',
        // Dividends
        'dividend_runs', 'dividend_allocations', 'dividend_deductions',
        // Ledger
        'journal_entries', 'journal_entry_lines', 'journal_number_sequences',
        // Products
        'loan_products', 'savings_products', 'share_products',
        // Tokens and failures
        'personal_access_tokens', 'failed_jobs', 'password_resets',
    ];

    /** Folders of uploaded documents, per storage disk. */
    public const UPLOAD_DIRECTORIES = [
        'private' => ['memberships', 'loan_applications', 'loan-applications', 'nomination-signatures'],
        'public' => ['loan-applications', 'nomination-signatures', 'files'],
    ];

    public function handle(): int
    {
        if (!config('demo.reset_enabled')) {
            $this->error('The demo reset is switched off. Set DEMO_RESET_ENABLED=true on the demo copy.');
            return 1;
        }
        if (!app()->environment(config('demo.allowed_environments'))) {
            $this->error('The demo reset refuses to run in the "' . app()->environment() . '" environment.');
            return 1;
        }
        if (!$this->option('force') && !$this->confirm('This permanently deletes the data in "' . DB::getDatabaseName() . '". Continue?')) {
            $this->warn('Cancelled.');
            return 1;
        }

        // Member portal users are the users linked to a member. Admins are never removed.
        $memberUserIds = Schema::hasTable('members')
            ? DB::table('members')->whereNotNull('user_id')->pluck('user_id')->all()
            : [];
        $protectedIds = User::role('sacco_admin')->pluck('id')->all();
        $memberUserIds = array_values(array_diff($memberUserIds, $protectedIds));

        $deleted = [];
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (self::CLEARED_TABLES as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $deleted[$table] = DB::table($table)->count();
                DB::table($table)->truncate();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($memberUserIds) {
            foreach (['model_has_roles', 'model_has_permissions'] as $pivot) {
                if (Schema::hasTable($pivot)) {
                    DB::table($pivot)->where('model_type', User::class)->whereIn('model_id', $memberUserIds)->delete();
                }
            }
            $deleted['users (member portal)'] = DB::table('users')->whereIn('id', $memberUserIds)->delete();
        }

        // A period closed during the demo would stop the next demo from posting.
        $reopened = Schema::hasTable('accounting_periods')
            ? DB::table('accounting_periods')->where('status', '!=', 'open')->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null])
            : 0;

        $files = 0;
        foreach (self::UPLOAD_DIRECTORIES as $disk => $directories) {
            foreach ($directories as $directory) {
                if (Storage::disk($disk)->exists($directory)) {
                    $files += count(Storage::disk($disk)->allFiles($directory));
                    Storage::disk($disk)->deleteDirectory($directory);
                }
            }
        }

        $this->table(['Cleared', 'Rows'], collect($deleted)->map(fn($rows, $name) => [$name, $rows])->values()->all());
        $this->info("Reopened {$reopened} accounting period(s); removed {$files} uploaded file(s).");

        return 0;
    }
}
