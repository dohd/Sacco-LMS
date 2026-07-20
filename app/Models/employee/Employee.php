<?php

namespace App\Models\employee;

use App\Models\employee\Traits\EmployeeAttribute;
use App\Models\employee\Traits\EmployeeRelationship;
use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use ModelTrait, EmployeeAttribute, EmployeeRelationship;   

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'employees';

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
