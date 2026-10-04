<?php

namespace App\Http\Controllers\ShareAccounts;

use App\Http\Controllers\Controller;
use App\Models\Memberships\Member;
use App\Models\Shares\ShareAccount;
use App\Models\Shares\ShareProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShareAccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shareAccounts = ShareAccount::latest()->get();

        return view('share_accounts.index', compact('shareAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->session()->forget(['_old_input', 'errors']);

        $members = Member::all();
        $shareProducts = ShareProduct::all();

        return view('share_accounts.create', compact('members', 'shareProducts')); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
                Rule::unique('share_accounts')->where(fn ($q) => $q->where('share_product_id', $request->share_product_id)),
            ],
            'share_product_id' => ['required', 'integer', 'exists:share_products,id'],
            'opened_date' => ['required', 'date'],
            'status' => ['required', 'in:active,frozen,closed'],
        ]);

        try {
            $account = DB::transaction(function () use ($validated) {
                $product = ShareProduct::findOrFail($validated['share_product_id']);

                if (!$product->is_active) {
                    throw ValidationException::withMessages([
                        'share_product_id' => 'The selected share product is inactive.'
                    ]);
                }

                return ShareAccount::create([
                    'member_id' => $validated['member_id'],
                    'share_product_id' => $validated['share_product_id'],
                    'account_number' => $this->generateShareAccountNumber(),
                    'total_units' => 0,
                    'share_balance' => 0,
                    'held_amount' => 0,
                    'available_amount' => 0,
                    'opened_date' => $validated['opened_date'],
                    'status' => $validated['status'],
                ]);
            });

            return redirect()->route('share_accounts.show', $account->id)
                ->with('success', 'Share account opened successfully.');
        } catch (\Exception $e) {
            return errorHandler('Error opening share account. Please try again.', $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ShareAccount $shareAccount)
    {
        return view('share_accounts.view', compact('shareAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ShareAccount $shareAccount)
    {
        // Inject the key-value pair into the request payload
        $payload = $shareAccount->toArray();
        request()->merge($payload);

        // Flash the modified request to the old input session store
        request()->flash();

        $members = Member::all();
        $shareProducts = ShareProduct::all();

        return view('share_accounts.edit', compact('shareAccount', 'members', 'shareProducts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShareAccount $shareAccount)
    {
        $validated = $request->validate([
            'opened_date' => ['required', 'date'],
            'status' => ['required', 'in:active,frozen,closed'],
        ]);

        try {
            DB::transaction(function () use ($shareAccount, $validated) {
                if ($shareAccount->status === 'closed' && $validated['status'] !== 'closed') {
                    throw ValidationException::withMessages([
                        'status' => 'A closed share account cannot be reactivated.'
                    ]);
                }

                $shareAccount->update([
                    'opened_date' => $validated['opened_date'],
                    'status' => $validated['status'],
                ]);
            });

            return redirect()->route('share_accounts.show', $shareAccount->id)
                ->with('success', 'Share account updated successfully.');
        } catch (\Exception $e) {
            return errorHandler('Error updating share account. Please try again.', $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function close(Request $request, $id)
    {
        $validated = $request->validate([
            'closure_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $shareAccount = ShareAccount::find($id);

            DB::transaction(function () use ($shareAccount, $validated) {
                $account = ShareAccount::whereKey($shareAccount->id)->lockForUpdate()->first();

                if ($account->status === 'closed') {
                    throw ValidationException::withMessages([
                        'account' => 'This share account is already closed.'
                    ]);
                }

                if ($account->held_amount > 0) {
                    throw ValidationException::withMessages([
                        'account' => 'The account cannot be closed while it has held funds.'
                    ]);
                }

                if ($account->available_amount > 0) {
                    throw ValidationException::withMessages([
                        'account' => 'The account cannot be closed while it has an available share balance.'
                    ]);
                }

                $account->update([
                    'status' => 'closed',
                    'closed_date' => now()->toDateString(),
                    'closed_by' => Auth::id(),
                    'closure_reason' => $validated['closure_reason'],
                ]);
            });

            return redirect()->route('share_accounts.show', $shareAccount->id)
                ->with('success', 'Share account closed successfully.');
        } catch (\Exception $e) {
            return errorHandler('Error closing share account. Please try again.', $e);
        }
    }

    public function generateShareAccountNumber()
    {
        do {
            $number = 'SHA-ACC-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (ShareAccount::where('account_number', $number)->exists());

        return $number;
    }
}
