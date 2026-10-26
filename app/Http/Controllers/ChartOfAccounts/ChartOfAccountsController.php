<?php

namespace App\Http\Controllers\ChartOfAccounts;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChartOfAccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = ChartOfAccount::latest()->get();

        return view('chart_of_accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parentAccounts = ChartOfAccount::where('is_active', true)
            ->where('is_postable', false) 
            ->orderBy('code') 
            ->get(); 

        return view('chart_of_accounts.create', compact('parentAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'               => ['required', 'string', 'max:50', 'unique:chart_of_accounts,code'],
            'name'               => ['required', 'string', 'max:255', 'unique:chart_of_accounts,name'],
            'parent_id'          => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'account_type'       => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'normal_balance'     => ['nullable', Rule::in(['debit', 'credit'])],
            'is_control_account' => ['sometimes', 'boolean'],
            'is_postable'        => ['sometimes', 'boolean'],
            'is_active'          => ['sometimes', 'boolean'],
            'description'        => ['nullable', 'string'],
        ]);

        try {
            // Default normal balance from account type when not supplied.
            $data['normal_balance'] = $data['normal_balance']
                ?? (in_array($data['account_type'], ['asset', 'expense'], true) ? 'debit' : 'credit');

            // Child accounts must share the parent's type, and the parent must be a header.
            if (! empty($data['parent_id'])) {
                $parent = ChartOfAccount::findOrFail($data['parent_id']);

                if ($parent->account_type !== $data['account_type']) {
                    return ValidationException::withMessages(['account_type' => 'Account type must match the parent account type.']);
                }

                if ($parent->is_postable) {
                    return ValidationException::withMessages(['account_type' => 'Parent must be a header (non-postable) account.']);                
                }
            }

            // Control accounts are not directly postable.
            if (!empty($data['is_control_account'])) {
                $data['is_postable'] = $data['is_postable'] ?? true;
            }

            $account = ChartOfAccount::create($data);

            return redirect()
                ->route('chart_of_accounts.index')
                ->with('success', 'Account created successfully.');
        } catch (\Exception $e) {
            return errorHandler("Account could not be submitted. Please try again.", $e);
        }    
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ChartOfAccount $chartOfAccount)
    {
        $chartOfAccount->load([ 
            'parent', 
            'children' => fn($query) => $query->orderBy('code'), 
        ]);

        return view('chart_of_accounts.view', compact( 'chartOfAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ChartOfAccount $chartOfAccount)
    {
        $parentAccounts = ChartOfAccount::where('is_active', true) 
            ->where('is_postable', false) 
            ->where('id', '!=', $chartOfAccount->id) 
            ->orderBy('code') 
            ->get(); 

        return view('chart_of_accounts.edit', compact( 'chartOfAccount', 'parentAccounts' ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        $data = $request->validate([
            'code'               => ['required', 'string', 'max:50', Rule::unique('chart_of_accounts', 'code')->ignore($chartOfAccount->id)],
            'name'               => ['required', 'string', 'max:255', Rule::unique('chart_of_accounts', 'name')->ignore($chartOfAccount->id)],
            'parent_id'          => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'account_type'       => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'normal_balance'     => ['nullable', Rule::in(['debit', 'credit'])],
            'is_control_account' => ['sometimes', 'boolean'],
            'is_postable'        => ['sometimes', 'boolean'],
            'is_active'          => ['sometimes', 'boolean'],
            'description'        => ['nullable', 'string'],
        ]);

        try {
            // Default normal balance from account type when not supplied.
            $data['normal_balance'] = $data['normal_balance']
                ?? (in_array($data['account_type'], ['asset', 'expense'], true) ? 'debit' : 'credit');

            // Child accounts must share the parent's type, and the parent must be a header.
            if (! empty($data['parent_id'])) {
                $parent = ChartOfAccount::findOrFail($data['parent_id']);

                if ($parent->account_type !== $data['account_type']) {
                    return ValidationException::withMessages(['account_type' => 'Account type must match the parent account type.']);
                }

                if ($parent->is_postable) {
                    return ValidationException::withMessages(['account_type' => 'Parent must be a header (non-postable) account.']);                
                }
            }

            // Control accounts are not directly postable.
            if (!empty($data['is_control_account'])) {
                $data['is_postable'] = $data['is_postable'] ?? true;
            }

            $chartOfAccount->update($data);

            return redirect()
                ->route('chart_of_accounts.index')
                ->with('success', 'Account updated successfully.');
        } catch (\Exception $e) {
            return errorHandler("Account could not be updated. Please try again.", $e);
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

    public function getAccountCode(Request $request)
    {
        $accType = request('account_type');
        $isControlAcc = request('is_control_account');
        $accountExists = ChartOfAccount::where('account_type', $accType)->exists();            
        
        if ($accountExists) {
            $code = ChartOfAccount::max('code')+1;  
            if (!empty($isControlAcc)) {
                $code = ChartOfAccount::whereNull('parent_id')->max('code')+100;  
            }        
        } else {
            $codes = [
                'asset' => 1000,
                'liability' => 2000,
                'equity' => 3000,
                'income' => 4000, 
                'expense' => 5000,
            ];
            $code = $codes[$accType]+1;
            if (!empty($isControlAcc)) {
                $code = $codes[$accType]+100;
            }  
        }

        return response()->json(compact('code'));
    }

    public function getParentAccounts(Request $request)
    {
        $accType = request('account_type');
        $accounts = ChartOfAccount::where('account_type', $accType)
            ->where('is_control_account', 1)
            ->get();

        return response()->json($accounts);
    }
}
