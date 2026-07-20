<?php

namespace App\Models\stockadj\Traits;

trait StockadjAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('invoices.show', 'view-invoice'),
            $this->getEditButtonAttribute('invoices.edit', 'edit-invoice'),
            $this->getDeleteButtonAttribute('invoices.destroy', 'delete-invoice'),
        );
    }
}
