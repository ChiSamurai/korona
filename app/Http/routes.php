<?php

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::post('login', 'Auth\\AuthController@login');
Route::get('login', 'Auth\\AuthController@showLoginForm');
Route::get('logout', 'Auth\\AuthController@logout');
Route::post('password/email', 'Auth\\PasswordController@sendResetLinkEmail');
Route::post('password/reset', 'Auth\\PasswordController@reset');
Route::get('password/reset/{token?}', 'Auth\\PasswordController@showResetForm');
