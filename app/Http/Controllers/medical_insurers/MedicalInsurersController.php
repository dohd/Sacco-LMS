<?php

namespace App\Http\Controllers\medical_insurers;

use App\Http\Controllers\Controller;
use App\Models\attendance\Attendance;
use App\Models\medical_insurers\MedicalInsurer;
use Illuminate\Http\Request;

class MedicalInsurersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $medical_insurers = MedicalInsurer::get();
        return view('medical_insurers.index', compact('medical_insurers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        $medical_insurers = MedicalInsurer::get();
        return view('medical_insurers.index', compact('medical_insurers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(MedicalInsurer $medical_insurer)
    {
        $medical_insurers = MedicalInsurer::get();
        return view('medical_insurers.index', compact('medical_insurer', 'medical_insurers'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(MedicalInsurer $medical_insurer)
    {
        return view('medical_insurers.edit', compact('medical_insurer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Attendance $attendance)
    { 
        // 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attendance $attendance)
    {
        // 
    }
}
