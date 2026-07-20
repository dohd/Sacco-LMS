<?php

namespace App\Http\Controllers\storage;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
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
}
