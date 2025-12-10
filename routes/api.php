<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\authController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['controller' => authController::class], function () {
    Route::post('/register', 'userRegister');
    Route::post('/login', 'login');

    // Email Verification Routes
    Route::post('/email/send-verification-code', 'sendVerificationCode');
    Route::post('/email/verify', 'verifyRegistration');

    //resend verification code.
    Route::post('/resend/verification-code', 'resendVerificationCode');


    // Password Reset Routes
    Route::post('/password/email', 'sendResetLinkEmail');
    Route::post('/password/reset', 'resetPassword');
});


