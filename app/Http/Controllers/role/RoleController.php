<?php

namespace App\Http\Controllers\role;

use App\Http\Controllers\Controller;
use App\Models\role\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required',
        ]);

        DB::beginTransaction();

        try {            
            $role = SpatieRole::create([
                'name' => $request->input('name'), 
                'user_id' => auth()->user()->id,
                'ins' => auth()->user()->ins,
            ]);
            $role->syncPermissions($request->input('permissions'));

            DB::commit();
            return redirect(route('roles.index'))->with(['success' => 'Role & Rights created successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error creating Role!', $th);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return view('roles.view', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(SpatieRole $role)
    {
        return view('roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SpatieRole $role)
    {
        $request->validate([
            'name' => 'required',
            'permissions' => 'required',
        ]);

        DB::beginTransaction();

        try {            
            $role->update(['name' => $request->input('name')]);
            $role->syncPermissions($request->input('permissions'));

            DB::commit();
            return redirect(route('roles.index'))->with(['success' => 'Role & Rights updated successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error updating role!', $th);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(SpatieRole $role)
    {
        try {          
            $role->permissions()->detach();
            $role->delete();

            return redirect(route('roles.index'))->with(['success' => 'Role & Rights deleted successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error deleting Role!', $th);
        }
    }
}
