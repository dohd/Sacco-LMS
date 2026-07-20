<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, HasPermissions, HasApiTokens, HasFactory, Notifiable, ModelTrait, UserAttribute, UserRelationship;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fname',
        'lname',
        'email',
        'role_id',
        'profile_pic',
        'phone',
        'password',
        'is_active',
        'created_by',
        'ins'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Set password attribute.
     *
     * @param [string] $password
     */
    public function setPasswordAttribute($password)
    {
        if ($password) $this->attributes['password'] = Hash::make($password);
    }

    /**
     * Model boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            $instance->created_by = auth()->user()->id;
            $instance->ins = auth()->user()->ins;
            return $instance;
        });

        static::addGlobalScope('ins', function ($builder) {
            // $builder->where('ins', auth()->user()->ins);
        });
    }
}
