<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\authController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ServiceController;
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
    Route::post('/password/email', 'sendResetOTP');
    Route::post('/password/verify-otp', 'verifyOtp');
    Route::post('/password/reset', 'resetPassword');
    Route::post('/password/change', 'changePassword');
});

Route::get('/service/list',[ServiceController::class, 'serviceList']);
Route::get('/service/details/{service}',[ServiceController::class,'serviceDetails']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [authController::class, 'logout']);
    Route::get('list/categories',[CategoryController::class, 'listCategories']);
    Route::get('category/details/{category}',[CategoryController::class, 'categoryDetails']);
});


Route::group (['middleware' => ['auth:sanctum', 'admin'], 'prefix' => 'admin'], function () {
    Route::group(['controller' => CategoryController::class], function () {
        Route::post('add/category','createCategory');
        Route::put('edit/category/{category}','editCategory');
        Route::delete('delete/category/{category}','deleteCategory');
    });

    Route::group(['controller' => ServiceController::class], function () {
        Route::post('create/service','createService');
        Route::put('update/service/{service}','updateService');
        Route::delete('delete/service/{service}','deleteService');
    });

    Route::group(['controller' => QuoteController::class], function () {
        Route::get('quotes/list','listQuotes');
        Route::get('quote/details/{quote}','quoteDetails');
        Route::delete('delete/quote/{quote}','deleteQuote');
    });
});

Route::group(['middleware'=> ['auth:sanctum','user'],'prefix' => 'user'], function () {
    Route::group(['controller' => QuoteController::class], function () {
        Route::post('create/quote','createQuote');
    });
});


