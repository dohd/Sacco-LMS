<?php

namespace App\Models\tenant\Traits;

use App\Models\User;

trait TenantRelationship
{
    public function users()
    {
        return $this->hasMany(User::class, 'ins');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'ins');
    }
}
