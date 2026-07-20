<?php

namespace App\Models\stockadj;

// use App\Models\invoice\Traits\InvoiceAttribute;
// use App\Models\invoice\Traits\InvoiceRelationship;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Model;

class StockadjItem extends Model
{
    // use ModelTrait, InvoiceAttribute, InvoiceRelationship;   

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'stock_adj_items';

    /**
     * Mass Assignable fields of model
     * @var array
     */
    protected $fillable = [];

    /**
     * Default values for model fields
     * @var array
     */
    protected $attributes = [];

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

    /**
     * Constructor of Model
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            $instance->fill([
                'user_id' => auth()->user()->id,
                // 'ins' => auth()->user()->ins,
            ]);
            return $instance;
        });

        static::addGlobalScope('ins', function ($builder) {
            // $builder->where('ins', auth()->user()->ins);
        });
    }
}
