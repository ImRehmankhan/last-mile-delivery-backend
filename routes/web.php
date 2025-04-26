<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hi', function () {
    return view('hi');
});

Route::post('/login', function (Request $request) {
    $username = $request->input('username');
    $password = $request->input('password');
    return view('showLogin', ['username' => $username, 'password' => $password]);
});