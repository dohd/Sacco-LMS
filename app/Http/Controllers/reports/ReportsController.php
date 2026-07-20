<?php

namespace App\Http\Controllers\reports;

use App\Http\Controllers\Controller;
use App\Models\employee\Employee;

class ReportsController extends Controller
{
    /**
     * 
     */
    public function employeeByDesignation() 
    {   
        $designations = Employee::selectRaw('job_desig, COUNT(*) as count')
        ->whereNotNull('job_desig')
        ->groupBy('job_desig')
        ->get();

        return view('reports.employee_by_designation', compact('designations'));
    }

    public function employeeBySkillLevel() 
    {   
        $skillLevels = Employee::selectRaw('education_peak, COUNT(*) as count')
        ->whereNotNull('education_peak')
        ->groupBy('education_peak')
        ->get();

        return view('reports.employee_by_skill_level', compact('skillLevels'));
    }

    public function employeeByWorkCounty() 
    {   
        $workCounties = Employee::selectRaw('work_county, COUNT(*) as count')
        ->whereNotNull('work_county')
        ->groupBy('work_county')
        ->get();

        return view('reports.employee_by_work_county', compact('workCounties'));
    }
}
