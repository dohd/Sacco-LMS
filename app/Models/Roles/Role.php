<?php

namespace App\Models\Roles;

use App\Models\ModelTrait;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use ModelTrait;

    /**
     * $guarded = ['id']; fields of model
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            null,
            $this->getEditButtonAttribute('roles.edit', null),
            $this->getDeleteButtonAttribute('roles.destroy', null),
        );
    }
}
