<?php

namespace App\Http\Controllers\file_import;

use App\Http\Controllers\Controller;
use App\Imports\EmployeeImport;
use App\Models\file_import\FileImport;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class FileImportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        return view('file_imports.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('file_imports.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        // dd($request->all(), Schema::getColumnListing('employees'));
        $request->validate([
            'file' => 'required|mimes:xls,xlsx'
        ]);

        try {
            $file = $request->file('file');
            Excel::import(new EmployeeImport, $file);
            
            return redirect(route('file_imports.index'))->with(['success' => 'Data imported successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error importing data! ' . $th->getMessage(), $th);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(FileImport $file_import)
    {
        $file_path = 'files' . DIRECTORY_SEPARATOR . $file_import->category_dir . DIRECTORY_SEPARATOR;
        return Storage::disk('public')->download($file_path . $file_import->file_name, $file_import->origin_name);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(FileImport $file_import)
    { 
        $this->deleteFile($file_import);
        
        try {
            $file_import->delete();
            return redirect(route('file_imports.index'))->with(['success' => 'File deleted successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error deleting file!', $th);
        }
    }

    /***
     * Download Template
     * **/
    public function downloadTemplate($template) 
    {
        if ($template == 'employees') {
            $path = 'templates/employees.xlsx';
            $filepath = Storage::disk('public')->path($path);
            if (!file_exists($filepath)) abort(404, 'File not found.');
        }
        return Storage::disk('public')->download($path);
    }

    /**
     * Upload file to storage
     */
    public function uploadFile($file, $category)
    {
        $file_name = time() . '_' . $file->getClientOriginalName();
        $file_path = 'files' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR;
        Storage::disk('public')->put($file_path . $file_name, file_get_contents($file->getRealPath()));
        return $file_name;
    }

    /**
     * Delete file from storage
     */
    public function deleteFile($record)
    {
        $file_path = 'files' . DIRECTORY_SEPARATOR . $record->category_dir . DIRECTORY_SEPARATOR;
        $file_exists = Storage::disk('public')->exists($file_path . $record->file_name);
        if ($file_exists) Storage::disk('public')->delete($file_path . $record->file_name);
        return $file_exists;
    }
}
