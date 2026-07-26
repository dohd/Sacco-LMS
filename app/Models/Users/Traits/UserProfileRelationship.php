<?php

namespace App\Models\Users\Traits;

use App\Models\Users\User;

trait UserProfileRelationship
{
    public function user_login()
    {
        return $this->belongsTo(User::class, 'rel_id');
    }
}
