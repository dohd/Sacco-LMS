<?php

namespace App\Models\employee\Traits;

trait EmployeeAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('employees.show', 'view-employee'),
            $this->getEditButtonAttribute('employees.edit', 'edit-employee'),
            $this->getDeleteButtonAttribute('employees.destroy', 'delete-employee'),
        );
    }
}
