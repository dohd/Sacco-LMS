<?php

namespace App\Models\tenant\Traits;

trait TenantAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('tenants.show', ''),
            $this->getEditButtonAttribute('tenants.edit', ''),
            $this->getDeleteButtonAttribute('tenants.destroy', ''),
        );
    }
}
