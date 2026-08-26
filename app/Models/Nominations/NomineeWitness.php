<?php

namespace App\Models\Nominations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NomineeWitness extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
}
