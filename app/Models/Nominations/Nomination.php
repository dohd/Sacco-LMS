<?php

namespace App\Models\Nominations;

use App\Models\Memberships\Member;
use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nomination extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // fetch only member records
        static::addGlobalScope('member_id', function($query) {
            if (Auth::user() && Auth::user()->hasRoleCode('member')) {
                $member = optional(Auth()->user()->member);
                $query->where('member_id', $member->id);
            }
        });
    }

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('nominations.show', 'view-nominations'),
            $this->getEditButtonAttribute('nominations.edit', 'edit-nominations'),
            null,
        );
    }


    /**
     * Relationships
     * */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function nominees()
    {
        return $this->hasMany(Nominee::class);
    }

    public function witnesses()
    {
        return $this->hasMany(NomineeWitness::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
