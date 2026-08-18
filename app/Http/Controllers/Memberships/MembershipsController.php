<?php

namespace App\Http\Controllers\Memberships;

use App\Http\Controllers\Controller;
use App\Models\Memberships\Member;
use App\Models\Memberships\MemberApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MembershipsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $memberships = MemberApplication::latest()->get();
        return view('memberships.index', compact('memberships'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('memberships.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],
        ]);

        $isDraft = $request->input('submission_action') === 'draft';
        $required = $isDraft ? 'nullable' : 'required';

        $validated = $request->validate([
            // 'submission_action' => ['required', Rule::in(['draft', 'submit'])],
            'application_channel' => ['nullable', Rule::in(['web', 'mobile', 'office', 'agent', 'import'])],

            // Personal details
            'last_name' => [$required, 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'first_name' => [$required, 'string', 'max:255'],
            'date_of_birth' => [$required, 'date', 'before:today'],
            'place_of_birth' => [$required, 'string', 'max:255'],
            'national_id' => [
                $required,
                'string',
                'max:100',
                Rule::unique('member_applications', 'national_id'),
            ],
            'phone' => [$required, 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'current_address' => ['nullable', 'string'],
            'residential_address' => [$required, 'string'],

            // Employment details
            'employer_name' => ['nullable', 'string', 'max:255'],
            'working_station' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employer_address' => ['nullable', 'string'],
            'employer_phone' => ['nullable', 'string', 'max:30'],
            'employment_terms' => [
                'nullable',
                Rule::in(['permanent', 'contract', 'temporary', 'casual', 'seasonal', 'self_employed']),
            ],

            // Business details
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_nature' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_location' => ['nullable', 'string'],

            // Next of kin
            'next_of_kin_name' => [$required, 'string', 'max:255'],
            'next_of_kin_id' => [$required, 'string', 'max:100'],
            'next_of_kin_relationship' => [$required, 'string', 'max:100'],

            // Contributions
            'monthly_contribution' => [$required, 'numeric', 'gt:0', 'regex:/^\d+(\.\d{2})?$/'],
            'contribution_start_date' => [$required, 'date'],

            // Documents
            'national_id_front' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'national_id_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'passport_photo_1' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'passport_photo_2' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'nominee_form' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'applicant_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            // Declaration
            'agreed_to_terms' => $isDraft ? ['nullable', 'boolean'] : ['required', 'accepted'],
            'application_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $applicationNumber = $this->generateApplicationNumber();
        $uploadedPaths = [];

        try {
            $payload = Arr::except($validated, ['submission_action']);

            $documentFields = [
                'national_id_front',
                'national_id_back',
                'passport_photo_1',
                'passport_photo_2',
                'nominee_form',
                'applicant_signature',
            ];

            foreach ($documentFields as $field) {
                unset($payload[$field]);

                if (!$request->hasFile($field)) {
                    continue;
                }

                $directory = "memberships/{$applicationNumber}/{$field}";
                $path = $request->file($field)->store($directory, 'public');

                $payload[$field] = $path;
                $uploadedPaths[] = $path;
            }

            $payload['application_number'] = $applicationNumber;
            $payload['application_channel'] = $validated['application_channel'] ?? 'web';
            $payload['status'] = $isDraft ? 'draft' : 'pending';
            $payload['agreed_to_terms'] = $isDraft ? $request->boolean('agreed_to_terms') : true;
            $payload['application_date'] = $validated['application_date'] ?? ($isDraft ? null : today());

            $payload = $this->normalizeApplicationData($payload);

            $payload = array_merge($payload, [
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'review_notes' => null,
            ]);

            // dd($payload);
            DB::beginTransaction();
            $application = MemberApplication::create($payload);
            DB::commit();

            if ($isDraft) {
                return redirect()
                    ->route('memberships.index', $application)
                    ->with('success', "Draft {$application->application_number} was saved successfully.");
            }

            return redirect()
                ->route('memberships.show', $application)
                ->with('success', "Application {$application->application_number} was submitted successfully.");
        } catch (\Exception $e) {
            if ($uploadedPaths !== []) {
                Storage::disk('public')->delete($uploadedPaths);
            }

            $message = $isDraft
                ? 'The draft could not be saved. Please try again.'
                : 'The application could not be submitted. Please try again.';

            return errorHandler($message, $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function approve(MemberApplication $membership)
    {
        if ($membership->status === 'approved') {
            throw ValidationException::withMessages([
                'application' => 'This application has already been approved.',
            ]);
        }

        $member = DB::transaction(function () use ($membership) {
            $membership->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return Member::create([
                'membership_application_id' => $membership->id,
                'membership_number' => $this->generateMembershipNumber(),

                'first_name' => $membership->first_name,
                'middle_name' => $membership->middle_name,
                'last_name' => $membership->last_name,

                'date_of_birth' => $membership->date_of_birth,
                'place_of_birth' => $membership->place_of_birth,

                'national_id' => $membership->national_id,
                'phone' => $membership->phone,
                'email' => $membership->email ?? null,

                'current_address' => $membership->current_address,
                'residential_address' => $membership->residential_address,

                'admission_date' => now()->toDateString(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),

                'status' => 'active',
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'The application was approved and the member account created.');
    }

    private function normalizeApplicationData(array $payload): array
    {
        $trimFields = [
            'last_name',
            'middle_name',
            'first_name',
            'place_of_birth',
            'employer_name',
            'working_station',
            'designation',
            'business_name',
            'business_nature',
            'next_of_kin_name',
            'next_of_kin_relationship',
        ];

        foreach ($trimFields as $field) {
            if (!array_key_exists($field, $payload) || !is_string($payload[$field])) {
                continue;
            }

            $payload[$field] = trim($payload[$field]) ?: null;
        }

        if (!empty($payload['national_id'])) {
            $payload['national_id'] = strtoupper(trim($payload['national_id']));
        }

        if (!empty($payload['next_of_kin_id'])) {
            $payload['next_of_kin_id'] = strtoupper(trim($payload['next_of_kin_id']));
        }

        if (!empty($payload['email'])) {
            $payload['email'] = strtolower(trim($payload['email']));
        }

        foreach (['phone', 'employer_phone', 'business_phone'] as $field) {
            if (!empty($payload[$field])) {
                $payload[$field] = $this->normalizePhone($payload[$field]);
            }
        }

        return $payload;
    }

    private function generateApplicationNumber(): string
    {
        return sprintf(
            'MAPP-%s-%s',
            now()->format('Y'),
            strtoupper((string) Str::ulid())
        );
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/(?!^\+)[^\d]/', '', trim($phone));
    }

    private function generateMembershipNumber(): string
    {
        $year = now()->format('Y');

        $lastMember = Member::query()
            ->where('membership_number', 'like', "MEM-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $lastSequence = $lastMember
            ? (int) substr($lastMember->membership_number, -6)
            : 0;

        return sprintf(
            'MEM-%s-%06d',
            $year,
            $lastSequence + 1
        );
    }
}
