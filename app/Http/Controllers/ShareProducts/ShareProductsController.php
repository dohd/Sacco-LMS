<?php

namespace App\Http\Controllers\ShareProducts;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Shares\ShareProduct;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShareProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shareProducts = ShareProduct::all();

        return view('share_products.index', compact('shareProducts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->session()->forget(['_old_input', 'errors']);

        $accounts = ChartOfAccount::all();

        return view('share_products.create', compact('accounts'));
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
            'code' => ['required', 'string', 'max:50', 'unique:share_products,code'],
            'name' => ['required', 'string', 'max:255', 'unique:share_products,name'],
            'description' => ['nullable', 'string'],

            // 'share_capital_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            // 'share_premium_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],

            'share_capital_account_id' => ['required', 'integer'],
            'share_premium_account_id' => ['required', 'integer'],

            'unit_value' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'minimum_units' => ['required', 'integer', 'min:1'],
            'maximum_units' => ['nullable', 'integer', 'min:1', 'gte:minimum_units'],

            'dividend_eligible' => ['nullable', 'boolean'],
            'allows_transfer' => ['nullable', 'boolean'],
            'allows_redemption' => ['nullable', 'boolean'],
            'can_secure_loan' => ['nullable', 'boolean'],
        ]);

        try {

            $shareProduct = DB::transaction(function () use ($validated, $request) {

                /*
                 * Make sure the selected GL accounts are active.
                 */
                $accounts = ChartOfAccount::whereIn('id', [
                    $validated['share_capital_account_id'],
                    $validated['share_premium_account_id'],
                ])
                    ->where('is_active', 1)
                    ->pluck('id')
                    ->toArray();

                // if (!in_array((int) $validated['share_capital_account_id'], $accounts, true)) {
                //     throw ValidationException::withMessages([
                //         'share_capital_account_id' => 'The selected share capital account is not active or does not exist.'
                //     ]);
                // }

                // if (!in_array((int) $validated['share_premium_account_id'], $accounts, true)) {
                //     throw ValidationException::withMessages([
                //         'share_premium_account_id' => 'The selected share premium account is not active or does not exist.'
                //     ]);
                // }

                /*
                 * Prevent the same GL account from being used for both
                 * share capital and share premium.
                 */
                // if (
                //     (int) $validated['share_capital_account_id'] ===
                //     (int) $validated['share_premium_account_id']
                // ) {
                //     throw ValidationException::withMessages([
                //         'share_premium_account_id' => 'Share capital and share premium must use different GL accounts.'
                //     ]);
                // }

                $minimumUnits = (int) $validated['minimum_units'];
                $maximumUnits = isset($validated['maximum_units'])
                    ? (int) $validated['maximum_units']
                    : null;

                if ($maximumUnits !== null && $maximumUnits < $minimumUnits) {
                    throw ValidationException::withMessages([
                        'maximum_units' => 'Maximum units cannot be less than minimum units.'
                    ]);
                }

                $shareProduct = ShareProduct::create([
                    'code' => strtoupper(trim($validated['code'])),
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,

                    'share_capital_account_id' => $validated['share_capital_account_id'],
                    'share_premium_account_id' => $validated['share_premium_account_id'],

                    'unit_value' => round((float) $validated['unit_value'], 2),
                    'minimum_units' => $minimumUnits,
                    'maximum_units' => $maximumUnits,

                    'dividend_eligible' => $request->boolean('dividend_eligible'),
                    'allows_transfer' => $request->boolean('allows_transfer'),
                    'allows_redemption' => $request->boolean('allows_redemption'),
                    'can_secure_loan' => $request->boolean('can_secure_loan'),
                ]);

                return $shareProduct;
            });

            return redirect()
                // ->route('share_products.show', $shareProduct->id)
                ->route('share_products.index')
                ->with('success', 'Share product created successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error creating share product. Please try again.',
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
    public function show(ShareProduct $shareProduct)
    {
        return view('share_products.view', compact('shareProduct'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ShareProduct $shareProduct)
    {
        // Inject the key-value pair into the request payload
        $payload = $shareProduct->toArray();
        request()->merge($payload);

        // Flash the modified request to the old input session store
        request()->flash();

        $accounts = ChartOfAccount::all();

        return view('share_products.edit', compact('shareProduct', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShareProduct $shareProduct)
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('share_products', 'code')->ignore($shareProduct->id),
            ],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('share_products', 'name')->ignore($shareProduct->id),
            ],
            'description' => ['nullable', 'string'],

            // 'share_capital_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            // 'share_premium_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],

            'share_capital_account_id' => ['required', 'integer'],
            'share_premium_account_id' => ['required', 'integer'],

            'unit_value' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'minimum_units' => ['required', 'integer', 'min:1'],
            'maximum_units' => ['nullable', 'integer', 'min:1', 'gte:minimum_units'],

            'dividend_eligible' => ['nullable', 'boolean'],
            'allows_transfer' => ['nullable', 'boolean'],
            'allows_redemption' => ['nullable', 'boolean'],
            'can_secure_loan' => ['nullable', 'boolean'],
        ]);

        try {

            DB::transaction(function () use ($validated, $request, $shareProduct) {

                /*
                 * Make sure the selected GL accounts are active.
                 */
                $accounts = ChartOfAccount::whereIn('id', [
                    $validated['share_capital_account_id'],
                    $validated['share_premium_account_id'],
                ])
                    ->where('is_active', 1)
                    ->pluck('id')
                    ->toArray();

                // if (!in_array((int) $validated['share_capital_account_id'], $accounts, true)) {
                //     throw ValidationException::withMessages([
                //         'share_capital_account_id' => 'The selected share capital account is not active or does not exist.'
                //     ]);
                // }

                // if (!in_array((int) $validated['share_premium_account_id'], $accounts, true)) {
                //     throw ValidationException::withMessages([
                //         'share_premium_account_id' => 'The selected share premium account is not active or does not exist.'
                //     ]);
                // }

                /*
                 * Prevent the same GL account from being used for both
                 * share capital and share premium.
                 */
                // if (
                //     (int) $validated['share_capital_account_id'] ===
                //     (int) $validated['share_premium_account_id']
                // ) {
                //     throw ValidationException::withMessages([
                //         'share_premium_account_id' => 'Share capital and share premium must use different GL accounts.'
                //     ]);
                // }

                $minimumUnits = (int) $validated['minimum_units'];
                $maximumUnits = isset($validated['maximum_units'])
                    ? (int) $validated['maximum_units']
                    : null;

                if ($maximumUnits !== null && $maximumUnits < $minimumUnits) {
                    throw ValidationException::withMessages([
                        'maximum_units' => 'Maximum units cannot be less than minimum units.'
                    ]);
                }

                $shareProduct->update([
                    'code' => strtoupper(trim($validated['code'])),
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,

                    'share_capital_account_id' => $validated['share_capital_account_id'],
                    'share_premium_account_id' => $validated['share_premium_account_id'],

                    'unit_value' => round((float) $validated['unit_value'], 2),
                    'minimum_units' => $minimumUnits,
                    'maximum_units' => $maximumUnits,

                    'dividend_eligible' => $request->boolean('dividend_eligible'),
                    'allows_transfer' => $request->boolean('allows_transfer'),
                    'allows_redemption' => $request->boolean('allows_redemption'),
                    'can_secure_loan' => $request->boolean('can_secure_loan'),
                ]);
            });

            return redirect()
                ->route('share_products.show', $shareProduct->id)
                ->with('success', 'Share product updated successfully.');

        } catch (Exception $e) {

            return errorHandler(
                'Error updating share product. Please try again.',
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

    public function toggleStatus($id)
    {
        try {
            $shareProduct = ShareProduct::find($id);
            $shareProduct->update(['is_active' => ! $shareProduct->is_active]);

        } catch (Exception $e) {

            return errorHandler(
                'Error updating status. Please try again.',
                $e
            );
        }
        
        return back()->with('success', "{$shareProduct->name} has been " . ($shareProduct->is_active ? 'activated' : 'deactivated') . '.');
    }
}
