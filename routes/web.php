<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/version', function () use ($router) {
    return $router->app->version();
});


Route::post('login', 'GeneralController@login');
Route::get('profile', 'GeneralController@profile');
Route::post('register', 'GeneralController@register');
Route::post('recipient-register', 'GeneralController@recipientRegister');
Route::get('check-forgot/{token}', 'GeneralController@checkForgotToken');
Route::get('check-username/{username}', 'GeneralController@checkUsernameExist');
Route::get('check-email/{email}', 'GeneralController@checkEmailExist');
Route::get('check-email-verification/{token}', 'GeneralController@checkEmailVerification');
Route::get('validate-email-verification/{token}', 'GeneralController@validateEmailVerification');
Route::get('check-phone/{phone}', 'GeneralController@checkPhoneExist');
Route::get('check-phone-verification/{otp}', 'GeneralController@checkPhoneVerification');
Route::get('validate-phone-verification/{otp}', 'GeneralController@validatePhoneVerification');
Route::post('forgot', 'GeneralController@forgotPassword');
Route::get(
    'check-forgot-token/{token}',
    'GeneralController@checkForgotToken'
);
Route::get('list-blood-gp', 'GeneralController@listBloodGp');
Route::get('list-years', 'GeneralController@listYears');
Route::get('list-states', 'GeneralController@listStates');
Route::get('list-cities', 'GeneralController@listCities');
Route::get('get-city-by-state/{name}', 'GeneralController@getCitiesByStateName');
Route::get('get-city-area-by-city/{name}', 'GeneralController@getCityAreaByCityName');
Route::put('update/{id}', 'GeneralController@updateUser');
Route::post('update-admin-password/{id}', 'GeneralController@updateAdmin');
Route::post('update-admin-image/{id}', 'GeneralController@updateAdminImage');
Route::put(
    'update-password/{token}',
    'GeneralController@updatePassword'
);
Route::post('contact', 'GeneralController@contactSubmit');
Route::post('blood-request', 'GeneralController@bloodRequest');
Route::get('list-blood-request/{type}', 'GeneralController@listBloodRequest');
Route::get('list-donor', 'GeneralController@listBloodDonor');
Route::get('list-all-donor', 'GeneralController@listAllBloodDonor');
Route::get('get-donor/{id}', 'GeneralController@getBloodDonorById');
Route::get('marquee', 'GeneralController@getMarquee');
Route::post('subscription', 'GeneralController@addSubscription');
Route::post('suggest-area', 'GeneralController@areaSuggestion');
Route::get('user-views', 'GeneralController@userViews');


Route::post('admin-login', 'GeneralController@adminLogin');
Route::put('admin-password', 'GeneralController@updateAdminPassword');
Route::get('admin-requests', 'GeneralController@listAdminBloodRequest');
Route::delete('admin-request/{id}', 'GeneralController@deleteBloodReqById');
Route::delete('admin-donor/{id}', 'GeneralController@deleteDonorById');
Route::delete('volunteer/{id}', 'GeneralController@deleteVolunteerById');
Route::get('donor-counter', 'GeneralController@donorCounter');
Route::get('request-counter', 'GeneralController@requestCounter');
Route::get('list-volunteers', 'GeneralController@listVolunteers');
Route::get('list-admin-volunteers', 'GeneralController@listAdminVolunteers');
Route::post('volunteer', 'GeneralController@addVolunteer');

// TODO deletion of blood donor, blood requests, listing of blood requests,  profile