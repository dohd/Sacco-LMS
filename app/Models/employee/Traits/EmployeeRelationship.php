<?php

namespace App\Models\employee\Traits;

use App\Models\employee\EmployeeDoc;

trait EmployeeRelationship
{
    public function documents()
    {
        return $this->hasMany(EmployeeDoc::class);
    }
}
