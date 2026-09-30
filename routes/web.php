<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login')->name('home');
Route::redirect('/login', '/admin/login')->name('login');
Route::view('/admin/login', 'admin.login')->name('admin.login');
Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
