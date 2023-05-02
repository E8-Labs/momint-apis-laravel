<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Minting\MintListingController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\ProfileUpdateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('signup', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);

Route::post('check_email_availablity', [UserAuthController::class, 'checkEmailAvailablity']);
Route::post('check_phone_availablity', [UserAuthController::class, 'checkPhoneAvailablity']);
Route::post('check_username_availablity', [UserAuthController::class, 'checkUsernameAvailablity']);

Route::post('send_test_email', [UserAuthController::class, 'sendTestEmail']);
Route::post('send_code', [UserAuthController::class, 'sendVerificationMail']);
Route::post('verify_email', [UserAuthController::class, 'confirmVerificationCode']);

Route::group([

    'middleware' => 'api',
    'prefix' => ''

], function ($router) {
	Route::get("me",[UserAuthController::class,'getMyProfile']);
	Route::get("profile",[UserAuthController::class,'getOtherUserProfile']);

    Route::post("delete_user",[UserAuthController::class,'deleteUser']);
    Route::post("disable_user",[UserAuthController::class,'disableUser']);

    Route::post('update_profile', [ProfileUpdateController::class, 'updateProfile']); // New

	//Minting
	Route::post("add_listing",[MintListingController::class,'addListing']);
    Route::post("delete_listing",[MintListingController::class,'deleteListing']);
    Route::post("flag_listing",[MintListingController::class,'flagListing']);
	Route::post("update_listing",[MintListingController::class,'updateListing']);
	Route::get("get_listings",[MintListingController::class,'getListings']);
    Route::get("get_flagged_listings",[MintListingController::class,'getFlaggedListings']);

    // Route::post('login', 'Auth\UserAuthController@login');
    Route::post('logout', 'Auth\UserAuthController@logout');
    Route::post('refresh', 'Auth\UserAuthController@refresh');
    Route::post('me', 'Auth\UserAuthController@me');

    Route::get('admin_dashboard', [AdminController::class, 'getGraphData']);
    Route::get('users', [AdminController::class, 'getUsers']);


    Route::get('notifications', [NotificationController::class, 'getNotifications']);//New
    Route::get('seen_notification', [NotificationController::class, 'notificationSeen']);//New

});
