<?php

namespace App\Models\invoice\Traits;

trait InvoiceAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            null, // $this->getViewButtonAttribute('invoices.show', 'view-invoice'),
            $this->getEditButtonAttribute('invoices.edit', 'edit-invoice'),
            $this->getDeleteButtonAttribute('invoices.destroy', 'delete-invoice'),
        );
    }
}
