<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotImportBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'preview_report' => 'array',
        'result' => 'array',
        'committed_at' => 'datetime',
    ];
}
