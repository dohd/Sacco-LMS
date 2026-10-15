<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function requireSaccoPermission(string $permission): void
    {
        if (!auth()->check()) {
            abort(401);
        }

        $isAdministrator = auth()->user()->roles()->where(function ($query) {
            $query->whereIn('name', ['sacco_admin', 'super_admin', 'administrator'])
                ->orWhereIn('key_name', ['sacco_admin', 'super_admin', 'administrator']);
        })->exists();

        if (!$isAdministrator && !auth()->user()->can($permission)) {
            abort(403);
        }
    }
}
