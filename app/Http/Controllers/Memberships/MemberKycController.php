<?php

namespace App\Http\Controllers\Memberships;

use App\Http\Controllers\Controller;
use App\Models\Memberships\MemberApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberKycController extends Controller
{
    public function show()
    {
        $member = auth()->user()->member;
        abort_unless($member, 403);
        if ($member->kyc_status === 'complete') return redirect()->route('home');
        if ($member->kyc_submitted_at) return redirect()->route('member.kyc.pending');

        $application = MemberApplication::findOrFail($member->member_application_id);
        return view('memberships.member_kyc', compact('member', 'application'));
    }

    public function store(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 403);
        abort_if($member->kyc_status === 'complete' || $member->kyc_submitted_at, 403);
        $application = MemberApplication::findOrFail($member->member_application_id);

        $validated = $request->validate([
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'national_id' => [
                'required', 'string', 'max:100',
                Rule::unique('member_applications', 'national_id')->ignore($application->id),
                Rule::unique('members', 'national_id')->ignore($member->id),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'current_address' => ['nullable', 'string', 'max:2000'],
            'residential_address' => ['required', 'string', 'max:2000'],
            'next_of_kin_name' => ['required', 'string', 'max:255'],
            'next_of_kin_id' => ['required', 'string', 'max:100'],
            'next_of_kin_relationship' => ['required', Rule::in(['spouse', 'parent', 'child', 'sibling', 'relative', 'guardian', 'other'])],
            'national_id_front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'national_id_back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $paths = [];
        foreach (['national_id_front', 'national_id_back'] as $field) {
            $paths[$field] = $request->file($field)->store("memberships/{$application->application_number}/{$field}", 'private');
        }

        DB::transaction(function () use ($member, $application, $validated, $paths) {
            $application->update([
                'date_of_birth' => $validated['date_of_birth'],
                'place_of_birth' => trim($validated['place_of_birth']),
                'national_id' => strtoupper(trim($validated['national_id'])),
                'phone' => trim($validated['phone']),
                'email' => auth()->user()->email,
                'current_address' => $validated['current_address'] ?? null,
                'residential_address' => trim($validated['residential_address']),
                'next_of_kin_name' => trim($validated['next_of_kin_name']),
                'next_of_kin_id' => strtoupper(trim($validated['next_of_kin_id'])),
                'next_of_kin_relationship' => $validated['next_of_kin_relationship'],
                'national_id_front' => $paths['national_id_front'],
                'national_id_back' => $paths['national_id_back'],
                'kyc_status' => 'pending',
            ]);
            $member->update([
                'date_of_birth' => $validated['date_of_birth'],
                'place_of_birth' => trim($validated['place_of_birth']),
                'national_id' => strtoupper(trim($validated['national_id'])),
                'phone' => trim($validated['phone']),
                'email' => auth()->user()->email,
                'current_address' => $validated['current_address'] ?? null,
                'residential_address' => trim($validated['residential_address']),
                'kyc_status' => 'pending',
                'kyc_submitted_at' => now(),
            ]);
        });

        return redirect()->route('member.kyc.pending')->with('success', 'Your membership details were submitted for KYC verification.');
    }

    public function pending()
    {
        $member = auth()->user()->member;
        abort_unless($member, 403);
        if ($member->kyc_status === 'complete') return redirect()->route('home');
        if (!$member->kyc_submitted_at) return redirect()->route('member.kyc.show');

        return view('memberships.member_kyc_pending', compact('member'));
    }
}
