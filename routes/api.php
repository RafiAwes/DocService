<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AdminDashboardController, CartController, CategoryController, CheckoutController, DeliveryController, HomeController, MessageController, NewsController, NotificationController, OrderController, PagesController, ProfileController, QuoteController, RatingController, ServiceController, SocialAuthController, StripeWebhookController, SubscriberController, authController};

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
    Route::get('/deliveries', [DeliveryController::class, 'list']);

Route::group(['controller' => ServiceController::class], function () {
    Route::get('/service/list', 'serviceList');
    Route::get('/service/details/{service}', 'serviceDetails');
    Route::get('/services/by-category', 'serviceUnderCategory');
    Route::get('/service/questions/{service}', 'serviceQuestions');
});

Route::apiResource('subscribers', SubscriberController::class)->only(['index', 'store']);

Route::group(['controller' => PagesController::class], function () {
    Route::get('/pages', 'show'); // supports /api/pages?key=terms
    Route::get('/pages/{key}', 'show'); // also supports /api/pages/terms
    Route::get('/faqs', 'index');
});


Route::group(['controller' => CategoryController::class], function () {
    Route::get('list/categories', 'listCategories');
});

//google authentication
Route::get('/auth/google/redirect/user', [SocialAuthController::class, 'redirectToGoogle'])->middleware(['web']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);

// ratings and reviews
Route::get('/rating/list', [RatingController::class, 'reviewList']);

// stripe payment
// Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [authController::class, 'logout']);

    // Rating routes
    Route::post('/rating', [RatingController::class, 'store']);
   

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

    // Delivery options
    Route::get('/deliveries', [DeliveryController::class, 'list']);
});

Route::group(['middleware' => ['auth:sanctum', 'admin'], 'prefix' => 'admin'], function () {

    Route::group(['controller' => AdminDashboardController::class], function () {
        Route::get('dashboard', 'index');
        Route::get('dashboard/chart-data', 'getChartData');
        Route::get('users/list', 'getUserList');
        Route::post('users/ban/{user}', 'banUser');
    });


    Route::group(['controller' => CategoryController::class], function () {
        Route::post('add/category', 'createCategory');
        Route::put('edit/category/{category}', 'editCategory');
        Route::delete('delete/category/{category}', 'deleteCategory');
    });

    Route::group(['controller' => ServiceController::class], function () {
        // Old endpoints (kept for backward compatibility)
        Route::post('create/service', 'createService'); // to be deprecated
        Route::put('update/service/{service}', 'updateService'); // to be deprecated
        Route::delete('delete/service/{service}', 'deleteService');

        // New granular endpoints for CREATE
        Route::post('service/create-base', 'createBaseService');
        Route::post('service/{service}/add-included-services', 'addIncludedServices');
        Route::post('service/{service}/add-processing-times', 'addProcessingTimes');
        Route::post('service/{service}/add-questions', 'addQuestions');
        Route::post('service/{service}/add-required-documents', 'addRequiredDocuments');

        // New granular endpoints for UPDATE
        Route::put('service/{service}/update-base', 'updateBaseService');
        Route::put('service/{service}/update-included-services', 'updateIncludedServices');
        Route::put('service/{service}/update-processing-times', 'updateProcessingTimes');
        Route::put('service/{service}/update-questions', 'updateQuestions');
        Route::put('service/{service}/update-required-documents', 'updateRequiredDocuments');
    });

    Route::group(['controller' => QuoteController::class], function () {
        Route::get('quotes-custom/list', 'customQuoteList');
        Route::put('quote/reply/{quote}', 'replyToQuote');
        Route::get('quotes-service/list', 'serviceQuoteList');
        Route::put('quotes-service/reply/{quote}', 'replyToServiceQuote');
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

    // Delivery management
    Route::group(['controller' => DeliveryController::class], function () {
        Route::post('/deliveries', 'store');
        Route::put('/deliveries/{id}', 'update');
        Route::delete('/deliveries/{id}', 'destroy');
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

    Route::post('/rating', [RatingController::class, 'store']);
    Route::get('/rating/list', [RatingController::class, 'reviewList']);
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
