<?php

namespace App\Models\invoice\Traits;

use App\Models\invoice\InvoiceItem;
use App\Models\stockadj\Stockadj;
use App\Models\stockadj\StockadjItem;

trait InvoiceRelationship
{
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockAdj()
    {
        return $this->hasOne(Stockadj::class);
    }

    public function stockAdjItems()
    {
        return $this->hasManyThrough(StockadjItem::class, Stockadj::class, 'invoice_id', 'stock_adj_id', 'id', 'id');
    }
}
