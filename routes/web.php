<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\config\ConfigController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoanApplications\LoanApplicationsController;
use App\Http\Controllers\Memberships\MembershipsController;
use App\Http\Controllers\Nominations\NominationsController;
use App\Http\Controllers\Users\UsersController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication
Auth::routes();
Route::get('/', [LoginController::class, 'index']);
Route::get('logout', [LoginController::class, 'logout']);
Route::group(['middleware' => 'auth'], function() {
    // Dashboard
    Route::get('dashboard', [HomeController::class, 'index'])->name('home');

    Route::resource('memberships', MembershipsController::class);
    Route::resource('nominations', NominationsController::class);
    Route::resource('loan_applications', LoanApplicationsController::class);
  
    // User Profiles
    Route::post('users/delete_profile_pic/{user}', [UsersController::class, 'delete_profile_pic'])->name('users.delete_profile_pic');
    Route::post('users/update_active_profile/{user}', [UsersController::class, 'update_active_profile'])->name('users.update_active_profile');
    Route::get('users/active_profile', [UsersController::class, 'active_profile'])->name('users.active_profile');
    Route::resource('users', UsersController::class);    

    // Configuration
    Route::get('settings', [ConfigController::class, 'create'])->name('settings.create');
    Route::post('settings/update', [ConfigController::class, 'update'])->name('settings.update');
    Route::get('clear-cache', [ConfigController::class, 'clear_cache'])->name('config.clear_cache');
    Route::get('site-down', [ConfigController::class, 'site_down'])->name('config.site_down');
});
