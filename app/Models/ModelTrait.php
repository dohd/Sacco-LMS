<?php

namespace App\Models;

trait ModelTrait
{
    /**
     * View Button Attribute
     * @return string
     */
    public function getViewButtonAttribute($route, $permission='')
    {
        if ($this->userHasPermission($permission))
            return '<a class="dropdown-item pt-1 pb-1 view" href="'. route($route, $this). '"><i class="bi bi-eye-fill"></i>View</a>';
    }

    /**
     * Edit Button Attribute
     * @return string
     */
    public function getEditButtonAttribute($route, $permission='')
    {
        if ($this->userHasPermission($permission))
            return '<a class="dropdown-item pt-1 pb-1 edit" href="'. route($route, $this) . '"><i class="bi bi-pencil-square"></i>Edit</a>';
    }

    /**
     * Delete Button Attribute
     * @return string
     */
    public function getDeleteButtonAttribute($route, $permission='')
    {
        if ($this->userHasPermission($permission)) {
            return '<a class="dropdown-item pt-1 pb-1 destroy" href="javascript:">
                    <i class="bi bi-trash text-danger icon-xs"></i>Delete
                    <form action="'. route($route, $this) .'" method="POST">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="'. csrf_token() .'">
                    </form>
                </a>';
        }
    }

    /**
     * Buttons Wrapper Container
     * 
     * @return string
     */
    public function getButtonWrapperAttribute($view, $edit, $delete)
    {
        $li = array_reduce([$view, $edit, $delete], function ($init, $curr) {
            return $curr? $init . "<li>{$curr}</li>" : $init;
        }, '');
        
        return '<div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Action
            </button>
            <ul class="dropdown-menu">'. $li .'</ul>
        </div>
        ';
    }

    /**
     * Validate Permission
     */
    public function userHasPermission($permission='')
    {
        try {
            // $role = auth()->user()->roles()->first();
            // return $role->hasPermissionTo($permission);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
