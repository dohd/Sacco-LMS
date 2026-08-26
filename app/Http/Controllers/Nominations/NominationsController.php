<?php

namespace App\Http\Controllers\Nominations;

use App\Http\Controllers\Controller;
use App\Models\Memberships\Member;
use App\Models\Nominations\Nomination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class NominationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $nominations = Nomination::latest()->get();
        return view('nominations.index', compact('nominations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->session()->forget(['_old_input', 'errors']);

        $members = Member::all();
        return view('nominations.create', compact('members'));
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
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'special_instructions' => ['nullable', 'string'],
            'declaration_date' => ['required', 'date', 'before_or_equal:today'],
            'member_signature' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'nominees' => ['required', 'array', 'min:1', 'max:5'],
            'nominees.*.full_name' => ['required', 'string', 'max:255'],
            'nominees.*.national_id' => ['required', 'string', 'max:100', 'distinct'],
            'nominees.*.postal_address' => ['required', 'string', 'max:255'],
            'nominees.*.phone' => ['required', 'string', 'max:50'],
            'nominees.*.email' => ['nullable', 'email', 'max:255'],
            'nominees.*.relationship' => ['required', 'string', 'max:100'],
            'nominees.*.percentage' => ['required', 'numeric', 'gt:0', 'max:100'],
            'nominees.*.is_minor' => ['required', 'boolean'],
            'nominees.*.date_of_birth' => ['nullable', 'required_if:nominees.*.is_minor,1', 'date', 'before:today'],

            'witnesses' => ['required', 'array', 'size:2'],
            'witnesses.*.full_name' => ['required', 'string', 'max:255'],
            'witnesses.*.national_id' => ['required', 'string', 'max:100', 'distinct'],
            'witnesses.*.signature' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'confirmed_declaration' => ['accepted'],
        ]); 

        $uploadedPaths = []; 

        try {
            $percentageTotal = collect($validated['nominees'])
                ->sum(fn (array $nominee) => (float) $nominee['percentage']);

            if (abs($percentageTotal - 100) > 0.001) {
                throw ValidationException::withMessages([
                    'nominees' => 'The total nominee allocation must equal exactly 100%.',
                ]);
            }

            DB::transaction(function () use ($request, $validated) {
                /*
                 * Deactivate previous nominations while preserving them
                 * for audit and historical reporting.
                 */
                Nomination::where('member_id', $validated['member_id'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $memberSignature = $request
                    ->file('member_signature')
                    ->store('nomination-signatures/members', 'public');
                $uploadedPaths[] = $memberSignature;   

                $nomination = Nomination::create([
                    'member_id' => $validated['member_id'],
                    'special_instructions' => $validated['special_instructions'] ?? null,
                    'declaration_date' => databaseDate($validated['declaration_date']),
                    'member_signature' => $memberSignature,
                    'status' => 'pending',
                    'is_active' => true,
                    'confirmed_declaration' => boolval($validated['confirmed_declaration'] ?? null),
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                ]);

                foreach ($validated['nominees'] as $nomineeData) {
                    $nomination->nominees()->create([
                        'member_id' => $validated['member_id'],
                        'full_name' => $nomineeData['full_name'],
                        'national_id' => $nomineeData['national_id'],
                        'postal_address' => $nomineeData['postal_address'],
                        'phone' => $nomineeData['phone'],
                        'email' => $nomineeData['email'] ?? null,
                        'relationship' => $nomineeData['relationship'],
                        'percentage' => $nomineeData['percentage'],
                        'is_minor' => $nomineeData['is_minor'],
                        'date_of_birth' => isset($nomineeData['date_of_birth'])? databaseDate($nomineeData['date_of_birth']) : null,
                    ]);
                }

                foreach ($validated['witnesses'] as $index => $witnessData) {
                    $signature = $request
                        ->file("witnesses.$index.signature")
                        ->store('nomination-signatures/witnesses', 'public');
                    $uploadedPaths[] = $signature;

                    $nomination->witnesses()->create([
                        'member_id' => $validated['member_id'],
                        'full_name' => $witnessData['full_name'],
                        'national_id' => $witnessData['national_id'],
                        'signature' => $signature,
                    ]);
                }
            });

            return redirect()
                ->route('nominations.index')
                ->with('success', 'The nomination was submitted successfully.');
        } catch (\Exception $e) {
            if ($uploadedPaths !== []) {
                Storage::disk('public')->delete($uploadedPaths);
            }

            return errorHandler("The nomination could not be saved. Please try again.", $e);
        }      
    }    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Nomination $nomination)
    {
        return view('nominations.view', compact('nomination'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Nomination $nomination, Request $request)
    {
        // Inject the key-value pair into the request payload
        $payload = $nomination->toArray();
        $payload['nominees'] = $nomination->nominees->toArray();
        $payload['witnesses'] = $nomination->witnesses->toArray();
        $request->merge($payload);

        // Flash the modified request to the old input session store
        $request->flash();

        $members = Member::all();
        return view('nominations.edit', compact('nomination', 'members'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Nomination $nomination)
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'special_instructions' => ['nullable', 'string'],
            'declaration_date' => ['required', 'date', 'before_or_equal:today'],
            'member_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'nominees' => ['required', 'array', 'min:1', 'max:5'],
            'nominees.*.full_name' => ['required', 'string', 'max:255'],
            'nominees.*.national_id' => ['required', 'string', 'max:100', 'distinct'],
            'nominees.*.postal_address' => ['required', 'string', 'max:255'],
            'nominees.*.phone' => ['required', 'string', 'max:50'],
            'nominees.*.email' => ['nullable', 'email', 'max:255'],
            'nominees.*.relationship' => ['required', 'string', 'max:100'],
            'nominees.*.percentage' => ['required', 'numeric', 'gt:0', 'max:100'],
            'nominees.*.is_minor' => ['required', 'boolean'],
            'nominees.*.date_of_birth' => ['nullable', 'required_if:nominees.*.is_minor,1', 'date', 'before:today'],

            'witnesses' => ['required', 'array', 'size:2'],
            'witnesses.*.full_name' => ['required', 'string', 'max:255'],
            'witnesses.*.national_id' => ['required', 'string', 'max:100', 'distinct'],
            'witnesses.*.signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'confirmed_declaration' => ['accepted'],
        ]); 

        $uploadedPaths = []; 

        try {
            $percentageTotal = collect($validated['nominees'])
                ->sum(fn (array $nominee) => (float) $nominee['percentage']);

            if (abs($percentageTotal - 100) > 0.001) {
                throw ValidationException::withMessages([
                    'nominees' => 'The total nominee allocation must equal exactly 100%.',
                ]);
            }

            DB::transaction(function () use ($request, $validated, $nomination) {

                if (request('member_signature')) {
                    $memberSignature = $request
                        ->file('member_signature')
                        ->store('nomination-signatures/members', 'public');
                    $uploadedPaths[] = $memberSignature;                    
                }

                if (isset($memberSignature)) {
                    $nomination->update(['member_signature' => $memberSignature]);   
                } elseif (empty($nomination->member_signature)) {
                    throw ValidationException::withMessages(['member_signature' => 'member signature is required']);
                }

                $nomination->update([
                    'special_instructions' => $validated['special_instructions'] ?? null,
                    'declaration_date' => databaseDate($validated['declaration_date']),
                    'confirmed_declaration' => boolval($validated['confirmed_declaration'] ?? null),
                    'updated_by' => auth()->id(),                    
                ]);

                $natIds = collect($validated['nominees'])->pluck('national_id')->filter()->toArray();
                $nomination->nominees()->whereNotIn('national_id', $natIds)->delete();
                foreach ($validated['nominees'] as $nomineeData) {
                    $nomination->nominees()->updateOrCreate(
                        [
                            'national_id' => $nomineeData['national_id']
                        ],
                        [
                            'member_id' => $validated['member_id'],
                            'full_name' => $nomineeData['full_name'],
                            'national_id' => $nomineeData['national_id'],
                            'postal_address' => $nomineeData['postal_address'],
                            'phone' => $nomineeData['phone'],
                            'email' => $nomineeData['email'] ?? null,
                            'relationship' => $nomineeData['relationship'],
                            'percentage' => $nomineeData['percentage'],
                            'is_minor' => $nomineeData['is_minor'],
                            'date_of_birth' => isset($nomineeData['date_of_birth'])? databaseDate($nomineeData['date_of_birth']) : null,
                        ]
                    );
                }

                $natIds = collect($validated['witnesses'])->pluck('national_id')->filter()->toArray();
                $nomination->witnesses()->whereNotIn('national_id', $natIds)->delete();
                foreach ($validated['witnesses'] as $index => $witnessData) {
                    if (request("witnesses.$index.signature")) {
                        $signature = $request
                            ->file("witnesses.$index.signature")
                            ->store('nomination-signatures/witnesses', 'public');
                        $uploadedPaths[] = $signature;                        
                    }

                    $witness = $nomination->witnesses()
                        ->where('national_id', $witnessData['national_id'])
                        ->first();

                    $nomination->witnesses()->updateOrCreate([
                            'national_id' => $witnessData['national_id']
                        ],
                        [
                            'member_id' => $validated['member_id'],
                            'full_name' => $witnessData['full_name'],
                            'national_id' => $witnessData['national_id'],                            
                        ]
                    );
                    
                    if (isset($signature) && $witness) {
                        $witness->update(['signature' => $signature]);
                    } elseif (empty($witness->signature)) {
                        throw ValidationException::withMessages(['signature' => 'witness '. strval($index+1) .' signature is required']);
                    }
                }
            });

            return redirect()
                ->route('nominations.show', $nomination)
                ->with('success', 'The nomination was updated successfully.');
        } catch (\Exception $e) {
            if ($uploadedPaths !== []) {
                Storage::disk('public')->delete($uploadedPaths);
            }

            return errorHandler("The nomination could not be updated. Please try again.", $e);
        }  
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

    public function approve(Nomination $nomination)
    {
        if ($nomination->status === 'approved') {
            throw ValidationException::withMessages([
                'application' => 'This nomination has already been approved.',
            ]);
        }

        DB::transaction(
            fn() => $nomination->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])
        );

        return redirect()
            ->route('nominations.show', $nomination)
            ->with('success', 'The nomination has been approved successfully.');
    }

    public function reject(Nomination $nomination)
    {
        DB::transaction(
            fn() => $nomination->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $nomination->rejection_reason,
            ])
        );

        return redirect()
            ->route('nominations.show', $nomination)
            ->with('success', 'The nomination has been rejected successfully.');
    }
}
