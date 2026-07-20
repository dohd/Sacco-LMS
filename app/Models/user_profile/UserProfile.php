<?php

namespace App\Models\user_profile;

use App\Models\ModelTrait;
use App\Models\user_profile\Traits\UserProfileAttribute;
use App\Models\user_profile\Traits\UserProfileRelationship;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use ModelTrait, UserProfileAttribute, UserProfileRelationship;    

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'user_profiles';

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
            $instance->user_id = auth()->user()->id;
            $instance->ins = auth()->user()->ins;
            return $instance;
        });

        static::addGlobalScope('ins', function ($builder) {
            $builder->where('ins', '=', auth()->user()->ins);
        });
    }
}
