# Phase 2 — savings workflow audit

Executed 2026-10-15 using PHP 7.4.33, PHPUnit 9.6.19 and isolated MySQL/MariaDB databases.

**Latest result after audit-field and product-retirement fixes: 12 tests, 653 assertions, all passing.** Phase 1 regression also passes: 2 tests, 100 assertions. Generic withdrawal creation/confirmation is blocked; withdrawal approval and generic debit confirmation enforce the minimum remaining available balance under the existing account lock. All generic debits (including fees and adjustments) currently obey this minimum; no exception policy was introduced.

Withdrawal approval now stamps the linked transaction's `confirmed_by` and `confirmed_at`, using the same timestamp as withdrawal approval. Payment preserves these fields. No historical audit data was backfilled.

Posting reloads and locks the product. Withdrawals and transfers out recheck `allows_withdrawals` and wait the configured number of full days from the server-recorded `created_at`, rather than the editable requested/transaction date. Administrative fees are not treated as member withdrawals. Product retirement (`is_active = false`) closes new enrollment, not access to funds in existing active accounts. Already-approved payments can complete after product restrictions change, because their debits have already been posted.

MVP fixed-deposit guard: new accounts and switching accounts to fixed-deposit products are rejected. Withdrawal/transfer posting for existing fixed-deposit products is rejected pending a maturity workflow. This does not implement maturity, penalties or rollover. Account opening, migration-to-fixed-deposit rejection, and fixed-deposit withdrawal rejection are covered by regression tests.

Regression checks cover exactly KES 1,000 remaining, a one-cent breach, transfer debits, held funds, unchanged pending records/balances on rejection, audit metadata, product changes, notice-period boundaries, backdated requests and approved payment completion. The original findings below are historical; confirmation audit fields and the described MVP product restrictions are now addressed. Other checklist items remain separate work.

## Run

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/SavingsPhaseTwoAuditTest.php --colors=never
```

The test copies roles read-only from the current database, as requested for phase 1. It creates a random `sacco_audit_<hex>` database per test, runs actual migrations, and wraps test records in a transaction. Teardown rolls back and drops only the generated database, even after assertions fail. The configured database needs CREATE/DROP DATABASE privileges. Forced process termination can leave a generated database behind. Current users, accounts and financial records are not copied or modified.

Member/application and three distinct GL accounts are prerequisite fixtures. Product, savings account, deposit, withdrawal and account-freeze mutations use the actual HTTP routes. Only the iframe middleware is excluded because it writes PHP headers directly under CLI. This is a staff-assisted workflow audit, not an authorization or external-payment-provider certification.

## Original audit results (before fixes)

| Component/control | Result |
| --- | --- |
| savings_products | Creates a voluntary product with KES 1,000 minimum and KES 20,000 maximum; duplicate product rejected. |
| savings_accounts | Opens one active account for the member/product with zero balances; duplicate account rejected. |
| savings_transactions | KES 10,000 deposit stays pending without changing balances; confirmation credits ledger/available balances once. Repeat confirmation rejected. |
| savings_withdrawals | KES 3,000 request creates a linked pending debit, leaving KES 10,000 unchanged. Approval confirms the debit and reduces balances to KES 7,000. Payment changes status to paid and records actor/time/reference without another debit. |
| Invalid transitions | Payment before approval, repeated approval, payment without reference, and repeated payment rejected. |
| Reads | Index and detail pages for all four resources return 200 after the normal lifecycle. |
| Insufficient funds/rejection | KES 11,000 request against KES 10,000 fails without creating orphan financial records. Rejection leaves balances unchanged and makes the pending debit unconfirmable. |
| Maximum balance | KES 21,000 deposit cannot confirm against a KES 20,000 limit; transaction remains pending and balances stay zero. |
| Frozen account | HTTP account update freezes the account; subsequent deposit and withdrawal requests fail without new records or balance changes. |
| Approval bypass | **FAIL:** confirming the linked debit through `savings_transactions.confirm` reduces KES 10,000 to KES 7,000 while the withdrawal remains pending. |
| Minimum balance | **FAIL:** approving KES 9,500 against KES 10,000 leaves KES 500 despite a KES 1,000 product minimum. |

The minimum-balance acceptance test interprets `minimum_balance` as the balance that must remain after withdrawal. If the business instead intends an opening-balance threshold, clarify and rename that policy before changing the test.

## Fixes ordered by complexity

- [ ] **Small · P1 — Remove the duplicate payment route.** `routes/web.php` declares `savings_withdrawals.pay` twice. Keep one route and verify route generation.
- [ ] **Small · P1 — Complete confirmation audit fields.** `SavingsWithdrawalsController::approve` sets the transaction to confirmed without its `confirmed_by`/`confirmed_at`; the generic transaction confirmer sets both. Populate these consistently. Code-review finding; normal lifecycle currently checks withdrawal approval metadata.
- [ ] **Small/medium · P0 — Block direct confirmation of withdrawal debits.** `SavingsTransactionsController::confirm` accepts the linked pending withdrawal transaction without checking the withdrawal approval workflow. Reject this path and route withdrawals exclusively through approval. Also review `store`, which accepts standalone `withdrawal` transactions. Runtime-proven bypass: HTTP 302, ledger KES 7,000, withdrawal pending, transaction confirmed. Acceptance: HTTP 422 and unchanged balances/statuses for the bypass test.
- [ ] **Medium · P1 — Enforce minimum remaining balance consistently.** `SavingsWithdrawalsController::approve` checks available funds but not the product minimum; generic debit confirmation needs the same policy. Enforce under the account lock, including held balances and any product-specific exceptions. Runtime-proven breach: KES 500 remains after approval. Acceptance: reject the request or approval without financial mutation and cover exact-boundary withdrawals.
- [ ] **Medium · P1 — Validate reference data and optional product input.** `SavingsProductsController::create` supplies an empty GL account collection, and store validates account IDs as integers without existence checks. Store also accesses optional validated keys directly. Supply valid account options, validate referenced GL accounts and default omitted optional fields. Code-review findings; audit supplies complete inputs and real GL rows.
- [ ] **Medium · P1 — Revalidate product restrictions at posting time.** Recheck active product, withdrawal eligibility and applicable notice/fixed-deposit rules when money is posted, rather than relying only on initial request validation. Include changed-product and concurrent-request tests. Code-review recommendation; fixed deposits and notices are not certified here.
- [ ] **Large · P1 — Share atomic posting rules and extend reconciliation coverage.** Withdrawal approval and generic transaction confirmation duplicate balance logic, allowing policy divergence. Centralize posting/validation while retaining explicit workflow permissions and audit records. Extend tests for holds, reversals, transfers, interest, fee rounding, concurrent requests and balanced GL postings.

The initial audit did not change production code. The subsequent fixes modify SavingsTransactionsController and SavingsWithdrawalsController. No general edit/delete CRUD, real bank/mobile settlement, permissions matrix, interest accrual, fixed-deposit maturity, concurrency or load-test claim is made.
