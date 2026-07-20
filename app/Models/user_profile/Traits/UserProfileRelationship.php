<?php

namespace App\Models\user_profile\Traits;

use App\Models\tenant\Tenant;
use App\Models\User;

trait UserProfileRelationship
{
    public function user_login()
    {
        return $this->belongsTo(User::class, 'rel_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'ins');
    }
}
