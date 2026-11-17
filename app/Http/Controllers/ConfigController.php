<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Settings\Setting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Accounting\ChartOfAccount;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ConfigController extends Controller
{
    /**
     * File Render
     */
    public function file_render ($file_params) 
    {   
        $file_path = '';
        $params = explode(',', $file_params);
        foreach ($params as $value) {
            $file_path .= $value . DIRECTORY_SEPARATOR;
        }

        return Storage::disk('public')->get($file_path);
    }

    /**
     * File Download
     */
    public function file_download ($file_params) 
    {   
        $file_path = '';
        $params = explode(',', $file_params);
        foreach ($params as $value) {
            $file_path .= $value . DIRECTORY_SEPARATOR;
        }

        return Storage::disk('public')->download($file_path);
    }

    public function create()
    {
        $this->requireSaccoPermission('manage-settings');
        $settings = Setting::pluck('value', 'key')->all();
        $accounts = ChartOfAccount::where('is_active', true)
            ->where('is_postable', true)
            ->where('is_control_account', false)
            ->orderBy('code')
            ->get();

        return view('settings.create', compact('settings', 'accounts'));
    }

    public function update(Request $request)
    {
        $this->requireSaccoPermission('manage-settings');

        $validated = $request->validate([
            'SACCO_NAME' => ['required', 'string', 'max:255'],
            'SACCO_REGISTRATION_NUMBER' => ['nullable', 'string', 'max:100'],
            'SACCO_PHONE' => ['nullable', 'string', 'max:50'],
            'SACCO_EMAIL' => ['nullable', 'email', 'max:255'],
            'SACCO_ADDRESS' => ['nullable', 'string', 'max:1000'],
            'SACCO_WEBSITE' => ['nullable', 'url', 'max:255'],
            'SACCO_LOGO' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'SACCO_CURRENCY' => ['required', 'regex:/^[A-Z]{3}$/i'],
            'MEMBERSHIP_NUMBER_PREFIX' => [
                'required',
                'string',
                'max:12',
                'regex:/\A[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*\z/',
            ],
            'MEMBERSHIP_NUMBER_SEQUENCE_LENGTH' => ['required', 'integer', 'between:3,10'],
            'PASSWORD_MIN_LENGTH' => ['required', 'integer', 'between:8,64'],
            'FINANCIAL_YEAR_START_MONTH' => ['required', 'integer', 'between:1,12'],
            'FINANCIAL_YEAR_START_DAY' => ['required', 'integer', 'between:1,31'],
            'RETAINED_EARNINGS_ACCOUNT_ID' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('account_type', 'equity')->where('is_active', true)->where('is_postable', true)->where('is_control_account', false)],
            'OPENING_BALANCE_EQUITY_ACCOUNT_ID' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('account_type', 'equity')->where('is_active', true)->where('is_postable', true)->where('is_control_account', false)],
            'DIVIDEND_PAYABLE_ACCOUNT_ID' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('account_type', 'liability')->where('is_active', true)->where('is_postable', true)->where('is_control_account', false)],
            'WITHHOLDING_TAX_ACCOUNT_ID' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('account_type', 'liability')->where('is_active', true)->where('is_postable', true)->where('is_control_account', false)],
        ]);
        $validated['MEMBERSHIP_NUMBER_PREFIX'] = strtoupper($validated['MEMBERSHIP_NUMBER_PREFIX']);
        $validated['SACCO_CURRENCY'] = strtoupper($validated['SACCO_CURRENCY']);
        $oldLogo = Setting::where('key', 'SACCO_LOGO')->value('value');
        $newLogo = null;
        if ($request->hasFile('SACCO_LOGO')) {
            $newLogo = $request->file('SACCO_LOGO')->store('images/settings', 'public');
            if (!$newLogo) {
                return redirect()->back()->withInput()->with('error', 'The SACCO logo could not be saved.');
            }
            $validated['SACCO_LOGO'] = $newLogo;
        } else {
            unset($validated['SACCO_LOGO']);
        }

        try {
            DB::beginTransaction();

            $changedKeys = [];
            foreach ($validated as $key => $value) {
                $previous = Setting::where('key', $key)->value('value');
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);

                if ($previous !== (string) $value) {
                    $changedKeys[] = $key;
                }
            }

            DB::commit();
            Cache::forget('sacco.settings.values');
            if ($request->hasFile('SACCO_LOGO') && $oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            Log::info('SACCO settings updated', [
                'user_id' => auth()->id(),
                'changed_keys' => $changedKeys,
            ]);

            return redirect()->back()->with('success', 'Settings updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }
            Log::error($e->getMessage() . ' {user_id:'. auth()->user()->id . '} at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', 'Settings update failed. Please try again.');
        }
    }

    /**
     * Clear Cache
     */
    public function clear_cache() 
    {   
        $this->requireSaccoPermission('manage-settings');

        try {
            Artisan::call('optimize:clear');
            return "Application cache cleared";
        } catch (\Throwable $th) {
            return "Something went wrong! " . $th->getMessage();
        }
    }

    /**
     * Maintenance Mode
     */
    public function site_down() 
    {
        $this->requireSaccoPermission('manage-settings');
        Artisan::call('down');
        return redirect()->back();
    }
}
