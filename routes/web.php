<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\config\ConfigController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoanApplications\LoanApplicationsController;
use App\Http\Controllers\LoanDisbursements\LoanDisbursementsController;
use App\Http\Controllers\LoanProducts\LoanProductsController;
use App\Http\Controllers\LoanRepayments\LoanRepaymentsController;
use App\Http\Controllers\Memberships\MembershipsController;
use App\Http\Controllers\Nominations\NominationsController;
use App\Http\Controllers\SavingsAccounts\SavingsAccountsController;
use App\Http\Controllers\SavingsProducts\SavingsProductsController;
use App\Http\Controllers\SavingsTransactions\SavingsTransactionsController;
use App\Http\Controllers\SavingsWithdrawals\SavingsWithdrawalsController;
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

    // Memberships
    Route::resource('memberships', MembershipsController::class);
    Route::patch('memberships/approve/{membership}', [MembershipsController::class, 'approve'])->name('memberships.approve');
    Route::patch('memberships/reject/{membership}', [MembershipsController::class, 'reject'])->name('memberships.reject');
    Route::patch('memberships/review/{membership}', [MembershipsController::class, 'review'])->name('memberships.review');

    Route::resource('nominations', NominationsController::class);

    // Loans
    Route::resource('loan_products', LoanProductsController::class);
    Route::resource('loan_applications', LoanApplicationsController::class);
    Route::resource('loan_disbursements', LoanDisbursementsController::class);
    Route::resource('loan_repayments', LoanRepaymentsController::class);

    // Savings
    Route::resource('savings_products', SavingsProductsController::class);
    Route::resource('savings_accounts', SavingsAccountsController::class);
    Route::resource('savings_transactions', SavingsTransactionsController::class);
    Route::resource('savings_withdrawals', SavingsWithdrawalsController::class);

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
