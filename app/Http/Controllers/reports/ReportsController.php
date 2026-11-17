<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\employee\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function employeeByDesignation()
    {
        $designations = Employee::selectRaw('job_desig, COUNT(*) as count')
            ->whereNotNull('job_desig')
            ->groupBy('job_desig')
            ->get();

        return view('reports.employee_by_designation', compact('designations'));
    }

    public function employeeBySkillLevel()
    {
        $skillLevels = Employee::selectRaw('education_peak, COUNT(*) as count')
            ->whereNotNull('education_peak')
            ->groupBy('education_peak')
            ->get();

        return view('reports.employee_by_skill_level', compact('skillLevels'));
    }

    public function employeeByWorkCounty()
    {
        $workCounties = Employee::selectRaw('work_county, COUNT(*) as count')
            ->whereNotNull('work_county')
            ->groupBy('work_county')
            ->get();

        return view('reports.employee_by_work_county', compact('workCounties'));
    }

    public function trialBalance(Request $request)
    {
        $this->authorizeReports();
        $asOf = $this->asOf($request);
        $balances = $this->accountBalances($asOf);
        $rows = [];
        $debits = $credits = 0.0;
        foreach ($balances as $account) {
            $net = (float) $account->debit_total - (float) $account->credit_total;
            $debit = max($net, 0);
            $credit = max(-$net, 0);
            $debits += $debit;
            $credits += $credit;
            $rows[] = [
                'code' => $account->code,
                'account' => $account->name,
                'type' => ucfirst($account->account_type),
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return $this->report(
            'Trial Balance',
            'Posted journal balances through the selected date. Debits and credits should agree.',
            [
                'code' => 'Code',
                'account' => 'Account',
                'type' => 'Type',
                'debit' => 'Debit',
                'credit' => 'Credit',
            ],
            $rows,
            [
                ['label' => 'Total debits', 'value' => $debits],
                ['label' => 'Total credits', 'value' => $credits],
            ],
            [['name' => 'as_of', 'label' => 'As at', 'type' => 'date', 'value' => $asOf]]
        );
    }

    public function dailyTransactionLog(Request $request)
    {
        $this->authorizeReports();
        $date = $this->singleDate($request, 'date');
        $methods = ['cash', 'mobile_money', 'bank_transfer', 'cheque', 'check_off', 'standing_order', 'post_dated_cheque'];
        $rows = [];
        $savings = DB::table('savings_transactions as t')->join('savings_accounts as a', 'a.id', '=', 't.savings_account_id')
            ->join('members as m', 'm.id', '=', 'a.member_id')->join('savings_products as p', 'p.id', '=', 'a.savings_product_id')
            ->whereDate('t.transaction_date', $date)->where('t.status', 'confirmed')->whereIn('t.payment_method', $methods)
            ->select('t.transaction_date as date', 'm.membership_number', 'm.first_name', 'm.last_name', 'p.name as account', 't.transaction_type as kind', 't.transaction_number as reference', 't.external_reference as external', 't.payment_method as method', 't.direction', 't.amount', 't.status')->get();
        foreach ($savings as $row) $rows[] = $this->dailyRow($row, 'Savings');

        $shares = DB::table('share_transactions as t')->join('share_accounts as a', 'a.id', '=', 't.share_account_id')
            ->join('members as m', 'm.id', '=', 'a.member_id')->join('share_products as p', 'p.id', '=', 'a.share_product_id')
            ->whereDate('t.transaction_date', $date)->where('t.status', 'confirmed')->whereIn('t.payment_method', $methods)
            ->select('t.transaction_date as date', 'm.membership_number', 'm.first_name', 'm.last_name', 'p.name as account', 't.transaction_type as kind', 't.transaction_number as reference', 't.payment_reference as external', 't.payment_method as method', 't.direction', 't.amount', 't.status')->get();
        foreach ($shares as $row) $rows[] = $this->dailyRow($row, 'Shares');

        $repayments = DB::table('loan_repayments as t')->join('members as m', 'm.id', '=', 't.member_id')
            ->whereDate('t.payment_date', $date)->where('t.status', 'confirmed')->whereIn('t.payment_method', $methods)
            ->select('t.payment_date as date', 'm.membership_number', 'm.first_name', 'm.last_name', DB::raw("'Loan repayment' as account"), DB::raw("'repayment' as kind"), 't.repayment_number as reference', 't.transaction_reference as external', 't.payment_method as method', DB::raw("'in' as direction"), 't.amount_paid as amount', 't.status')->get();
        foreach ($repayments as $row) $rows[] = $this->dailyRow($row, 'Loans');

        $disbursements = DB::table('loan_disbursements as t')->join('loans as l', 'l.id', '=', 't.loan_id')->join('members as m', 'm.id', '=', 'l.member_id')
            ->whereDate('t.disbursement_date', $date)->where('t.status', 'processed')->whereIn('t.disbursement_method', $methods)
            ->select('t.disbursement_date as date', 'm.membership_number', 'm.first_name', 'm.last_name', DB::raw("'Loan disbursement' as account"), DB::raw("'disbursement' as kind"), 't.disbursement_number as reference', 't.transaction_reference as external', 't.disbursement_method as method', DB::raw("'out' as direction"), 't.net_amount as amount', 't.status')->get();
        foreach ($disbursements as $row) $rows[] = $this->dailyRow($row, 'Loans');
        usort($rows, function ($a, $b) { return [$a['date'], $a['reference']] <=> [$b['date'], $b['reference']]; });

        $inflow = array_sum(array_column(array_filter($rows, function ($r) { return $r['direction'] === 'In'; }), 'amount'));
        $outflow = array_sum(array_column(array_filter($rows, function ($r) { return $r['direction'] === 'Out'; }), 'amount'));
        return $this->report('Daily Transaction Log', 'Confirmed cash, mobile money, bank, cheque, and check-off movements, including processed loan disbursements.',
            ['date' => 'Date', 'member' => 'Member', 'source' => 'Area', 'account' => 'Product / activity', 'kind' => 'Transaction', 'reference' => 'Reference', 'external' => 'External reference', 'method' => 'Method', 'direction' => 'Flow', 'amount' => 'Amount', 'status' => 'Status'],
            $rows, [['label' => 'Money in', 'value' => $inflow], ['label' => 'Money out', 'value' => $outflow], ['label' => 'Transactions', 'value' => count($rows)]],
            [['name' => 'date', 'label' => 'Date', 'type' => 'date', 'value' => $date]]);
    }

    public function balanceSheet(Request $request)
    {
        $this->authorizeReports();
        $asOf = $this->asOf($request);
        $balances = $this->accountBalances($asOf);
        $rows = [];
        $assets = $liabilities = $equity = $income = $expenses = 0.0;
        foreach ($balances as $account) {
            $netDebit = (float) $account->debit_total - (float) $account->credit_total;
            if ($account->account_type === 'asset') { $amount = $netDebit; $assets += $amount; }
            elseif ($account->account_type === 'liability') { $amount = -$netDebit; $liabilities += $amount; }
            elseif ($account->account_type === 'equity') { $amount = -$netDebit; $equity += $amount; }
            elseif ($account->account_type === 'income') { $income += -$netDebit; continue; }
            else { $expenses += $netDebit; continue; }
            if (abs($amount) > 0.004) $rows[] = ['section' => ucfirst($account->account_type), 'code' => $account->code, 'account' => $account->name, 'amount' => $amount];
        }
        $surplus = $income - $expenses;
        $rows[] = ['section' => 'Equity', 'code' => '', 'account' => 'Current surplus / (deficit)', 'amount' => $surplus];
        return $this->report('Balance Sheet', 'Basic statement of assets, liabilities, and equity. Current surplus includes posted income less expenses through the selected date.',
            ['section' => 'Section', 'code' => 'Code', 'account' => 'Account', 'amount' => 'Amount'], $rows,
            [['label' => 'Total assets', 'value' => $assets], ['label' => 'Liabilities and equity', 'value' => $liabilities + $equity + $surplus], ['label' => 'Current surplus / (deficit)', 'value' => $surplus]],
            [['name' => 'as_of', 'label' => 'As at', 'type' => 'date', 'value' => $asOf]]);
    }

    public function memberStatement(Request $request)
    {
        $this->authorizeReports();
        $currentMember = auth()->user()->hasRoleCode('member')
            ? \App\Models\Memberships\Member::where('user_id', auth()->id())->firstOrFail()
            : null;
        if ($currentMember) {
            $request->merge(['member_id' => $currentMember->id]);
        }
        $request->validate(['member_id' => 'nullable|integer|exists:members,id']);
        [$from, $to] = $this->dateRange($request);
        $memberOptions = DB::table('members')->when($currentMember, fn ($query) => $query->where('id', $currentMember->id))
            ->orderBy('membership_number')->get()->mapWithKeys(function ($m) { return [$m->id => $m->membership_number . ' — ' . $m->first_name . ' ' . $m->last_name]; })->all();
        if (!$request->filled('member_id')) {
            return $this->report('Member Statement', 'Choose a member to view their savings, shares, loan releases, and repayment activity.',
                ['date' => 'Date', 'area' => 'Area', 'account' => 'Account / product', 'type' => 'Activity', 'reference' => 'Reference', 'direction' => 'Direction', 'amount' => 'Amount', 'status' => 'Status'], [], [],
                [['name' => 'member_id', 'label' => 'Member', 'type' => 'select', 'value' => '', 'options' => $memberOptions, 'locked' => (bool) $currentMember],
                    ['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $from], ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $to]]);
        }
        $member = DB::table('members')->where('id', $request->input('member_id'))->first();
        $rows = [];

        $savings = DB::table('savings_transactions as t')->join('savings_accounts as a', 'a.id', '=', 't.savings_account_id')->join('savings_products as p', 'p.id', '=', 'a.savings_product_id')
            ->where('a.member_id', $member->id)->whereBetween('t.transaction_date', [$from, $to])->whereIn('t.status', ['confirmed', 'reversed'])
            ->select('t.transaction_date as date', 'p.name as account', 't.transaction_type as type', 't.transaction_number as reference', 't.direction', 't.amount', 't.status')->get();
        foreach ($savings as $row) $rows[] = $this->statementRow($row, 'Savings');

        $shares = DB::table('share_transactions as t')->join('share_accounts as a', 'a.id', '=', 't.share_account_id')->join('share_products as p', 'p.id', '=', 'a.share_product_id')
            ->where('a.member_id', $member->id)->whereBetween('t.transaction_date', [$from, $to])->whereIn('t.status', ['confirmed', 'reversed'])
            ->select('t.transaction_date as date', 'p.name as account', 't.transaction_type as type', 't.transaction_number as reference', 't.direction', 't.amount', 't.status')->get();
        foreach ($shares as $row) $rows[] = $this->statementRow($row, 'Shares');

        $repayments = DB::table('loan_repayments as t')->join('loans as l', 'l.id', '=', 't.loan_id')
            ->where('t.member_id', $member->id)->whereBetween('t.payment_date', [$from, $to])->whereIn('t.status', ['confirmed', 'reversed'])
            ->select('t.payment_date as date', DB::raw("CONCAT('Loan ', l.loan_number) as account"), DB::raw("'repayment' as type"), 't.repayment_number as reference', DB::raw("'debit' as direction"), 't.amount_paid as amount', 't.status')->get();
        foreach ($repayments as $row) $rows[] = $this->statementRow($row, 'Loans');

        $disbursements = DB::table('loan_disbursements as d')->join('loans as l', 'l.id', '=', 'd.loan_id')
            ->where('l.member_id', $member->id)->whereBetween('d.disbursement_date', [$from, $to])->whereIn('d.status', ['processed', 'reversed'])
            ->select('d.disbursement_date as date', DB::raw("CONCAT('Loan ', l.loan_number) as account"), DB::raw("'disbursement' as type"), 'd.disbursement_number as reference', DB::raw("'credit' as direction"), 'd.net_amount as amount', 'd.status')->get();
        foreach ($disbursements as $row) $rows[] = $this->statementRow($row, 'Loans');
        usort($rows, function ($a, $b) { return [$a['date'], $a['reference']] <=> [$b['date'], $b['reference']]; });

        return $this->report('Member Statement — ' . $member->membership_number . ' · ' . trim($member->first_name . ' ' . $member->last_name), 'Subledger activity for the selected period. Savings and share flows are member-account directions; loan payments and releases are shown separately.',
            ['date' => 'Date', 'area' => 'Area', 'account' => 'Account / product', 'type' => 'Activity', 'reference' => 'Reference', 'direction' => 'Direction', 'amount' => 'Amount', 'status' => 'Status'], $rows,
            [['label' => 'Current savings', 'value' => (float) DB::table('savings_accounts')->where('member_id', $member->id)->sum('ledger_balance')],
                ['label' => 'Current shares', 'value' => (float) DB::table('share_accounts')->where('member_id', $member->id)->sum('share_balance')],
                ['label' => 'Current loan outstanding', 'value' => (float) DB::table('loans')->where('member_id', $member->id)->sum('total_outstanding_balance')]],
            [['name' => 'member_id', 'label' => 'Member', 'type' => 'select', 'value' => $member->id, 'options' => $memberOptions, 'locked' => (bool) $currentMember],
                ['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $from], ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $to]]);
    }

    public function memberListing(Request $request)
    {
        $this->authorizeReports();
        $request->validate(['status' => 'nullable|in:active,dormant,suspended,withdrawn,deceased,closed']);
        $query = DB::table('members as m')->leftJoin('member_applications as a', 'a.id', '=', 'm.member_application_id')
            ->select('m.membership_number', 'm.import_membership_number', 'm.first_name', 'm.middle_name', 'm.last_name', 'm.phone', 'm.email', 'm.admission_date', 'm.status', 'm.kyc_status');
        if ($request->filled('status')) $query->where('m.status', $request->input('status'));
        $rows = $query->orderBy('m.membership_number')->get()->map(function ($m) {
            return ['number' => $m->membership_number, 'import_number' => $m->import_membership_number ?: '—', 'name' => trim(implode(' ', array_filter([$m->first_name, $m->middle_name, $m->last_name]))), 'phone' => $m->phone, 'email' => $m->email,
                'admission' => $m->admission_date ?: '—', 'status' => ucfirst($m->status), 'kyc' => ucfirst($m->kyc_status ?: 'complete')];
        })->all();
        return $this->report('Member Listing', 'Membership register by current status, including KYC review state.',
            ['number' => 'Membership number', 'import_number' => 'Import membership number', 'name' => 'Member', 'phone' => 'Phone', 'email' => 'Email', 'admission' => 'Admission date', 'status' => 'Member status', 'kyc' => 'KYC status'], $rows,
            [['label' => 'Members', 'value' => count($rows)]],
            [['name' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => $request->input('status'), 'options' => ['' => 'All statuses', 'active' => 'Active', 'dormant' => 'Dormant', 'suspended' => 'Suspended', 'withdrawn' => 'Withdrawn', 'deceased' => 'Deceased', 'closed' => 'Closed']]]);
    }

    public function savingsSummary()
    {
        $this->authorizeReports();
        $rows = [];
        $savings = DB::table('savings_accounts as a')->join('savings_products as p', 'p.id', '=', 'a.savings_product_id')
            ->select('p.name as product', DB::raw("'Savings' as type"), DB::raw('COUNT(a.id) as accounts'), DB::raw('SUM(a.ledger_balance) as balance'))
            ->where('a.status', '!=', 'closed')->groupBy('p.id', 'p.name')->get();
        foreach ($savings as $row) $rows[] = ['type' => $row->type, 'product' => $row->product, 'accounts' => (int) $row->accounts, 'balance' => (float) $row->balance];
        $shares = DB::table('share_accounts as a')->join('share_products as p', 'p.id', '=', 'a.share_product_id')
            ->select('p.name as product', DB::raw("'Shares' as type"), DB::raw('COUNT(a.id) as accounts'), DB::raw('SUM(a.share_balance) as balance'))
            ->where('a.status', '!=', 'closed')->groupBy('p.id', 'p.name')->get();
        foreach ($shares as $row) $rows[] = ['type' => $row->type, 'product' => $row->product, 'accounts' => (int) $row->accounts, 'balance' => (float) $row->balance];
        usort($rows, function ($a, $b) { return [$a['type'], $a['product']] <=> [$b['type'], $b['product']]; });
        return $this->report('Product-Wise Savings Summary', 'Current active and frozen member savings and share balances grouped by product.',
            ['type' => 'Account type', 'product' => 'Product', 'accounts' => 'Open accounts', 'balance' => 'Total balance'], $rows,
            [['label' => 'Total balances', 'value' => array_sum(array_column($rows, 'balance'))], ['label' => 'Accounts', 'value' => array_sum(array_column($rows, 'accounts'))]]);
    }

    public function loanDisbursals(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $rows = DB::table('loan_disbursements as d')->join('loans as l', 'l.id', '=', 'd.loan_id')->join('members as m', 'm.id', '=', 'l.member_id')
            ->whereBetween('d.disbursement_date', [$from, $to])->where('d.status', 'processed')
            ->select('d.disbursement_date', 'd.disbursement_number', 'm.membership_number', 'm.first_name', 'm.last_name', 'l.loan_number', 'd.gross_amount', 'd.deductions_amount', 'd.net_amount', 'd.disbursement_method', 'd.transaction_reference')
            ->orderBy('d.disbursement_date')->orderBy('d.disbursement_number')->get()->map(function ($d) {
                return ['date' => $d->disbursement_date, 'number' => $d->disbursement_number, 'member' => $d->membership_number . ' — ' . trim($d->first_name . ' ' . $d->last_name), 'loan' => $d->loan_number,
                    'gross' => (float) $d->gross_amount, 'deductions' => (float) $d->deductions_amount, 'net' => (float) $d->net_amount, 'method' => ucfirst(str_replace('_', ' ', $d->disbursement_method)), 'reference' => $d->transaction_reference];
            })->all();
        return $this->report('Loan Disbursal Report', 'Processed loan releases in the selected period. Reversed and incomplete disbursements are excluded.',
            ['date' => 'Date', 'number' => 'Disbursement', 'member' => 'Member', 'loan' => 'Loan', 'gross' => 'Gross', 'deductions' => 'Deductions', 'net' => 'Net paid', 'method' => 'Method', 'reference' => 'External reference'], $rows,
            [['label' => 'Gross disbursed', 'value' => array_sum(array_column($rows, 'gross'))], ['label' => 'Net paid', 'value' => array_sum(array_column($rows, 'net'))], ['label' => 'Disbursements', 'value' => count($rows)]],
            [['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $from], ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $to]]);
    }

    public function loanAging(Request $request)
    {
        $this->authorizeReports();
        $asOf = $this->asOf($request);
        $overdue = DB::table('loan_repayment_schedules')->where('outstanding_amount', '>', 0)->whereDate('due_date', '<', $asOf)
            ->select('loan_id', DB::raw('MIN(due_date) as oldest_due'), DB::raw('SUM(outstanding_amount) as overdue_amount'))->groupBy('loan_id')->get()->keyBy('loan_id');
        $loans = DB::table('loans as l')->join('members as m', 'm.id', '=', 'l.member_id')->whereIn('l.id', $overdue->keys())
            ->whereIn('l.status', ['active', 'in_arrears', 'restructured'])
            ->select('l.id', 'l.loan_number', 'l.status', 'l.total_outstanding_balance', 'm.membership_number', 'm.first_name', 'm.last_name')->get();
        $buckets = ['1–30 days' => ['count' => 0, 'balance' => 0.0, 'overdue' => 0.0], '31–60 days' => ['count' => 0, 'balance' => 0.0, 'overdue' => 0.0], '61–90 days' => ['count' => 0, 'balance' => 0.0, 'overdue' => 0.0], '90+ days' => ['count' => 0, 'balance' => 0.0, 'overdue' => 0.0]];
        $rows = [];
        foreach ($loans as $loan) {
            $due = $overdue[$loan->id];
            $days = Carbon::parse($due->oldest_due)->diffInDays(Carbon::parse($asOf));
            $bucket = $days <= 30 ? '1–30 days' : ($days <= 60 ? '31–60 days' : ($days <= 90 ? '61–90 days' : '90+ days'));
            $balance = (float) $loan->total_outstanding_balance;
            $amountOverdue = (float) $due->overdue_amount;
            $buckets[$bucket]['count']++;
            $buckets[$bucket]['balance'] += $balance;
            $buckets[$bucket]['overdue'] += $amountOverdue;
            $rows[] = ['member' => $loan->membership_number . ' — ' . trim($loan->first_name . ' ' . $loan->last_name), 'loan' => $loan->loan_number, 'status' => ucfirst(str_replace('_', ' ', $loan->status)),
                'oldest_due' => $due->oldest_due, 'days' => $days, 'bucket' => $bucket, 'overdue' => $amountOverdue, 'exposure' => $balance];
        }
        $portfolio = (float) DB::table('loans')->whereIn('status', ['active', 'in_arrears', 'restructured'])->sum('total_outstanding_balance');
        $atRisk = array_sum(array_column($buckets, 'balance'));
        return $this->report('Loan Aging & Portfolio at Risk', 'A loan is assigned to the bucket of its oldest unpaid installment. PAR exposure uses the full outstanding loan balance; overdue installment amounts are shown separately.',
            ['member' => 'Member', 'loan' => 'Loan', 'status' => 'Status', 'oldest_due' => 'Oldest unpaid due', 'days' => 'Days overdue', 'bucket' => 'Aging bucket', 'overdue' => 'Overdue installments', 'exposure' => 'Loan exposure'], $rows,
            [['label' => 'Portfolio at risk', 'value' => $atRisk], ['label' => 'Active portfolio', 'value' => $portfolio], ['label' => 'PAR', 'value' => $portfolio > 0 ? ($atRisk / $portfolio * 100) : 0, 'suffix' => '%']],
            [['name' => 'as_of', 'label' => 'As at', 'type' => 'date', 'value' => $asOf]], $buckets);
    }

    public function repaymentScheduleVsActual(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $rows = DB::table('loan_repayment_schedules as s')->join('loans as l', 'l.id', '=', 's.loan_id')->join('members as m', 'm.id', '=', 's.member_id')
            ->whereBetween('s.due_date', [$from, $to])->where('s.status', '!=', 'waived')
            ->select('s.due_date', 'm.membership_number', 'm.first_name', 'm.last_name', 'l.loan_number', 's.installment_number', 's.total_due', 's.total_paid', 's.outstanding_amount', 's.status')
            ->orderBy('s.due_date')->orderBy('l.loan_number')->orderBy('s.installment_number')->get()->map(function ($s) {
                return ['due' => $s->due_date, 'member' => $s->membership_number . ' — ' . trim($s->first_name . ' ' . $s->last_name), 'loan' => $s->loan_number, 'installment' => $s->installment_number,
                    'scheduled' => (float) $s->total_due, 'actual' => (float) $s->total_paid, 'variance' => (float) $s->total_due - (float) $s->total_paid, 'outstanding' => (float) $s->outstanding_amount, 'status' => ucfirst($s->status)];
            })->all();
        return $this->report('Repayment Schedule vs. Actual', 'Scheduled installments by due date compared with amounts allocated to those schedules.',
            ['due' => 'Due date', 'member' => 'Member', 'loan' => 'Loan', 'installment' => 'Installment', 'scheduled' => 'Scheduled', 'actual' => 'Actual paid', 'variance' => 'Unpaid variance', 'outstanding' => 'Outstanding', 'status' => 'Status'], $rows,
            [['label' => 'Scheduled', 'value' => array_sum(array_column($rows, 'scheduled'))], ['label' => 'Actual allocated', 'value' => array_sum(array_column($rows, 'actual'))], ['label' => 'Unpaid variance', 'value' => array_sum(array_column($rows, 'variance'))]],
            [['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $from], ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $to]]);
    }

    private function authorizeReports(): void
    {
        abort_unless(userCan('view-reports'), 403);
    }

    private function accountBalances(string $asOf)
    {
        return DB::table('journal_entry_lines as l')->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('chart_of_accounts as c', 'c.id', '=', 'l.chart_of_account_id')->where('j.status', 'posted')->whereDate('j.transaction_date', '<=', $asOf)
            ->select('c.id', 'c.code', 'c.name', 'c.account_type', DB::raw('SUM(l.debit) as debit_total'), DB::raw('SUM(l.credit) as credit_total'))
            ->groupBy('c.id', 'c.code', 'c.name', 'c.account_type')->orderBy('c.code')->get();
    }

    private function asOf(Request $request): string
    {
        $request->validate(['as_of' => 'nullable|date_format:Y-m-d']);
        return $request->input('as_of') ?: now()->toDateString();
    }

    private function singleDate(Request $request, string $key): string
    {
        $request->validate([$key => 'nullable|date_format:Y-m-d']);
        return $request->input($key) ?: now()->toDateString();
    }

    private function dateRange(Request $request): array
    {
        $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d']);
        $from = $request->input('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->input('to') ?: now()->toDateString();
        if ($from > $to) abort(422, 'The start date must be on or before the end date.');
        return [$from, $to];
    }

    private function dailyRow($row, string $area): array
    {
        return ['date' => $row->date, 'member' => $row->membership_number . ' — ' . trim($row->first_name . ' ' . $row->last_name), 'source' => $area, 'account' => $row->account,
            'kind' => ucfirst(str_replace('_', ' ', $row->kind)), 'reference' => $row->reference, 'external' => $row->external ?: '—', 'method' => ucfirst(str_replace('_', ' ', $row->method)),
            'direction' => in_array($row->direction, ['credit', 'in'], true) ? 'In' : 'Out', 'amount' => (float) $row->amount, 'status' => ucfirst($row->status)];
    }

    private function statementRow($row, string $area): array
    {
        return ['date' => $row->date, 'area' => $area, 'account' => $row->account, 'type' => ucfirst(str_replace('_', ' ', $row->type)), 'reference' => $row->reference,
            'direction' => ucfirst($row->direction), 'amount' => (float) $row->amount, 'status' => ucfirst($row->status)];
    }

    private function report(string $title, string $description, array $columns, array $rows, array $summary = [], array $filters = [], array $groups = [])
    {
        return view('reports.report', compact('title', 'description', 'columns', 'rows', 'summary', 'filters', 'groups'));
    }
}
