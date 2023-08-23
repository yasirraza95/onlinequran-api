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

Route::get('sliders', 'GeneralController@listSliders');
Route::post('sliders', 'GeneralController@addSliders');