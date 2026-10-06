<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login')->name('home');
Route::redirect('/login', '/admin/login')->name('login');
Route::view('/admin/login', 'admin.login')->name('admin.login');
Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');

Route::view('/payments/smart-abstract/success', 'payments.smart-abstract-return', ['success' => true])
    ->name('payments.smart-abstract.success');
Route::view('/payments/smart-abstract/cancel', 'payments.smart-abstract-return', ['success' => false])
    ->name('payments.smart-abstract.cancel');
