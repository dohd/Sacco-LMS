<?php

namespace App\Http\Controllers\Memberships;

use App\Http\Controllers\Controller;
use App\Models\Memberships\MemberApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MemberKycController extends Controller
{
    public function show()
    {
        $member = auth()->user()->member;
        abort_unless($member, 403);
        if ($member->kyc_status === 'complete') {
            return redirect()->route('memberships.show', $member->member_application_id)
                ->with('warning', 'Your KYC details have been verified and can no longer be edited. Contact an administrator if a correction is needed.');
        }

        $application = MemberApplication::findOrFail($member->member_application_id);
        $request = request();
        $applicationData = $application->toArray();
        foreach (['national_id', 'phone'] as $field) {
            if (strpos((string) ($applicationData[$field] ?? ''), 'PILOT-PENDING-') === 0) {
                $applicationData[$field] = '';
            }
        }

        // Seed the form with saved application values on first load, but keep
        // Laravel's flashed submission values after a validation redirect.
        if (!$request->session()->has('_old_input')) {
            $request->merge($applicationData);
            $request->flash();
        }

        $memberKycMode = true;
        return view('memberships.member_kyc', compact('member', 'application', 'memberKycMode'));
    }

    public function store(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 403);
        if ($member->kyc_status === 'complete') {
            return redirect()->route('memberships.show', $member->member_application_id)
                ->with('warning', 'Your KYC details have been verified and can no longer be edited. Contact an administrator if a correction is needed.');
        }
        $application = MemberApplication::findOrFail($member->member_application_id);

        $frontDocumentRule = $application->national_id_front ? 'nullable' : 'required';
        $backDocumentRule = $application->national_id_back ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
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
            'employer_name' => ['nullable', 'string', 'max:255'],
            'working_station' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employer_address' => ['nullable', 'string', 'max:2000'],
            'employer_phone' => ['nullable', 'string', 'max:30'],
            'employment_terms' => ['nullable', Rule::in(['permanent', 'contract', 'temporary', 'casual', 'seasonal', 'self_employed'])],
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_nature' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:2000'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_location' => ['nullable', 'string', 'max:2000'],
            'next_of_kin_name' => ['required', 'string', 'max:255'],
            'next_of_kin_id' => ['required', 'string', 'max:100'],
            'next_of_kin_relationship' => ['required', Rule::in(['spouse', 'parent', 'child', 'sibling', 'relative', 'guardian', 'other'])],
            'monthly_contribution' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'contribution_start_date' => ['nullable', 'date'],
            'national_id_front' => [$frontDocumentRule, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'national_id_back' => [$backDocumentRule, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $employmentFields = ['employer_name', 'working_station', 'designation', 'employer_address', 'employer_phone', 'employment_terms'];
            $businessFields = ['business_name', 'business_nature', 'business_address', 'business_phone', 'business_location'];

            $hasEmploymentDetails = collect($employmentFields)->contains(function ($field) use ($request) {
                return $request->filled($field);
            });
            $hasBusinessDetails = collect($businessFields)->contains(function ($field) use ($request) {
                return $request->filled($field);
            });

            if (!$hasEmploymentDetails && !$hasBusinessDetails) {
                $validator->errors()->add('employment_or_business', 'Provide employment details, business details, or both.');
            }
        });

        $validated = $validator->validate();

        $paths = [];
        foreach (['national_id_front', 'national_id_back'] as $field) {
            if ($request->hasFile($field)) {
                $paths[$field] = $request->file($field)->store("memberships/{$application->application_number}/{$field}", 'private');
            }
        }

        DB::transaction(function () use ($member, $application, $validated, $paths) {
            $application->update(array_merge([
                'first_name' => trim($validated['first_name']),
                'middle_name' => isset($validated['middle_name']) ? trim($validated['middle_name']) : null,
                'last_name' => trim($validated['last_name']),
                'date_of_birth' => $validated['date_of_birth'],
                'place_of_birth' => trim($validated['place_of_birth']),
                'national_id' => strtoupper(trim($validated['national_id'])),
                'phone' => trim($validated['phone']),
                'email' => auth()->user()->email,
                'current_address' => $validated['current_address'] ?? null,
                'residential_address' => trim($validated['residential_address']),
                'employer_name' => $validated['employer_name'] ?? null,
                'working_station' => $validated['working_station'] ?? null,
                'designation' => $validated['designation'] ?? null,
                'employer_address' => $validated['employer_address'] ?? null,
                'employer_phone' => $validated['employer_phone'] ?? null,
                'employment_terms' => $validated['employment_terms'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'business_nature' => $validated['business_nature'] ?? null,
                'business_address' => $validated['business_address'] ?? null,
                'business_phone' => $validated['business_phone'] ?? null,
                'business_location' => $validated['business_location'] ?? null,
                'next_of_kin_name' => trim($validated['next_of_kin_name']),
                'next_of_kin_id' => strtoupper(trim($validated['next_of_kin_id'])),
                'next_of_kin_relationship' => $validated['next_of_kin_relationship'],
                'monthly_contribution' => $validated['monthly_contribution'] ?? 0,
                'contribution_start_date' => $validated['contribution_start_date'] ?? null,
                'kyc_status' => 'pending',
            ], $paths));
            $member->update([
                'first_name' => trim($validated['first_name']),
                'middle_name' => isset($validated['middle_name']) ? trim($validated['middle_name']) : null,
                'last_name' => trim($validated['last_name']),
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

        return redirect()->route('memberships.show', $member->member_application_id);
    }
}
