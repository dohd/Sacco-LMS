<?php

namespace App\Http\Controllers\Nominations;

use App\Http\Controllers\Controller;
use App\Models\Memberships\MemberApplication;
use App\Models\Nominations\Nomination;
use Illuminate\Http\Request;

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
        $member = MemberApplication::make();
        return view('nominations.create', compact('member'));
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
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'special_instructions' => [
                'nullable',
                'string',
            ],

            'declaration_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'member_signature' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'nominees' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            'nominees.*.full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'nominees.*.national_id' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'nominees.*.postal_address' => [
                'required',
                'string',
                'max:255',
            ],

            'nominees.*.phone' => [
                'required',
                'string',
                'max:50',
            ],

            'nominees.*.email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'nominees.*.relationship' => [
                'required',
                'string',
                'max:100',
            ],

            'nominees.*.percentage' => [
                'required',
                'numeric',
                'gt:0',
                'max:100',
            ],

            'nominees.*.is_minor' => [
                'required',
                'boolean',
            ],

            'nominees.*.date_of_birth' => [
                'nullable',
                'required_if:nominees.*.is_minor,1',
                'date',
                'before:today',
            ],

            'witnesses' => [
                'required',
                'array',
                'size:2',
            ],

            'witnesses.*.full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'witnesses.*.national_id' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'witnesses.*.signature' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],

            'confirmed_declaration' => [
                'accepted',
            ],
        ]);

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
            MemberNomination::where('member_id', $validated['member_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $memberSignature = $request
                ->file('member_signature')
                ->store('nomination-signatures/members', 'public');

            $nomination = MemberNomination::create([
                'member_id' => $validated['member_id'],
                'special_instructions' => $validated['special_instructions'] ?? null,
                'declaration_date' => $validated['declaration_date'],
                'member_signature' => $memberSignature,
                'status' => 'pending',
                'is_active' => true,
            ]);

            foreach ($validated['nominees'] as $nomineeData) {
                $nomination->nominees()->create([
                    'full_name' => $nomineeData['full_name'],
                    'national_id' => $nomineeData['national_id'],
                    'postal_address' => $nomineeData['postal_address'],
                    'phone' => $nomineeData['phone'],
                    'email' => $nomineeData['email'] ?? null,
                    'relationship' => $nomineeData['relationship'],
                    'percentage' => $nomineeData['percentage'],
                    'is_minor' => $nomineeData['is_minor'],
                    'date_of_birth' => $nomineeData['date_of_birth'] ?? null,
                ]);
            }

            foreach ($validated['witnesses'] as $index => $witnessData) {
                $signature = $request
                    ->file("witnesses.$index.signature")
                    ->store('nomination-signatures/witnesses', 'public');

                $nomination->witnesses()->create([
                    'full_name' => $witnessData['full_name'],
                    'national_id' => $witnessData['national_id'],
                    'signature' => $signature,
                ]);
            }
        });

        return redirect()
            ->route('nominations.index')
            ->with('success', 'The nomination was submitted successfully.');
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
}
