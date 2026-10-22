<?php

namespace App\Models\Memberships;

use App\Models\ModelTrait;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberApplication extends Model
{
    use HasFactory, ModelTrait;

    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'contribution_start_date' => 'date',
        'application_date' => 'date',
        'agreed_to_terms' => 'boolean',
        'monthly_contribution' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // fetch only member records
        static::addGlobalScope('member_id', function($query) {
            if (Auth::user() && Auth::user()->hasRoleCode('member')) {
                $member = optional(Auth()->user()->member);
                $query->whereHas('member', fn($subq) => $subq->where('members.id', $member->id));                
            }
        });
    }

    /**
     * Getters
     * */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('memberships.show', 'view-own-membership'),
            $this->getEditButtonAttribute('memberships.edit', 'edit-own-membership'),
            null,
        );
    }

    public function getMemberNameAttribute()
    {
        return $this->middle_name? "{$this->first_name} {$this->middle_name} {$this->last_name}" : "{$this->first_name} {$this->last_name}";
    }

    public function getKycStatusAttribute()
    {
        return in_array($this->status, ['draft', 'pending'])? 'pending' : 'verified';
    }


    /**
     * Relationships
     * */
    public function member()
    {
        return $this->hasOne(Member::class, 'member_application_id');
    }
    
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
