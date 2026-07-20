<?php

namespace App\Models\role\Traits;

trait RoleAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            null,
            $this->getEditButtonAttribute('roles.edit', 'edit-role'),
            $this->getDeleteButtonAttribute('roles.destroy', 'delete-role'),
        );
    }
}
