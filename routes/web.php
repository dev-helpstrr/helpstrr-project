<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Notifications\BrowserNotification;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CustomerController;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\PushSubscription;
use App\Http\Controllers\Referral\ReferralController;
use App\Http\Controllers\NotificationDetailController;
use App\Http\Controllers\SP\AUTH\SPRegisterController;


// Route::get('/', function () {
//     return view('welcome');
// });


Route::get('/', [SPRegisterController::class, 'homePage'])->name('sphome.page');
Route::get('/referral/registration', [ReferralController::class, 'showRegistrationForm'])->name('referralsignup.page');


Route::get('/signup-worker-form', [SPRegisterController::class, 'viewRegisterForm'])->name('signup.page');
Route::get('/service-provider', [SPRegisterController::class, 'ViewSPWelcomePage'])->name('spwelcome.page');

Route::get('/', [SPRegisterController::class, 'homePage'])->name('sphome.page');
Route::get('/referral/registration', [ReferralController::class, 'showRegistrationForm'])->name('referralsignup.page');

Route::prefix('sp')->group(function () {
    Route::post('/send-otp', [SPRegisterController::class, 'sendOtpAjax']);       // send OTP
    Route::post('/verify-otp', [SPRegisterController::class, 'verifyOtpAjax']);   // verify OTP
    Route::post('/form-details', [SPRegisterController::class, 'formDetailsAjax']); // fetch SP details
    Route::post('/save-details', [SPRegisterController::class, 'saveSpDetailsAjax']); // save SP details
    Route::get('/roles', [SPRegisterController::class, 'getRolesAjax']);          // fetch roles
});

Route::get('/notify', function () {
    return view('notify'); // loads notify.blade.php
})->name('notify');

Route::get('/send-notification', function () {
    $subscriptions = PushSubscription::all();

    foreach ($subscriptions as $subscription) {
        $subscription->sendNotification(
            (new WebPushMessage)
                ->title('Hello Guest!')
                ->body('This is a test notification for guest users.')
                ->icon('/icon.png')
                ->data(['url' => url('/notify')])
        );
    }

    return 'Notifications sent to all guests!';
});

Route::match(['get', 'post'], '/subscribe', function (Request $request) {
    $user = auth()->user(); // pick logged-in user
    $user->updatePushSubscription(
        $request->endpoint,
        $request->key,
        $request->token,
        $request->contentEncoding
    );
    return response()->json(['success' => true]);
});

Route::match(['get', 'post'], 'aws-file/', [SPRegisterController::class, 'showListedFiles']);

Route::get('aws/file/{value}', [SPRegisterController::class, 'getAWSFile'])->where('value', '.*')->name('awsfile.show');

Route::post('sp/referral', [ReferralController::class, 'store']);


Route::get('/get-templates/{type}', [TemplateController::class, 'getTemplatesByType']);
Route::get('/get-template/{id}', [TemplateController::class, 'getTemplateById']);

Route::post('/send-notification', [NotificationDetailController::class, 'sendNotification'])
    ->name('notifications.send');

Route::get('/debug/utm-check', function () {
    return \App\Models\UtmTracking::latest()->take(5)->get();
});

// Customer Management Routes
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('/statistics', [CustomerController::class, 'statistics'])->name('statistics');
    Route::post('/bulk-action', [CustomerController::class, 'bulkAction'])->name('bulk-action');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::patch('/{customer}', [CustomerController::class, 'update'])->name('patch');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    Route::patch('/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('toggle-status');
});

// Service Provider Management Routes
Route::prefix('service-providers')->name('service-providers.')->group(function () {
    Route::get('/', [App\Http\Controllers\ServiceProviderController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\ServiceProviderController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\ServiceProviderController::class, 'store'])->name('store');
    Route::get('/statistics', [App\Http\Controllers\ServiceProviderController::class, 'statistics'])->name('statistics');
    Route::post('/bulk-action', [App\Http\Controllers\ServiceProviderController::class, 'bulkAction'])->name('bulk-action');
    Route::get('/{serviceProvider}', [App\Http\Controllers\ServiceProviderController::class, 'show'])->name('show');
    Route::get('/{serviceProvider}/edit', [App\Http\Controllers\ServiceProviderController::class, 'edit'])->name('edit');
    Route::put('/{serviceProvider}', [App\Http\Controllers\ServiceProviderController::class, 'update'])->name('update');
    Route::patch('/{serviceProvider}', [App\Http\Controllers\ServiceProviderController::class, 'update'])->name('patch');
    Route::delete('/{serviceProvider}', [App\Http\Controllers\ServiceProviderController::class, 'destroy'])->name('destroy');
    Route::patch('/{serviceProvider}/toggle-status', [App\Http\Controllers\ServiceProviderController::class, 'toggleStatus'])->name('toggle-status');
});
