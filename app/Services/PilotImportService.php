<?php

namespace App\Services;

use App\Imports\PilotWorkbook;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\LoanApplications\Loan;
use App\Models\LoanApplications\LoanApplication;
use App\Models\LoanApplications\LoanApproval;
use App\Models\LoanApplications\LoanProduct;
use App\Models\Memberships\Member;
use App\Models\Memberships\MemberApplication;
use App\Models\PilotImportBatch;
use App\Models\Savings\SavingsAccount;
use App\Models\Savings\SavingsProduct;
use App\Models\Savings\SavingsTransaction;
use App\Models\Shares\ShareAccount;
use App\Models\Shares\ShareProduct;
use App\Models\Shares\ShareTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

/** Validation and one-time commit for the pilot's opening member balances. */
class PilotImportService
{
    public function products(): array
    {
        return [
            'savings' => SavingsProduct::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'shares' => ShareProduct::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'unit_value']),
            'loans' => LoanProduct::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'opening_accounts' => ChartOfAccount::where('account_type', 'equity')->where('is_active', true)->where('is_postable', true)->where('is_control_account', false)->orderBy('code')->get(['id', 'code', 'name']),
        ];
    }

    public function preview($file, array $ids, string $openingDate): array
    {
        $errors = [];
        $parsedOpeningDate = \DateTime::createFromFormat('!Y-m-d', $openingDate);
        if (!$parsedOpeningDate || $parsedOpeningDate->format('Y-m-d') !== $openingDate) {
            $errors[] = 'Choose a valid opening balance date.';
        }
        $rows = $this->readMembership($file, $errors);
        $products = $this->products();
        $selected = [];
        foreach (['savings', 'shares', 'loans', 'opening_equity'] as $key) {
            $selected[$key] = $products[$key === 'opening_equity' ? 'opening_accounts' : $key]->firstWhere('id', (int) ($ids[$key . '_product_id'] ?? $ids[$key . '_account_id'] ?? 0));
            if (!$selected[$key]) $errors[] = 'Choose an active ' . ($key === 'opening_equity' ? 'postable equity ledger account' : 'product') . '.';
        }

        $totals = ['shares' => 0.0, 'deposit_balance' => 0.0, 'loan_balance' => 0.0, 'interest_accrued' => 0.0];
        $seenNumbers = $seenIds = $seenEmails = [];
        $newMemberCount = 0;
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $number = strtoupper(trim((string) ($row['import_membership_number'] ?? '')));
            if ($number === '' && strtoupper(trim((string) ($row['comments'] ?? ''))) === 'NEW') $number = 'NEW';
            if ($number !== 'NEW' && $number !== '' && isset($seenNumbers[$number])) $errors[] = "Membership row $line: duplicate import_membership_number $number.";
            elseif ($number !== 'NEW') $seenNumbers[$number] = true;

            foreach (['first_name', 'last_name', 'date_of_birth', 'national_id', 'phone', 'email', 'admission_date', 'shares', 'deposit_balance', 'loan_balance', 'interest_accrued'] as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value === '') $errors[] = "Membership row $line: $field is required; use MISSING for unavailable KYC details.";
                if (strtoupper($value) === 'MISSING' && !in_array($field, ['date_of_birth', 'national_id', 'phone', 'email', 'admission_date'], true)) $errors[] = "Membership row $line: $field cannot be MISSING.";
            }
            foreach (array_keys($totals) as $field) {
                $value = $row[$field] ?? null;
                if (!is_numeric($value) || (float) $value < 0) $errors[] = "Membership row $line: $field must be a numeric amount greater than or equal to zero.";
                else $totals[$field] += (float) $value;
            }
            foreach (['date_of_birth', 'admission_date'] as $field) {
                if (strtoupper(trim((string) ($row[$field] ?? ''))) !== 'MISSING' && !$this->parseDate($row[$field] ?? null)) $errors[] = "Membership row $line: $field must be a real Excel date or dd/mm/yyyy.";
            }
            $idNumber = strtoupper(trim((string) ($row['national_id'] ?? '')));
            if ($idNumber !== '' && $idNumber !== 'MISSING') {
                if (isset($seenIds[$idNumber])) $errors[] = "Membership row $line: duplicate national_id $idNumber.";
                $seenIds[$idNumber] = true;
            }
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email !== '' && !in_array($email, ['none', 'missing'], true)) {
                if (isset($seenEmails[$email])) $errors[] = "Membership row $line: duplicate email $email.";
                $seenEmails[$email] = true;
            }
            if (isset($row['status']) && !in_array(strtolower(trim((string) $row['status'])), ['active', 'defaulter', 'defaulted', 'dormant', 'exited'], true)) $errors[] = "Membership row $line: status must be active, defaulter, defaulted, dormant, or exited.";
            if (isset($row['loan_status']) && !in_array(strtolower(trim((string) $row['loan_status'])), ['active', 'in_arrears', 'defaulted', 'cleared'], true)) $errors[] = "Membership row $line: loan_status must be active, in_arrears, defaulted, or cleared.";
            if ($selected['shares'] && (float) $selected['shares']->unit_value > 0 && round(fmod((float) ($row['shares'] ?? 0), (float) $selected['shares']->unit_value), 2) !== 0.0) $errors[] = "Membership row $line: shares must be an exact multiple of the selected product's unit value.";
        }

        $this->checkSelectedLedgers($selected, $errors);
        if ($parsedOpeningDate && $parsedOpeningDate->format('Y-m-d') === $openingDate) {
            $period = AccountingPeriod::whereDate('start_date', '<=', $openingDate)->whereDate('end_date', '>=', $openingDate)->where('status', 'open')->first();
            if (!$period) $errors[] = 'An open accounting period must contain ' . $parsedOpeningDate->format('d/m/Y') . ' for opening balance journal postings.';
        }

        foreach ($rows as $index => $row) {
            $number = strtoupper(trim((string) ($row['import_membership_number'] ?? '')));
            if ($number === '' && strtoupper(trim((string) ($row['comments'] ?? ''))) === 'NEW') $number = 'NEW';
            $member = $number !== '' && $number !== 'NEW'
                ? Member::where('import_membership_number', $number)->first()
                : null;
            // Older workbooks used membership_number for the source identifier.
            if (!$member && $number !== '' && $number !== 'NEW') $member = Member::where('membership_number', $number)->first();
            if ($member) {
                $incomingId = trim((string) ($row['national_id'] ?? ''));
                if ($incomingId !== '' && strtoupper($incomingId) !== 'MISSING' && $member->national_id !== $incomingId) $errors[] = 'Membership row ' . ($index + 2) . ": membership number $number belongs to a different national_id.";
                $incomingName = trim(implode(' ', array_filter([$row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? ''])));
                if (strcasecmp(preg_replace('/\\s+/', ' ', $incomingName), preg_replace('/\\s+/', ' ', trim($member->full_name))) !== 0) $errors[] = 'Membership row ' . ($index + 2) . ": membership number $number belongs to a different member name.";
                if (SavingsAccount::where('member_id', $member->id)->where('savings_product_id', optional($selected['savings'])->id)->exists() ||
                    ShareAccount::where('member_id', $member->id)->where('share_product_id', optional($selected['shares'])->id)->exists() ||
                    Loan::where('member_id', $member->id)->exists()) {
                    $errors[] = 'Membership row ' . ($index + 2) . ": member $number already has a pilot balance account; refusing to duplicate opening balances.";
                }
            } else $newMemberCount++;
        }

        return [
            'opening_date' => $openingDate,
            'errors' => array_values(array_unique($errors)),
            'counts' => ['members' => count($rows), 'new_members' => $newMemberCount, 'kyc_pending' => count(array_filter($rows, function ($r) { foreach (['date_of_birth', 'national_id', 'phone', 'email', 'admission_date'] as $f) if (strtoupper(trim((string) ($r[$f] ?? ''))) === 'MISSING') return true; return false; }))],
            'totals' => $totals,
            'products' => [
                'savings' => $this->productLabel($selected['savings']), 'shares' => $this->productLabel($selected['shares']),
                'loans' => $this->productLabel($selected['loans']), 'opening_equity' => $this->productLabel($selected['opening_equity']),
            ],
            'status_mapping' => [
                'active' => 'active', 'defaulter' => 'active (source status retained in remarks)', 'defaulted' => 'active (source status retained in remarks)',
                'dormant' => 'dormant', 'exited' => 'closed',
            ],
        ];
    }

    public function stage($file, array $ids, int $userId, string $openingDate): \stdClass
    {
        $token = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('pilot-imports', $token . '.' . $extension, 'local');
        $id = DB::table('pilot_import_batches')->insertGetId([
            'token' => $token, 'user_id' => $userId, 'status' => 'previewed', 'membership_path' => $path,
            'opening_date' => $openingDate,
            'savings_product_id' => $ids['savings_product_id'], 'share_product_id' => $ids['shares_product_id'],
            'loan_product_id' => $ids['loans_product_id'], 'opening_equity_account_id' => $ids['opening_equity_account_id'],
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return (object) ['id' => $id, 'token' => $token];
    }

    public function commit(string $token, int $userId): array
    {
        $batch = PilotImportBatch::where('token', $token)->where('user_id', $userId)->first();
        if (!$batch) throw ValidationException::withMessages(['batch' => 'Pilot preview was not found. Upload the workbook and preview it again.']);
        if ($batch->status === 'committed') return $batch->result ?: [];
        if ($batch->status !== 'previewed') throw ValidationException::withMessages(['batch' => 'This pilot batch cannot be committed in its current state.']);

        $path = Storage::disk('local')->path($batch->membership_path);
        if (!file_exists($path)) throw ValidationException::withMessages(['batch' => 'The staged workbook has expired. Upload and preview it again.']);
        $ids = ['savings_product_id' => $batch->savings_product_id, 'shares_product_id' => $batch->share_product_id, 'loans_product_id' => $batch->loan_product_id, 'opening_equity_account_id' => $batch->opening_equity_account_id];
        $report = $this->preview(new \Illuminate\Http\UploadedFile($path, basename($path), null, null, true), $ids, $batch->opening_date);
        if ($report['errors']) throw ValidationException::withMessages(['batch' => 'The batch no longer passes validation. Preview it again to review current errors.']);

        return DB::transaction(function () use ($batch, $path, $ids, $report, $userId) {
            $locked = PilotImportBatch::whereKey($batch->id)->lockForUpdate()->first();
            if ($locked->status === 'committed') return $locked->result ?: [];
            $savingsProduct = SavingsProduct::findOrFail($ids['savings_product_id']);
            $shareProduct = ShareProduct::findOrFail($ids['shares_product_id']);
            $loanProduct = LoanProduct::findOrFail($ids['loans_product_id']);
            $openingAccount = ChartOfAccount::findOrFail($ids['opening_equity_account_id']);
            $rows = $this->readMembership((new \Illuminate\Http\UploadedFile($path, basename($path), null, null, true)), $readErrors);
            if ($readErrors) throw ValidationException::withMessages(['batch' => implode(' ', $readErrors)]);

            $result = ['opening_date' => $locked->opening_date, 'created_members' => 0, 'existing_members' => 0, 'member_logins_created' => 0, 'savings_accounts' => 0, 'share_accounts' => 0, 'opening_loans' => 0, 'membership_numbers' => []];
            foreach ($rows as $i => $row) {
                $member = $this->resolveMember($row, $i + 2, $batch, $userId, $result, $locked->opening_date);
                $this->createOpeningShare($member, $row, $shareProduct, $openingAccount, $batch, $userId, $i + 2, $result, $locked->opening_date);
                $this->createOpeningSavings($member, $row, $savingsProduct, $openingAccount, $batch, $userId, $i + 2, $result, $locked->opening_date);
                $this->createOpeningLoan($member, $row, $loanProduct, $batch, $userId, $i + 2, $result, $locked->opening_date);
            }
            $this->postOpeningLoanJournal($batch, $loanProduct, $openingAccount, $userId, $locked->opening_date);
            unset($result['_loan_journal_lines']);

            $locked->update(['status' => 'committed', 'result' => $result, 'committed_at' => now()]);
            return $result;
        });
    }

    private function readMembership($file, array &$errors): array
    {
        try { $sheets = Excel::toArray(new PilotWorkbook(), $file); }
        catch (\Throwable $e) { $errors[] = 'Membership workbook could not be read: ' . $e->getMessage(); return []; }
        $rows = $sheets[0] ?? [];
        if (count($rows) < 2) { $errors[] = 'Membership workbook has no member rows.'; return []; }
        $headers = array_map(function ($value) { return $this->normalizeHeader($value); }, array_shift($rows));
        $out = [];
        foreach ($rows as $values) {
            if (!array_filter($values, function ($v) { return $v !== null && trim((string) $v) !== ''; })) continue;
            $row = [];
            foreach ($headers as $i => $key) if ($key !== '') $row[$key] = $values[$i] ?? null;
            if (empty($row['first_name']) && !empty($row['member_name'])) {
                $parts = preg_split('/\s+/', trim((string) $row['member_name']));
                $row['first_name'] = array_shift($parts);
                $row['last_name'] = count($parts) ? array_pop($parts) : '';
                $row['middle_name'] = implode(' ', $parts);
            }
            $out[] = $row;
        }
        return $out;
    }

    private function normalizeHeader($value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        $key = trim(trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $value))), '_'));
        $aliases = [
            'membership_no' => 'import_membership_number', 'member_no' => 'import_membership_number', 'member_number' => 'import_membership_number',
            'membership_number' => 'import_membership_number', 'user_membership_number' => 'import_membership_number',
            'user_defined_membership_number' => 'import_membership_number', 'legacy_membership_number' => 'import_membership_number', 'member_id' => 'import_membership_number',
            'name' => 'member_name', 'full_name' => 'member_name', 'member' => 'member_name',
            'share_balance' => 'shares', 'share_capital' => 'shares', 'share_contribution' => 'shares',
            'deposits' => 'deposit_balance', 'savings_balance' => 'deposit_balance', 'deposit' => 'deposit_balance',
            'loan_bal' => 'loan_balance', 'interest_balance' => 'interest_accrued', 'accumulated_interest' => 'interest_accrued', 'interest_accrued_balance' => 'interest_accrued',
            'dob' => 'date_of_birth', 'date_joined' => 'admission_date', 'join_date' => 'admission_date',
            'comments' => 'comments', 'member_status' => 'status', 'legacy_status' => 'status', 'loan_status_31_12_2025' => 'loan_status',
        ];
        return $aliases[$key] ?? $key;
    }

    private function checkSelectedLedgers(array $selected, array &$errors): void
    {
        if ($selected['savings'] && $selected['savings']->allows_withdrawals) $errors[] = 'Choose a non-withdrawable savings product for the directive deposit balance.';
        if ($selected['shares'] && (float) $selected['shares']->unit_value <= 0) $errors[] = 'The selected share product must have a positive unit value.';
        foreach ([['savings', 'savingsControlAccount'], ['shares', 'shareCapitalAccount'], ['loans', 'loanPrincipalAccount'], ['loans', 'interestReceivableAccount']] as [$productKey, $relation]) {
            $account = optional($selected[$productKey])->{$relation};
            if (!$account || !$account->is_active || !$account->is_postable) $errors[] = ucfirst($productKey) . ' product is missing an active, postable mapped ledger account.';
        }
        $register = ChartOfAccount::where('config_key', 'individual_savings_register')->first();
        if ($register && (int) optional(optional($selected['savings'])->savingsControlAccount)->id !== (int) $register->id) $errors[] = 'Selected savings product must use the configured individual savings register ledger.';
        $offsetId = (int) optional($selected['opening_equity'])->id;
        $productLedgerIds = [
            optional(optional($selected['savings'])->savingsControlAccount)->id,
            optional(optional($selected['shares'])->shareCapitalAccount)->id,
            optional(optional($selected['loans'])->loanPrincipalAccount)->id,
            optional(optional($selected['loans'])->interestReceivableAccount)->id,
        ];
        if ($offsetId && in_array($offsetId, array_map('intval', array_filter($productLedgerIds)), true)) {
            $errors[] = 'Opening balance equity account must be separate from the selected savings, share capital, loan principal, and interest receivable ledgers.';
        }
    }

    private function resolveMember(array $row, int $line, $batch, int $userId, array &$result, string $openingDate): Member
    {
        $number = strtoupper(trim((string) ($row['import_membership_number'] ?? '')));
        if ($number === '' && strtoupper(trim((string) ($row['comments'] ?? ''))) === 'NEW') $number = 'NEW';
        if ($number !== 'NEW') {
            $existing = $number === '' ? null : Member::where('import_membership_number', $number)->first();
            // Keep imports made with the previous membership_number header compatible.
            if (!$existing && $number !== '') $existing = Member::where('membership_number', $number)->first();
            if ($existing) {
                if (!$existing->import_membership_number && $number !== '') $existing->import_membership_number = $number;
                $sourceStatus = strtolower(trim((string) ($row['status'] ?? '')));
                $remarks = trim((string) ($row['remarks'] ?? $row['comments'] ?? ''));
                if (in_array($sourceStatus, ['defaulter', 'defaulted'], true)) $remarks = trim($remarks . ' Legacy status: ' . $sourceStatus . '.');
                $changes = ['remarks' => $remarks ?: $existing->remarks];
                if ($sourceStatus !== '') {
                    $status = $sourceStatus === 'exited' ? 'closed' : ($sourceStatus === 'dormant' ? 'dormant' : 'active');
                    $changes['status'] = $status;
                    $changes['is_active'] = $status !== 'closed';
                }
                $existing->update($changes);
                if ($this->ensureImportedMemberLogin($existing)) {
                    $result['member_logins_created']++;
                    $result['membership_numbers'][] = ['row' => $line, 'member' => trim($existing->full_name), 'membership_number' => $existing->membership_number, 'import_membership_number' => $existing->import_membership_number, 'kyc_status' => $existing->kyc_status];
                }
                $result['existing_members']++;
                return $existing;
            }
        }
        $importMembershipNumber = $number === 'NEW' ? null : ($number !== '' ? $number : null);
        $number = $this->nextMembershipNumber();
        if (!$importMembershipNumber) $importMembershipNumber = $number;

        $missing = function ($field) use ($row) { return strtoupper(trim((string) ($row[$field] ?? ''))) === 'MISSING'; };
        // Imported identity records remain unverified until an administrator completes KYC.
        $kyc = 'pending';
        $nationalId = $missing('national_id') ? 'PILOT-PENDING-' . $batch->id . '-' . $line : trim((string) $row['national_id']);
        $email = in_array(strtoupper(trim((string) $row['email'])), ['NONE', 'MISSING'], true) ? 'pilot-' . $batch->id . '-' . $line . '@upenyo.invalid' : trim((string) $row['email']);
        $phone = $missing('phone') ? 'PILOT-PENDING-' . $batch->id . '-' . $line : trim((string) $row['phone']);
        $statusSource = strtolower(trim((string) ($row['status'] ?? 'active')));
        $memberStatus = $statusSource === 'exited' ? 'closed' : ($statusSource === 'dormant' ? 'dormant' : 'active');
        $remarks = trim((string) ($row['remarks'] ?? $row['comments'] ?? ''));
        if (in_array($statusSource, ['defaulter', 'defaulted'], true)) $remarks = trim($remarks . ' Legacy status: ' . $statusSource . '.');
        $missingKycDetails = array_filter(['date_of_birth', 'national_id', 'phone', 'email', 'admission_date'], $missing);
        $remarks = trim($remarks . (count($missingKycDetails)
            ? ' KYC pending: one or more required details were marked MISSING.'
            : ' KYC pending: administrator verification is required.'));
        $application = MemberApplication::create([
            'application_number' => 'PILOT-' . $batch->id . '-' . str_pad((string) $line, 4, '0', STR_PAD_LEFT),
            'application_channel' => 'import', 'first_name' => trim((string) $row['first_name']),
            'middle_name' => $this->nullableValue($row['middle_name'] ?? null), 'last_name' => trim((string) $row['last_name']),
            'date_of_birth' => $missing('date_of_birth') ? null : $this->parseDate($row['date_of_birth']),
            'place_of_birth' => $this->nullableValue($row['place_of_birth'] ?? null), 'national_id' => $nationalId,
            'phone' => $phone, 'email' => $email, 'current_address' => $this->nullableValue($row['current_address'] ?? null),
            'residential_address' => $this->nullableValue($row['residential_address'] ?? null),
            'next_of_kin_name' => $this->nullableValue($row['next_of_kin_name'] ?? null), 'next_of_kin_id' => $this->nullableValue($row['next_of_kin_id'] ?? null),
            'next_of_kin_relationship' => $this->nullableValue($row['next_of_kin_relationship'] ?? null), 'monthly_contribution' => 0,
            'contribution_start_date' => null, 'status' => 'approved', 'approved_by' => $userId, 'approved_at' => now(),
            'application_date' => $openingDate, 'kyc_status' => $kyc, 'review_notes' => 'Directly approved through pilot import batch ' . $batch->token,
        ]);
        $member = Member::create([
            'member_application_id' => $application->id, 'membership_number' => $number, 'import_membership_number' => $importMembershipNumber, 'first_name' => $application->first_name,
            'middle_name' => $application->middle_name, 'last_name' => $application->last_name, 'date_of_birth' => $application->date_of_birth,
            'place_of_birth' => $application->place_of_birth, 'national_id' => $nationalId, 'phone' => $phone, 'email' => $email,
            'current_address' => $application->current_address, 'residential_address' => $application->residential_address,
            'admission_date' => $missing('admission_date') ? null : $this->parseDate($row['admission_date']), 'approved_by' => $userId,
            'approved_at' => now(), 'status' => $memberStatus, 'is_active' => $memberStatus !== 'closed', 'remarks' => $remarks, 'kyc_status' => $kyc,
        ]);
        if ($this->ensureImportedMemberLogin($member)) $result['member_logins_created']++;
        $result['created_members']++;
        $result['membership_numbers'][] = ['row' => $line, 'member' => trim($member->full_name), 'membership_number' => $number, 'import_membership_number' => $member->import_membership_number, 'kyc_status' => $kyc];
        return $member;
    }

    private function ensureImportedMemberLogin(Member $member): bool
    {
        if ($member->user_id) return false;

        $role = Role::where('key_name', 'member')->orWhere('name', 'member')->first();
        if (!$role) throw ValidationException::withMessages(['member_login' => 'The member role must exist before imported member logins can be created.']);

        $loginIdentifier = trim((string) ($member->import_membership_number ?: $member->membership_number));
        $user = User::create([
            'name' => trim($member->full_name),
            'email' => 'member-import-' . $member->id . '@upenyo.invalid',
            'password' => $loginIdentifier,
            'is_active' => (bool) $member->is_active,
            'member_login_setup_required' => true,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);
        $member->update(['user_id' => $user->id]);
        return true;
    }

    private function createOpeningShare(Member $member, array $row, ShareProduct $product, ChartOfAccount $offset, $batch, int $userId, int $line, array &$result, string $openingDate): void
    {
        $amount = round((float) $row['shares'], 2);
        $account = ShareAccount::create(['member_id' => $member->id, 'share_product_id' => $product->id,
            'account_number' => 'SHR-' . $member->membership_number, 'total_units' => (int) round($amount / (float) $product->unit_value),
            'share_balance' => $amount, 'held_amount' => 0, 'available_amount' => $amount, 'opened_date' => $openingDate, 'status' => $member->status === 'closed' ? 'closed' : 'active', 'closed_date' => $member->status === 'closed' ? $openingDate : null]);
        $result['share_accounts']++;
        if ($amount <= 0) return;
        $transaction = ShareTransaction::create(['share_account_id' => $account->id, 'transaction_number' => 'PILOT-SH-' . $batch->id . '-' . $line,
            'transaction_type' => 'adjustment', 'direction' => 'credit', 'units' => (int) round($amount / (float) $product->unit_value),
            'unit_value' => $product->unit_value, 'amount' => $amount, 'running_units' => $account->total_units, 'running_balance' => $amount,
            'transaction_date' => $openingDate, 'value_date' => $openingDate, 'payment_method' => 'system',
            'ledger_account_id' => $offset->id, 'status' => 'confirmed', 'recorded_by' => $userId,
            'description' => 'Pilot opening share balance. Source row ' . $line . '; batch ' . $batch->token]);
        app(JournalPostingService::class)->postShareTransaction($transaction);
    }

    private function createOpeningSavings(Member $member, array $row, SavingsProduct $product, ChartOfAccount $offset, $batch, int $userId, int $line, array &$result, string $openingDate): void
    {
        $amount = round((float) $row['deposit_balance'], 2);
        $account = SavingsAccount::create(['member_id' => $member->id, 'savings_product_id' => $product->id,
            'account_number' => 'SAV-' . $member->membership_number, 'ledger_balance' => $amount, 'held_balance' => 0,
            'available_balance' => $amount, 'opened_date' => $openingDate, 'opened_by' => $userId,
            'last_transaction_date' => $amount > 0 ? $openingDate : null, 'status' => $member->status === 'closed' ? 'closed' : 'active', 'closed_date' => $member->status === 'closed' ? $openingDate : null]);
        $result['savings_accounts']++;
        if ($amount <= 0) return;
        $transaction = SavingsTransaction::create(['savings_account_id' => $account->id, 'transaction_number' => 'PILOT-SV-' . $batch->id . '-' . $line,
            'transaction_type' => 'adjustment', 'direction' => 'credit', 'amount' => $amount, 'running_balance' => $amount,
            'transaction_date' => $openingDate, 'value_date' => $openingDate, 'payment_method' => 'system',
            'offset_account_id' => $offset->id, 'status' => 'confirmed', 'recorded_by' => $userId, 'confirmed_by' => $userId,
            'confirmed_at' => now(), 'description' => 'Pilot non-withdrawable deposit opening balance. Source row ' . $line . '; batch ' . $batch->token]);
        app(JournalPostingService::class)->postSavingsMovement($transaction);
    }

    private function createOpeningLoan(Member $member, array $row, LoanProduct $product, $batch, int $userId, int $line, array &$result, string $openingDate): void
    {
        $principal = round((float) $row['loan_balance'], 2);
        $interest = round((float) $row['interest_accrued'], 2);
        if ($principal <= 0 && $interest <= 0) return;
        $application = LoanApplication::create(['member_id' => $member->id, 'loan_product_id' => $product->id,
            'application_number' => 'PILOT-LOAN-' . $batch->id . '-' . $line, 'amount_requested' => $principal, 'amount_approved' => $principal,
            'approved_repayment_months' => 0, 'approved_monthly_installment' => 0, 'approved_interest_rate' => $product->interest_rate,
            'approved_interest_method' => $product->interest_method, 'approved_interest_frequency' => $product->interest_frequency,
            'payment_mode' => 'cash', 'status' => 'approved', 'drafted_by' => $userId, 'approved_by' => $userId,
            'approved_at' => now(), 'drafted_at' => now(), 'loan_purpose' => 'Legacy opening balance import; original terms not supplied.']);
        $approval = LoanApproval::create(['loan_application_id' => $application->id, 'member_id' => $member->id,
            'approved_amount' => $principal, 'repayment_months' => 0, 'monthly_installment' => 0,
            'interest_rate' => $product->interest_rate, 'decision' => 'approved', 'approved_by' => $userId,
            'reason' => 'Technical approval record required to attach the legacy opening loan. No new credit decision was made.']);
        $sourceStatus = strtolower(trim((string) ($row['loan_status'] ?? $row['status'] ?? 'active')));
        $loanStatus = in_array($sourceStatus, ['defaulter', 'defaulted', 'in_arrears'], true) ? 'in_arrears' : (in_array($sourceStatus, ['exited', 'dormant'], true) ? 'closed' : ($sourceStatus === 'cleared' ? 'fully_paid' : 'active'));
        $remarks = 'Legacy opening loan imported at ' . date('d/m/Y', strtotime($openingDate)) . '. Original status: ' . $sourceStatus . '. ' . trim((string) ($row['interest_period_covered'] ?? '')) . ' ' . trim((string) ($row['remarks'] ?? ''));
        $loan = Loan::create(['loan_application_id' => $application->id, 'member_id' => $member->id, 'loan_approval_id' => $approval->id,
            'loan_number' => 'PILOT-LN-' . $batch->id . '-' . $line, 'approved_amount' => $principal, 'amount_disbursed' => 0,
            'repayment_period_months' => 0, 'interest_rate' => $product->interest_rate, 'monthly_installment' => 0,
            'interest_method' => $product->interest_method, 'disbursement_date' => null, 'payment_mode' => 'cash',
            'principal_balance' => $principal, 'interest_balance' => $interest, 'total_outstanding_balance' => $principal + $interest,
            'status' => $loanStatus, 'remarks' => $remarks]);
        $result['opening_loans']++;
        $result['_loan_journal_lines'][] = ['member_id' => $member->id, 'loan_id' => $loan->id, 'principal' => $principal, 'interest' => $interest];
    }

    private function postOpeningLoanJournal($batch, LoanProduct $product, ChartOfAccount $offset, int $userId, string $openingDate): void
    {
        $lines = DB::table('loans')->join('loan_applications', 'loans.loan_application_id', '=', 'loan_applications.id')
            ->where('loan_applications.application_number', 'like', 'PILOT-LOAN-' . $batch->id . '-%')
            ->select('loans.id', 'loans.member_id', 'loans.principal_balance', 'loans.interest_balance')->get();
        if ($lines->isEmpty()) return;
        $period = AccountingPeriod::whereDate('start_date', '<=', $openingDate)->whereDate('end_date', '>=', $openingDate)->where('status', 'open')->firstOrFail();
        $entry = JournalEntry::create(['accounting_period_id' => $period->id, 'entry_number' => app(JournalPostingService::class)->generateJournalEntryNumber(),
            'posting_key' => 'pilot_opening_loans:' . $batch->id, 'transaction_date' => $openingDate, 'posting_date' => $openingDate,
            'reference_number' => $batch->token, 'event_type' => 'pilot_opening_loans', 'source_type' => PilotImportBatch::class, 'source_id' => $batch->id,
            'description' => 'Pilot opening loan balances for import batch ' . $batch->token, 'status' => 'posted', 'created_by' => $userId, 'posted_by' => $userId, 'posted_at' => now()]);
        foreach ($lines as $loan) {
            if ((float) $loan->principal_balance > 0) JournalEntryLine::create(['journal_entry_id' => $entry->id, 'chart_of_account_id' => $product->loan_principal_account_id,
                'debit' => $loan->principal_balance, 'credit' => 0, 'description' => 'Opening loan principal', 'member_id' => $loan->member_id, 'loan_id' => $loan->id]);
            if ((float) $loan->interest_balance > 0) JournalEntryLine::create(['journal_entry_id' => $entry->id, 'chart_of_account_id' => $product->interest_receivable_account_id,
                'debit' => $loan->interest_balance, 'credit' => 0, 'description' => 'Opening interest receivable', 'member_id' => $loan->member_id, 'loan_id' => $loan->id]);
        }
        $debit = (float) JournalEntryLine::where('journal_entry_id', $entry->id)->sum('debit');
        if ($debit > 0) JournalEntryLine::create(['journal_entry_id' => $entry->id, 'chart_of_account_id' => $offset->id,
            'debit' => 0, 'credit' => $debit, 'description' => 'Opening balance equity offset']);
        $difference = JournalEntryLine::where('journal_entry_id', $entry->id)->sum(DB::raw('debit - credit'));
        if (round((float) $difference, 2) !== 0.0) throw ValidationException::withMessages(['ledger' => 'Opening loan balance journal did not balance.']);
    }

    private function nextMembershipNumber(): string
    {
        $year = now()->format('Y');
        $last = (int) Member::where('membership_number', 'like', 'MEM-' . $year . '-%')->lockForUpdate()->orderByDesc('id')->value(DB::raw('CAST(SUBSTRING(membership_number, -6) AS UNSIGNED)'));
        return sprintf('MEM-%s-%06d', $year, $last + 1);
    }

    private function parseDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
        if ($value === null || trim((string) $value) === '' || strtoupper(trim((string) $value)) === 'MISSING') return null;
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            $date = \DateTime::createFromFormat($format, trim((string) $value));
            if ($date && $date->format($format) === trim((string) $value)) return $date->format('Y-m-d');
        }
        return null;
    }

    private function nullableValue($value): ?string { $value = trim((string) $value); return $value === '' || strtoupper($value) === 'MISSING' ? null : $value; }
    private function productLabel($product): string { return $product ? $product->name . ' (' . $product->code . ')' : 'Not selected'; }
}
