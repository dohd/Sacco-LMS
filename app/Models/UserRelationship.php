<?php

namespace App\Models;

use App\Models\tenant\Tenant;

trait UserRelationship
{
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'ins');
    }
}
