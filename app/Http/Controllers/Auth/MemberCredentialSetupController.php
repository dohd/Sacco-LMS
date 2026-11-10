<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Memberships\MemberApplication;

class MemberCredentialSetupController extends Controller
{
    public function show()
    {
        abort_unless(auth()->user()->member_login_setup_required, 403);

        return view('auth.member_credential_setup');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->member_login_setup_required, 403);
        $member = $user->member;
        abort_unless($member, 403);
        $applicationId = $member->member_application_id;

        $request->validate([
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('members', 'email')->ignore($member->id),
                Rule::unique('member_applications', 'email')->ignore($applicationId),
            ],
            'password' => 'required|string|min:10|confirmed',
        ]);

        DB::transaction(function () use ($request, $user, $member) {
            $user->email = strtolower($request->input('email'));
            $user->password = $request->input('password');
            $user->member_login_setup_required = false;
            $user->save();

            $member->email = strtolower($request->input('email'));
            $member->save();

            MemberApplication::whereKey($member->member_application_id)->update(['email' => strtolower($request->input('email'))]);
        });

        $request->session()->regenerate();

        return redirect()->route('member.kyc.show')->with('success', 'Your login is set up. Complete your membership details for KYC review.');
    }
}
