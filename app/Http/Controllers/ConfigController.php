<?php

namespace App\Http\Controllers\config;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $settings = Setting::pluck('value', 'key')->all();
        
        return view('settings.create', compact('settings'));
    }

    public function update(Request $request)
    {
        $input = $request->except('_token');

        try {
            DB::beginTransaction();

            // update key-value pairs
            foreach ($input as $key => $value) {
                Setting::where('key', $key)->update(['value' => $value]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Settings updated successfully');
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' {user_id:'. auth()->user()->id . '} at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', 'Settings update failed. ' . $e->getMessage());
        }
    }

    /**
     * Clear Cache
     */
    public function clear_cache() 
    {   
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
        Artisan::call('down');
        return redirect()->back();
    }
}
