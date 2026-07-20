<?php

namespace App\Models\whatsapp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRating extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'customer_ratings';

    /**
     * Dates
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Guarded fields of model
     * @var array
     */
    protected $guarded = [
        'id'
    ];
}
