<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Notifications\Notifiable;
use App\Notifications\BrowserNotification;
use NotificationChannels\WebPush\WebPushChannel;
use App\Http\Controllers\Api\v1\GeneralController;
use App\Http\Controllers\Api\v1\UTMDataController;
use NotificationChannels\WebPush\PushSubscription;
use App\Http\Controllers\Api\v1\CustomerController;
use App\Http\Controllers\Api\v1\ReferralController;
use App\Http\Controllers\Api\v1\SP\SPAuthController;
use App\Http\Controllers\Api\v1\UserTokenController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Api\Customer\TaskController;
use App\Http\Controllers\Api\SimpleOrderController;

use App\Http\Controllers\Api\v1\SP\SPDetailController;
use App\Http\Controllers\Api\v1\CustomerProfileController;
use App\Http\Controllers\Api\v1\ServiceBookingController;
use App\Http\Controllers\Api\v1\UnifiedBookingController;
use App\Http\Controllers\Api\v1\CustomerOrderController;
use App\Http\Controllers\Api\v1\ServiceProviderTaskController;
use App\Http\Controllers\Api\v1\TaskManagementController;
use App\Http\Controllers\Api\Customer\ChefBookingController;
use NotificationChannels\WebPush\PushSubscription as WebPushSubscription;

// Removed Sanctum middleware - using custom token validation

// Simple Orders API Routes
Route::apiResource('simple-orders', SimpleOrderController::class);


