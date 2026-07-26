<?php

namespace App\Http\Controllers\Memberships;

use App\Http\Controllers\Controller;
use App\Models\Memberships\Member;
use App\Models\Memberships\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect(route('memberships.create'));
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

    public function approve(Membership $membership)
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
