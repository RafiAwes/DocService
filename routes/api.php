<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\Api\authController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PagesController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\Api\MessageController;
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

    // sending message from user
    Route::post('/send/message', [MessageController::class, 'sendMessage']);
    Route::get('/testimonials', [HomeController::class, 'testimonials']);

Route::group(['controller' => ServiceController::class], function () {
    Route::get('/service/list', 'serviceList');
    Route::get('/service/details/{service}', 'serviceDetails');
    Route::get('/services/by-category', 'serviceUnderCategory');
    Route::get('/service/questions/{service}', 'serviceQuestions');
});

Route::apiResource('subscribers', SubscriberController::class)->only(['index', 'store']);

Route::group(['controller' => PagesController::class], function () {
    Route::get('/pages', 'show'); // e.g. /api/pages/terms
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
    // Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead']);

    // Route::post('/pages/save', [PagesController::class, 'savePage']);

    Route::get('category/details/{category}', [CategoryController::class, 'categoryDetails']);
    Route::group(['controller' => CheckoutController::class], function () {

        // Start the process
        Route::post('/checkout/intent', 'paymentIntent');

        // Finish the process (Call this after Stripe frontend succeeds)
        Route::post('/checkout/success', 'paymentSuccess');
    });

    // cart module
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    
    // Note: We use the CartItem ID here (e.g., /cart/update/5)
    Route::put('/cart/update/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/remove/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);
    Route::get('/cart/questions', [CartController::class, 'getCartRequirements']);
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
        Route::get('quotes-custom/list', 'customQuoteList');
        Route::get('quotes-service/list', 'serviceQuoteList');
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

    Route::get('/messages', [MessageController::class, 'index']);



});

Route::group(['middleware' => ['auth:sanctum', 'user'], 'prefix' => 'user'], function () {
    Route::group(['controller' => QuoteController::class], function () {
        Route::post('create/custom/quote', 'createCustomQuote');
        Route::post('create/service/quote', 'createServiceQuote');
    });
    // User Routes
    Route::get('/my-orders', [OrderController::class, 'userOrders']);
    Route::get('/my-orders/{id}', [OrderController::class, 'details']);

    Route::post('rating', [RatingController::class, 'store']);
    Route::get('transactions-history', [OrderController::class, 'transactionsHistory']);
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
