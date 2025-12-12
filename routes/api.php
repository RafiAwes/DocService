<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\authController;
use App\Http\Controllers\Api\CategoryController;

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

Route::group (['middleware' => ['auth:sanctum', 'admin'], 'prefix' => 'admin'], function () {
    Route::post('/logout', [authController::class, 'logout']);

    Route::group(['controller' => CategoryController::class], function () {
        Route::get('/profile', 'adminProfile');
        Route::post('add/category','createCategory');
    });
});


