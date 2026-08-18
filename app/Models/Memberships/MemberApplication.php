<?php

namespace App\Models\Memberships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberApplication extends Model
{
    use HasFactory;

    protected $guarded = [];

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

    public function getMemberNameAttribute()
    {
        return $this->middle_name? 
            "{$this->first_name} {$this->middle_name} {$this->last_name}" : 
            "{$this->first_name} {$this->last_name}";
    }

    public function getKycStatusAttribute()
    {
        return in_array($this->status, ['draft', 'pending'])? 'pending' : 'verified';
    }
}
