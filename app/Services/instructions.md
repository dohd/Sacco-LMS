Below is a posting function from a POS system that creates journal entries. 

Review this code to understand its logic, then generate a similar, structurally adapted function inside the `JournalPostingService` class. 

### Target Context & Schema Rules:
1. Context: A SACCO (Savings and Credit Co-operative) Management System.
2. Architecture Change: Instead of a flat table like the POS example, use a master-detail structure:
   - `journal_entries` (Header table: stores transno, date, reference, notes, currency, trans_type)
   - `journal_entry_lines` (Lines table: stores account_id, account_type_id, debit, credit, member/contact info)
3. Schema Names to use: `chart_of_accounts`, `journal_entries`, and `journal_entry_lines`.
4. Domain Terminology: Replace POS terminology with SACCO terms (e.g., use 'member_id' instead of 'customer_id').

### POS Reference Function:
public function postPaymentReceipt($model)
{
	$transtype = TransactionType::where('type', 'payment-receipt')->first();
	$transno = JournalEntry::max('transno')+1;

	// debit Bank/Cash account
	$depositAccount = $model->depositAccount;
	if (!$depositAccount) trigger_error('Deposit account required');
	$paymentEntry = JournalEntry::create([
		'transno' => $transno,
		'date' => $model->date,
		'trans_type' => $transtype->type,
		'trans_type_id' => $transtype->id,
		'account_id' => $depositAccount->id,
		'account_type_id' => $depositAccount->account_type_id,
		'debit' => $model->payments_amount,
		'contact' => 'customer',
		'customer_id' => $model->customer_id,
		'reference' => $model->reference,
		'notes' => $model->notes,
		'currency_id' => $model->currency_id,
		'currency_rate' => $model->currency_rate ?? 1,
	]);
	$paymentEntry->entriesPivot()->create(['receivepayment_id' => $model->id]);

	// credit A/R account
	$receivableAccount = ChartOfAccount::doesntHave('subAccounts')
		->whereNull('is_disabled')
		->whereHas('accountType', fn($q) => $q->where('subtype', 'receivable'))
		->first();
	$receivableEntry = $paymentEntry->replicate();
	$receivableEntry->fill([
		'debit' => 0,
		'credit' => $model->payments_amount,
		'account_id' => $receivableAccount->id,
		'account_type_id' => $receivableAccount->account_type_id,
		'related_id' => $receivableEntry->id,
	]);
	$receivableEntry->save();
	$receivableEntry->entriesPivot()->create(['receivepayment_id' => $model->id]);

	$diff = JournalEntry::where('transno', $transno)->sum(DB::raw('debit-credit'));
	if (bccomp($diff, 0, 2) != 0) return $this->accountingError();
}
