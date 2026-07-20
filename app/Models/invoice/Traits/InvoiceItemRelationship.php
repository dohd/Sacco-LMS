<?php

namespace App\Models\invoice\Traits;

use App\Models\invoice\Invoice;

trait InvoiceItemRelationship
{
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
