<?php

namespace App\Http\Controllers\employees;

use App\Http\Controllers\Controller;
use App\Models\employee\Employee;
use App\Models\employee\EmployeeDoc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $employees = Employee::latest()->get();
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        $excluded = [
            'id', 'ins', 'user_id', 'created_at', 'updated_at',
        ];
        $employeeCols = DB::table('information_schema.columns')
        ->select('COLUMN_NAME')
        ->where('table_schema', DB::getDatabaseName()) // current DB
        ->where('table_name', 'employees')
        ->whereNotIn('COLUMN_NAME', $excluded)
        ->orderBy('ORDINAL_POSITION')
        ->pluck('COLUMN_NAME')
        ->toArray();
        $employeeCols = array_chunk($employeeCols, 4);

        return view('employees.create', compact('employeeCols'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $input = $request->except('_token');
        try {   
            foreach ($input as $key => $value) {
                if ($key == 'gross_salary') $input[$key] = numberClean($value);
                if (strpos($key, 'date') !== false) {
                    $input[$key] = Carbon::parse($value)->format('Ymd');
                }
            }

            $employee = Employee::create($input);
            return redirect(route('employees.index'))->with(['success' => 'Employee created successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error creating Employee!', $th);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Employee $employee)
    {
        $excluded = [
            'id', 'ins', 'user_id', 'created_at', 'updated_at',
        ];
        $employeeCols = DB::table('information_schema.columns')
        ->select('COLUMN_NAME')
        ->where('table_schema', DB::getDatabaseName()) // current DB
        ->where('table_name', 'employees')
        ->whereNotIn('COLUMN_NAME', $excluded)
        ->orderBy('ORDINAL_POSITION')
        ->pluck('COLUMN_NAME')
        ->toArray();
        $employeeCols = array_chunk($employeeCols, 4);

        foreach ($employee->getAttributes() as $key => $value) {
            if (strpos($key, 'date') !== false && $value) {
                $employee[$key] = dateFormat($value, 'Y-m-d');
            }
        }

        return view('employees.view', compact('employee', 'employeeCols'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Employee $employee)
    {
        $excluded = [
            'id', 'ins', 'user_id', 'created_at', 'updated_at',
        ];
        $employeeCols = DB::table('information_schema.columns')
        ->select('COLUMN_NAME')
        ->where('table_schema', DB::getDatabaseName()) // current DB
        ->where('table_name', 'employees')
        ->whereNotIn('COLUMN_NAME', $excluded)
        ->orderBy('ORDINAL_POSITION')
        ->pluck('COLUMN_NAME')
        ->toArray();
        $employeeCols = array_chunk($employeeCols, 4);

        foreach ($employee->getAttributes() as $key => $value) {
            if (strpos($key, 'date') !== false && $value) {
                $employee[$key] = dateFormat($value, 'Y-m-d');
            }
        }

        return view('employees.edit', compact('employee', 'employeeCols'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Employee $employee, Request $request)
    { 
        // dd($request->all());
        $input = $request->except('_token');
        try {      
            foreach ($input as $key => $value) {
                if ($key == 'gross_salary') $input[$key] = numberClean($value);
                if (strpos($key, 'date') !== false) {
                    $input[$key] = Carbon::parse($value)->format('Ymd');
                }
            }

            $employee->update($input);
            return redirect(route('employees.index'))->with(['success' => 'Employee updated successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error updating Employee!', $th);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Employee $employee)
    {
        try {            
            $employee->delete();
            return redirect(route('employees.index'))->with(['success' => 'Employee deleted successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error deleting Employee!', $th);
        }
    }

    /**
     * Employee Document Upload
     * */
    public function documentUpload(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'employee_id' => 'required',
        ]);
        $file = $request->file('file');
        // dd($file);

        try {  
            $employee = Employee::findOrFail(request('employee_id'));

            $fileName = $this->storeFile($file);
            EmployeeDoc::create([
                'employee_id' => $request->employee_id,
                'origin_name' => $file->getClientOriginalName(),
                'name' => $fileName,
                'caption' => $request->caption,
                'doc_type' => $request->doc_type,
            ]);

            return redirect(route('employees.show', $employee))->with(['success' => 'Document uploaded successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error uploading Document!', $th);
        }
    }

    /***
     * Document Download
     * **/
    public function documentDownload($doc) 
    {
        $document = EmployeeDoc::findOrFail($doc);
        $path = 'employee_docs/' . $document->name;
        $filepath = Storage::disk('public')->path($path);
        if (!file_exists($filepath)) abort(404, 'File not found.');
        return Storage::disk('public')->download($path);
    }

    /**
     * Store file in local storage
     */
    public function storeFile($file)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        Storage::disk('public')->put("employee_docs/{$fileName}", file_get_contents($file->getRealPath()));
        return $fileName;
    }
}
