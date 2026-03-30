<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//ez egyenlőre egy place holder, h ha erre az oldalra visz ne dobjon egyenlőre automatice hibát
Route::get('/login', function () {
    return 'login placeholder';
})->name('login');

