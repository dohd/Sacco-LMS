<?php

namespace App\Http\Controllers\AccountingPeriods;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPeriod;
use Auth;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountingPeriodsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $periods = AccountingPeriod::latest()->get();

        return view('accounting_periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('accounting_periods.create');
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
            'name' => ['required', 'string', 'max:255'],
            'financial_year' => ['required', 'integer', 'min:1900', 'max:9999'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            $exists = AccountingPeriod::where(function ($q) use ($validated) {
                $q->whereDate('start_date', '<=', $validated['end_date'])
                  ->whereDate('end_date', '>=', $validated['start_date']);
            })->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'start_date' => 'The selected dates overlap an existing accounting period.'
                ]);
            }

            $period = AccountingPeriod::create([
                'name' => $validated['name'],
                'financial_year' => $validated['financial_year'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
            ]);

            return redirect()
                ->route('accounting_periods.show', $period->id)
                ->with('success', 'Accounting period created successfully.');

        } catch (Exception $e) {
            return errorHandler(
                'Error creating accounting period. Please try again.',
                $e
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(AccountingPeriod $accountingPeriod)
    {
        $accountingPeriod->load('closedBy');

        return view('accounting_periods.view', compact('accountingPeriod'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(AccountingPeriod $accountingPeriod)
    {
        if ($accountingPeriod->status !== 'open') {
            throw ValidationException::withMessages([
                'period' => 'Only open accounting periods can be edited.'
            ]);
        }

        return view('accounting_periods.edit', compact('accountingPeriod'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AccountingPeriod $accountingPeriod)
    {
        if ($accountingPeriod->status !== 'open') {
            throw ValidationException::withMessages([
                'period' => 'Only open accounting periods can be edited.'
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'financial_year' => ['required', 'integer', 'min:1900', 'max:9999'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            DB::transaction(function () use ($accountingPeriod, $validated) {

                $period = AccountingPeriod::whereKey($accountingPeriod->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($period->status !== 'open') {
                    throw ValidationException::withMessages([
                        'period' => 'Only open accounting periods can be edited.'
                    ]);
                }

                $exists = AccountingPeriod::where('id', '!=', $period->id)
                    ->whereDate('start_date', '<=', $validated['end_date'])
                    ->whereDate('end_date', '>=', $validated['start_date'])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'start_date' =>
                            'The selected dates overlap an existing accounting period.'
                    ]);
                }

                $period->update([
                    'name' => $validated['name'],
                    'financial_year' => $validated['financial_year'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);
            });

            return redirect()
                ->route('accounting_periods.show', $accountingPeriod->id)
                ->with('success', 'Accounting period updated successfully.');

        } catch (Exception $e) {
            return errorHandler(
                'Error updating accounting period. Please try again.',
                $e
            );
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

    public function close($id)
    {
        try {
            DB::transaction(function () use ($id) {

                $period = AccountingPeriod::whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($period->status !== 'open') {
                    throw ValidationException::withMessages([
                        'status' => 'Only open accounting periods can be closed.'
                    ]);
                }

                $period->update([
                    'status' => 'closed',
                    'closed_by' => Auth::id(),
                    'closed_at' => now(),
                ]);
            });

            return back()->with(
                'success',
                'Accounting period closed successfully.'
            );

        } catch (Exception $e) {
            return errorHandler(
                'Error closing accounting period. Please try again.',
                $e
            );
        }
    }

    public function lock($id)
    {
        try {
            DB::transaction(function () use ($id) {

                $period = AccountingPeriod::whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($period->status !== 'closed') {
                    throw ValidationException::withMessages([
                        'status' => 'Only closed accounting periods can be locked.'
                    ]);
                }

                $period->update([
                    'status' => 'locked',
                ]);
            });

            return back()->with(
                'success',
                'Accounting period locked successfully.'
            );

        } catch (Exception $e) {
            return errorHandler(
                'Error locking accounting period. Please try again.',
                $e
            );
        }
    }
}
