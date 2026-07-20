<?php

namespace App\Models;

trait UserAttribute
{
    /**
     * Action Button Attribute to show in grid
     * @return string
     */
    public function getActionButtonsAttribute()
    {
        return $this->getButtonWrapperAttribute(
            null,
            $this->getEditButtonAttribute('user_profiles.edit', 'edit-user'),
            $this->getDeleteButtonAttribute('user_profiles.destroy', 'delete-user'),
        );
    }

    /**
     * Name Attribute
     * @return string
     */
    public function getNameAttribute()
    {
        return "{$this->fname} {$this->lname}";
    }

    /**
     * Is active status
     * @return string
     */
    public function getIsActiveStatusAttribute()
    {
        return $this->is_active? 'Active' : 'Inactive';
    }

    /**
     * Is active status budge
     * @return string
     */
    public function getIsActiveStatusBudgeAttribute()
    {
        return '<span class="badge bg-'. ($this->is_active? 'success' : 'secondary') .' modal-btn" style="cursor:pointer;" role="button" data-bs-toggle="modal" data-bs-target="#status_modal" data-url="'. route('user_profiles.update', $this) .'">'
        . $this->is_active_status . '<i class="bi bi-caret-down-fill"></i></span>';
    }
}
