<?php

namespace App\Models\Memberships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Getters
     * */
    public function getFullNameAttribute()
    {
        return $this->middle_name? 
            "{$this->first_name} {$this->middle_name} {$this->last_name}" : 
            "{$this->first_name} {$this->last_name}";
    }
}
