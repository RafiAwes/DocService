<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'userRegister']);
Route::post('/login', [AuthController::class, 'login']);

// Email Verification Routes
Route::post('/email/send-verification-code', [EmailVerificationController::class, 'sendVerificationCode']);
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Password Reset Routes
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
