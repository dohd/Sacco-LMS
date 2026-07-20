<?php

namespace App\Models\user_profile\Traits;

trait UserProfileAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            $this->getViewButtonAttribute('user_profiles.show', ''),
            $this->getEditButtonAttribute('user_profiles.edit', ''),
            $this->getDeleteButtonAttribute('user_profiles.destroy', ''),
        );
    }
}
