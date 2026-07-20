<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\config\ConfigController;
use App\Http\Controllers\invoices\InvoicesController;
use App\Http\Controllers\Users\UsersController;
use App\Http\Controllers\whatsapp\WhatsAppController;
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

    // Invoices
    Route::get('invoices/datatable', [InvoicesController::class, 'datatable'])->name('invoices.datatable');
    Route::get('invoices/duedate', [InvoicesController::class, 'getDuedate'])->name('invoices.get_duedate');
    Route::get('invoices/currencies', [InvoicesController::class, 'currencies'])->name('invoices.currencies');
    Route::get('invoices/itemlocations', [InvoicesController::class, 'itemLocations'])->name('invoices.itemlocations');
    Route::get('invoices/paymentterms', [InvoicesController::class, 'paymentTerms'])->name('invoices.paymentterms');
    Route::get('invoices/items', [InvoicesController::class, 'searchItems'])->name('invoices.search_items');
    Route::get('invoices/salespersons', [InvoicesController::class, 'searchSalesPersons'])->name('invoices.search_salespersons');
    Route::get('invoices/contacts', [InvoicesController::class, 'searchContacts'])->name('invoices.search_contacts');
    Route::post('invoices/update_status', [InvoicesController::class, 'updateStatus'])->name('invoices.update_status');
    Route::resource('invoices', InvoicesController::class);

    // whatsapp
    Route::get('whatsapp/overview', [WhatsAppController::class, 'overview'])->name('whatsapp.overview');
    Route::get('whatsapp/customer_rating', [WhatsAppController::class, 'customerRating'])->name('whatsapp.customer_rating');
    Route::get('whatsapp/message_log', [WhatsAppController::class, 'messageLog'])->name('whatsapp.message_log');
    Route::get('whatsapp/message_log_datatable', [WhatsAppController::class, 'messageLogDatatable'])->name('whatsapp.message_log_datatable');
    Route::post('whatsapp/customer_rating_datatable', [WhatsAppController::class, 'customerRatingDatatable'])->name('whatsapp.customer_rating_datatable');    

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
