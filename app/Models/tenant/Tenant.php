<?php

namespace App\Models\tenant;

use App\Models\ModelTrait;
use App\Models\tenant\Traits\TenantAttribute;
use App\Models\tenant\Traits\TenantRelationship;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use ModelTrait, TenantAttribute, TenantRelationship;    

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'tenants';

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
            return $instance;
        });
    }
}
