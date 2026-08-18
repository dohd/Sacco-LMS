<?php

namespace App\Models\Memberships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberApplication extends Model
{
    use HasFactory;

    protected $fillable = [];

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
}
