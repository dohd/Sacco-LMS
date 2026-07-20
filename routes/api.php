<?php

use App\Http\Controllers\whatsapp\WhatsAppController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('webhook/whatsapp/payment_receipt_notice', [WhatsAppController::class, 'paymentReceiptNotice'])->name('whatsapp.payment_receipt_notice');
Route::post('webhook/whatsapp/feedback_message', [WhatsAppController::class, 'feedbackMessage'])->name('whatsapp.feedback_message');

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required',
        'password' => 'required',
    ]);
    $user = User::where('email', $request->email)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
    }
    
    return response()->json(['access_token' => $user->createToken(config('app.name'))->plainTextToken]);
});

Route::group(['middleware' => 'auth:sanctum'], function() {
    // medical insurers
    Route::get('medical_insurers', function(Request $request) {
        return response()->json([]);
    });    
});
