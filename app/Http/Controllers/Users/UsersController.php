<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Roles\Role;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('id', '!=', auth()->user()->id)->whereNotNull('created_by')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::get();
        return view('users.create', compact('roles'));
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
            'fname' => 'required',
            'lname' => 'required',
            'email' => ['required', Rule::unique('users')->ignore(auth()->user()->id)],
            'phone' => 'required',
        ]);

        try {           
            DB::beginTransaction();

            $input = array_replace($request->except('_token'), [
                'password' => $request->phone,
            ]);
            $user = User::create($input);
            // $role = Role::find($input['role_id']);
            // $user->assignRole($role->name);

            DB::commit();
            return redirect(route('users.index'))->with(['success' => 'User created successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error creating user!', $th);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('users.view', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles = Role::get();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        if ($request->status != null) {
            try {
                $user->update(['is_active' => $request->input('status')]);
                return redirect()->back()->with('success', 'Status updated successfully');
            } catch (\Throwable $th) {
                return errorHandler('Error updating status!', $th);
            }
        } else {
            $request->validate([
                'fname' => 'required',
                'lname' => 'required',
                'email' => ['required', Rule::unique('users')->ignore($user->id)],
                'phone' => 'required',
            ]);
    
            try {
                DB::beginTransaction();
                
                $input = $request->only(['fname', 'lname', 'email', 'phone']);
                // $role = Role::find($input['role_id']);
                // $user->syncRoles([$role->name]);
                $user->update($input);
                
                DB::commit();
                return redirect(route('users.index'))->with(['success' => 'User updated successfully']);
            } catch (\Throwable $th) {
                return errorHandler('Error updating User!', $th);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        try {     
            // $role = Role::find($user->role_id);
            // $user->removeRole($role->name);       
            $user->delete();

            return redirect(route('users.index'))->with(['success' => 'User deleted successfully']);
        } catch (\Throwable $th) { 
            return errorHandler('Error deleting User!', $th);
        }
    }

    /**
     * Display active user profile.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active_profile()
    {
        $user = auth()->user();
        $role = auth()->user()->roles()->first() ?: new Role;
        
        return view('users.active_profile', compact('user', 'role'));
    }

    /**
     * Update Active Profile
     */
    public function update_active_profile(Request $request, User $user)
    {
        if ($request->password) {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password',
            ]);
            $input = $request->except('_token');
            $is_valid = password_verify($input['current_password'], auth()->user()->password);
            if (!$is_valid) return errorHandler('Current password is invalid!');
            
            try {     
                $user->update(['password' => $input['password']]);
                return redirect()->back()->with(['success' => 'Password updated successfully']);
            } catch (\Throwable $th) { 
                return errorHandler('Error updating Password!', $th);
            }
        }

        $request->validate([
            'username' => 'required',
            'email' => 'required',
        ]);
        $input = $request->only('username', 'email', 'phone');
        // unset($input['email']);

        $validator = Validator::make($request->all(), [
            'profile_pic' => $request->profile_pic? 'required|mimes:png,jpg,jpeg' : 'nullable',
        ]);
        if ($validator->fails()) return errorHandler('Unsupported image format! Use png, jpg or jpeg');
        $file = $request->file('profile_pic');
        if ($file) $input['profile_pic'] = $this->uploadFile($file);

        try {     
            $user->update($input);
            return redirect()->back()->with(['success' => 'User Profile updated successfully']);
        } catch (\Throwable $th) { 
            return errorHandler('Error updating User Profile!', $th);
        }
    }

    /**
     * Remove the image from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete_profile_pic(Request $request, User $user)
    {
        try {
            $this->deleteFile($user->profile_pic);
            $user->update(['profile_pic' => null]);
            return response()->json(['success' => true, 'message' => 'Profile Picture removed successfully', 'redirectTo' => route('users.active_profile')]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Upload file to storage
     */
    public function uploadFile($file)
    {
        $file_name = time() . '_' . $file->getClientOriginalName();
        $file_path = 'images' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR;
        Storage::disk('public')->put($file_path . $file_name, file_get_contents($file->getRealPath()));
        return $file_name;
    }

    /**
     * Delete file from storage
     */
    public function deleteFile($file_name)
    {
        $file_path = 'images' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR;
        $file_exists = Storage::disk('public')->exists($file_path . $file_name);
        if ($file_exists) Storage::disk('public')->delete($file_path . $file_name);
        return $file_exists;
    }
}