// Route::post('/login', [AuthController::class, 'login']);
Route::prefix('v1')->group(function () {

    // === Service Provider Routes ===
    Route::prefix('sp')->group(function () {
        Route::post('signup', [SPAuthController::class, 'signup'])->name('sp.register');
        Route::post('verify-otp', [SPAuthController::class, 'verifyOtp'])->name('sp.verifyOtp');
        Route::get('profile', [SPDetailController::class, 'getSPDetails'])->name('sp.getdetails');
        Route::post('profile', [SPDetailController::class, 'saveSPDetails'])->name('sp.savedetails');
    });
    // === Location Routes ===
    Route::prefix('general')->group(function () {
        Route::get('countries', [GeneralController::class, 'countryDetails'])->name('general.countries');
        Route::get('states', [GeneralController::class, 'statesDetails'])->name('general.states');
        Route::get('cities', [GeneralController::class, 'citiesDetails'])->name('general.cities');

        Route::get('states/{country_id}', [GeneralController::class, 'countryWiseStates'])->name('country.states');

        Route::get('cities/{state_id}', [GeneralController::class, 'stateWiseCities'])->name('state.cities');

        Route::get('data/{type}', [GeneralController::class, 'typeWiseData'])->name('helpstrr.data');
    });

    Route::prefix('referral')->group(function () {
        Route::post('sp/invite', [ReferralController::class, 'inviteDetailsSubmit'])->name('sp.invite');
    });

    //user token api

    Route::post('/user-token', [UserTokenController::class, 'store']);

    Route::get('job-roles', [SPDetailController::class, 'getJobRoles'])->name('sortkar.jobrole');

    Route::post('/subscribe', [PushSubscriptionController::class, 'store']);

    Route::post('/test-notify', [PushSubscriptionController::class, 'send']);

    Route::post('/utm-track', [UTMDataController::class, 'store']);

    // === Customer Routes ===
    Route::prefix('customer')->group(function () {

        // Task Management
        Route::prefix('tasks')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('customer.tasks.index');
            Route::post('/', [TaskController::class, 'store'])->name('customer.tasks.store');
            Route::get('/{id}', [TaskController::class, 'show'])->name('customer.tasks.show');
            Route::post('/{id}/cancel', [TaskController::class, 'cancel'])->name('customer.tasks.cancel');
            Route::post('/{id}/rate', [TaskController::class, 'rate'])->name('customer.tasks.rate');
            Route::post('/pricing-preview', [TaskController::class, 'pricingPreview'])->name('customer.tasks.pricing-preview');
            Route::get('/statistics', [TaskController::class, 'statistics'])->name('customer.tasks.statistics');
        });

        // Chef Booking Flow
        Route::prefix('chef')->group(function () {
            Route::get('/services', [ChefBookingController::class, 'getServices'])->name('customer.chef.services');
            Route::get('/cuisines', [ChefBookingController::class, 'getCuisines'])->name('customer.chef.cuisines');
            Route::get('/dietary-preferences', [ChefBookingController::class, 'getDietaryPreferences'])->name('customer.chef.dietary-preferences');
            Route::get('/optional-flags', [ChefBookingController::class, 'getOptionalFlags'])->name('customer.chef.optional-flags');
            Route::get('/pax-time-options', [ChefBookingController::class, 'getPaxAndTimeOptions'])->name('customer.chef.pax-time-options');
            Route::get('/addresses', [ChefBookingController::class, 'getAddresses'])->name('customer.chef.addresses');
            Route::get('/complete-flow', [ChefBookingController::class, 'getCompleteFlow'])->name('customer.chef.complete-flow');
            Route::post('/validate-booking', [ChefBookingController::class, 'validateBooking'])->name('customer.chef.validate-booking');
            Route::post('/pricing-preview', [ChefBookingController::class, 'getPricingPreview'])->name('customer.chef.pricing-preview');
            Route::get('/booking-rules', [ChefBookingController::class, 'getBookingRules'])->name('customer.chef.booking-rules');
            Route::post('/check-availability', [ChefBookingController::class, 'checkAvailability'])->name('customer.chef.check-availability');
        });
    });

    // === Customer Routes ===
    Route::prefix('customer')->group(function () {
        Route::post('login', [CustomerProfileController::class, 'login']);
        Route::post('send-otp', [CustomerProfileController::class, 'sendOtp']);
        Route::post('verify-otp', [CustomerProfileController::class, 'verifyOtp']);
        Route::post('profile', [CustomerProfileController::class, 'profileDetails']);

        Route::get('profile', [CustomerProfileController::class, 'getCustomerDetails'])->name('customer.getdetails');
    });


    // === Mobile API Routes ===

    // === Service Provider Mobile APIs ===

    // === Core API Routes ===

    // === Service Booking APIs ===
    // Route::prefix('bookings')->group(function () {
    //     Route::post('/', [ServiceBookingController::class, 'createBooking']);
    //     Route::post('/pricing-preview', [ServiceBookingController::class, 'getPricingPreview']);
    //     Route::get('/services', [ServiceBookingController::class, 'getAvailableServices']);
    // });

    // === Unified Booking APIs (New System) ===
    Route::prefix('bookings')->group(function () {
        Route::post('/', [UnifiedBookingController::class, 'createBooking']);
        Route::post('/pricing-preview', [UnifiedBookingController::class, 'getPricingPreview']);
        Route::get('/services', [UnifiedBookingController::class, 'getAvailableServices']);
    });

    // === Task Management APIs ===
    Route::prefix('tasks')->group(function () {
        Route::put('/{taskId}/status', [TaskManagementController::class, 'updateTaskStatus']);
        Route::post('/{taskId}/cancel', [TaskManagementController::class, 'cancelTask']);
        Route::post('/{taskId}/rate', [TaskManagementController::class, 'rateTask']);
        Route::get('/{taskId}', [TaskManagementController::class, 'getTaskDetails']);
    });

    // === Customer Order APIs ===
    Route::prefix('customer')->group(function () {
        Route::get('/orders', [CustomerOrderController::class, 'getCustomerOrders']);
        Route::get('/orders/{orderId}', [CustomerOrderController::class, 'getOrderDetails']);
        Route::get('/orders/statistics', [CustomerOrderController::class, 'getOrderStatistics']);
        Route::get('/orders/upcoming', [CustomerOrderController::class, 'getUpcomingOrders']);
    });

    // === Service Provider Task APIs ===
    Route::prefix('sp')->group(function () {
        Route::get('/dashboard', [ServiceProviderTaskController::class, 'getDashboard']);
        Route::get('/tasks', [ServiceProviderTaskController::class, 'getAssignedTasks']);
        Route::post('/task-requests/{broadcastId}/accept', [ServiceProviderTaskController::class, 'acceptTaskRequest']);
        Route::post('/task-requests/{broadcastId}/reject', [ServiceProviderTaskController::class, 'rejectTaskRequest']);
        Route::get('/earnings', [ServiceProviderTaskController::class, 'getEarnings']);
        Route::put('/availability', [ServiceProviderTaskController::class, 'updateAvailability']);
    });

    // === Allocation Engine APIs ===
    Route::prefix('allocation')->group(function () {
        Route::post('auto-assign', [\App\Http\Controllers\Api\AllocationEngineController::class, 'autoAssignTask']);
        Route::get('available-providers', [\App\Http\Controllers\Api\AllocationEngineController::class, 'getAvailableProviders']);
        Route::post('reassign', [\App\Http\Controllers\Api\AllocationEngineController::class, 'reassignTask']);
        Route::get('stats', [\App\Http\Controllers\Api\AllocationEngineController::class, 'getAllocationStats']);
    });

    // === Safety & Security APIs ===
    Route::prefix('safety')->group(function () {
        Route::post('panic-button', [\App\Http\Controllers\Api\SafetySecurityController::class, 'triggerPanicButton']);
        Route::get('alerts/{alertId}', [\App\Http\Controllers\Api\SafetySecurityController::class, 'getAlertStatus']);
        Route::post('verify-identity', [\App\Http\Controllers\Api\SafetySecurityController::class, 'verifyIdentity']);
        Route::get('guidelines', [\App\Http\Controllers\Api\SafetySecurityController::class, 'getSafetyGuidelines']);
        Route::post('report-incident', [\App\Http\Controllers\Api\SafetySecurityController::class, 'reportIncident']);
        Route::get('women-safety', [\App\Http\Controllers\Api\SafetySecurityController::class, 'getWomenSafetyFeatures']);
    });

    // === Pricing Logic APIs ===
    Route::prefix('pricing')->group(function () {
        Route::post('calculate', [\App\Http\Controllers\Api\PricingController::class, 'calculatePricing']);
        Route::get('surge', [\App\Http\Controllers\Api\PricingController::class, 'getSurgePricing']);
        Route::get('cancellation-policy', [\App\Http\Controllers\Api\PricingController::class, 'getCancellationPolicy']);
        Route::post('cancellation-charges', [\App\Http\Controllers\Api\PricingController::class, 'calculateCancellationCharges']);
    });

    // === Communication APIs ===
    Route::prefix('communication')->group(function () {
        Route::post('push-notification', [\App\Http\Controllers\Api\CommunicationController::class, 'sendPushNotification']);
        Route::post('sms', [\App\Http\Controllers\Api\CommunicationController::class, 'sendSMS']);
        Route::post('whatsapp', [\App\Http\Controllers\Api\CommunicationController::class, 'sendWhatsApp']);
        Route::post('ivr-call', [\App\Http\Controllers\Api\CommunicationController::class, 'makeIVRCall']);
        Route::post('task-notification', [\App\Http\Controllers\Api\CommunicationController::class, 'sendTaskNotification']);
        Route::get('notification-history', [\App\Http\Controllers\Api\CommunicationController::class, 'getNotificationHistory']);
    });
});
