<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\authController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PagesController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\AdminDashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['controller' => authController::class], function () {
    Route::post('/register', 'userRegister');
    Route::post('/login', 'login');

    // Email Verification Routes
    Route::post('/email/send-verification-code', 'sendVerificationCode');
    Route::post('/email/verify', 'verifyRegistration');

    // resend verification code.
    Route::post('/resend/verification-code', 'resendVerificationCode');

    // Password Reset Routes
    Route::post('/password/email', 'sendResetOTP');
    Route::post('/password/verify-otp', 'verifyOtp');
    Route::post('/password/reset', 'resetPassword');
    Route::post('/password/change', 'changePassword');
});

Route::group(['controller' => ServiceController::class], function () {
    Route::get('/service/list', 'serviceList');
    Route::get('/service/details/{service}', 'serviceDetails');
});

Route::group(['controller' => PagesController::class], function () {
    Route::get('/pages/{key}', 'show'); // e.g. /api/pages/terms
    Route::get('/faqs', 'index');
});


Route::group(['controller' => CategoryController::class], function () {
    Route::get('list/categories', 'listCategories');
});


//google authentication
Route::get('/auth/google/redirect/user', [SocialAuthController::class, 'redirectToGoogle'])->middleware(['web']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);

// stripe payment
// Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [authController::class, 'logout']);
   

    Route::group(['controller' => ProfileController::class], function () {
        // updating profile
        Route::post('/profile/update', 'updateProfile');
        // updating profile image
        Route::post('/profile/update-picture', 'updateProfilePicture');
        // get profile details
        Route::get('/profile', 'viewProfile');
    });

    // Notification Center
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Route::post('/pages/save', [PagesController::class, 'savePage']);
    
    Route::get('category/details/{category}', [CategoryController::class, 'categoryDetails']);
    Route::group(['controller' => CheckoutController::class], function () {

        // Start the process
        Route::post('/checkout/intent', 'paymentIntent');

        // Finish the process (Call this after Stripe frontend succeeds)
        Route::post('/checkout/success', 'paymentSuccess');
    });
});

Route::group(['middleware' => ['auth:sanctum', 'admin'], 'prefix' => 'admin'], function () {

    Route::group(['controller' => AdminDashboardController::class], function () {
        Route::get('dashboard', 'index');
        Route::get('dashboard/chart-data', 'getChartData');
    });
    
    
    Route::group(['controller' => CategoryController::class], function () {
        Route::post('add/category', 'createCategory');
        Route::put('edit/category/{category}', 'editCategory');
        Route::delete('delete/category/{category}', 'deleteCategory');
    });

    Route::group(['controller' => ServiceController::class], function () {
        Route::post('create/service', 'createService');
        Route::put('update/service/{service}', 'updateService');
        Route::delete('delete/service/{service}', 'deleteService');
    });

    Route::group(['controller' => QuoteController::class], function () {
        Route::get('quotes/list', 'listQuotes');
        Route::get('quote/details/{quote}', 'quoteDetails');
        Route::delete('delete/quote/{quote}', 'deleteQuote');
    });

    Route::group(['controller' => PagesController::class], function () {

        // Manage Pages (Terms, Privacy)
        Route::post('/pages/save', 'savePage');
        // Manage FAQs
        Route::post('/faqs', 'store');
        Route::put('/faqs/{id}', 'update');
        Route::delete('/faqs/{id}', 'destroy');

    });

    Route::group(['controller' => OrderController::class], function () {
        Route::get('/orders', 'adminOrders');
        Route::get('/orders/{id}', 'details');
        Route::post('/orders/{orderId}/complete', 'completeOrder');
        Route::get('/completed-orders', 'completedOrders');
    });

    
    
});

Route::group(['middleware' => ['auth:sanctum', 'user'], 'prefix' => 'user'], function () {
    Route::group(['controller' => QuoteController::class], function () {
        Route::post('create/quote', 'createQuote');
    });
    // User Routes
    Route::get('/my-orders', [OrderController::class, 'userOrders']);
    Route::get('/my-orders/{id}', [OrderController::class, 'details']);
});

// News routes
// public: list and details
Route::get('/news', [NewsController::class, 'listNews']);
Route::get('/news/{news}', [NewsController::class, 'newsDetails']);

// admin: create, update, delete
Route::group(['middleware' => ['auth:sanctum', 'admin'], 'prefix' => 'admin'], function () {
    Route::post('create/news', [NewsController::class, 'createNews']);
    Route::put('update/news/{news}', [NewsController::class, 'updateNews']);
    Route::delete('delete/news/{news}', [NewsController::class, 'deleteNews']);
});
