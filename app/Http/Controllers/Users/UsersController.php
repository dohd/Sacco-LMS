<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Roles\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $users = User::where('id', '!=', auth()->user()->id)->get();
        $users = User::all();
        
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::where('is_active', true)->orderBy('name')->get();

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255', 'unique:users,phone'],
            'employee_number' => ['nullable', 'string', 'max:255', 'unique:users,employee_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        try {
            DB::transaction(function () use ($validated, &$user) {

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'employee_number' => $validated['employee_number'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'is_active' => $validated['is_active'] ?? false,
                ]);

                $user->roles()->sync($validated['roles']);
            });

            return redirect()
                ->route('users.show', $user->id)
                ->with('success', 'User created successfully.');

        } catch (Exception $e) {
            return errorHandler(
                'Error creating user. Please try again.',
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
    public function show(User $user)
    {
        $user->load('roles');
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
        $roles = Role::where('is_active', true)->orderBy('name')->get();

        $user->load('roles');

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'employee_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'employee_number')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        try {
            DB::transaction(function () use ($validated, $user) {

                $data = [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'employee_number' => $validated['employee_number'] ?? null,
                    'is_active' => $validated['is_active'] ?? false,
                ];

                if (!empty($validated['password'])) {
                    $data['password'] = Hash::make($validated['password']);
                }

                $user->update($data);

                $user->roles()->sync($validated['roles']);
            });

            return redirect()
                ->route('users.show', $user->id)
                ->with('success', 'User updated successfully.');

        } catch (Exception $e) {
            return errorHandler(
                'Error updating user. Please try again.',
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

    public function deactivate($id)
    {
        try {     

            return back()->with(['success' => 'User deactivated successfully']);
        } catch (\Throwable $th) { 
            return errorHandler('Error deactivating user. Trya again later', $th);
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
