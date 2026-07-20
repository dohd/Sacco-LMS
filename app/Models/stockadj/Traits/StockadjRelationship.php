<?php

namespace App\Models\stockadj\Traits;

use App\Models\stockadj\StockadjItem;

trait StockadjRelationship
{
    public function items()
    {
        return $this->hasMany(StockadjItem::class, 'stock_adj_id');
    }
}
